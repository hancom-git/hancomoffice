<?php
/**
 *
 * (c) Copyright Hancom Inc
 *
 */

namespace OCA\HancomOffice\Service\Lock;

use OC\DB\QueryBuilder\Literal;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;
use OCP\ILogger;
use OCP\Lock\ILockingProvider;
use OCP\Lock\LockedException;

use OC\Lock\AbstractLockingProvider;

/**
 * Locking provider that stores the locks in the database
 */
class FileLockingProvider {

	/** @var \OCP\IDBConnection */
	private $connection;

	/** @var ILogger */
	private $logger;

	/** @var ITimeFactory */
	private $timeFactory;

	private $sharedLocks = [];

	protected $ttl = 3600;

	protected $acquiredLocks = [
		'shared' => [],
		'exclusive' => []
	];

	public const LOCK_SHARED = 1;
	public const LOCK_EXCLUSIVE = 2;

	/**
	 * @param IDBConnection $connection
	 * @param ILogger $logger
	 * @param ITimeFactory $timeFactory
	 */
	public function __construct(
		IDBConnection $connection,
		ILogger $logger,
		ITimeFactory $timeFactory
	) {
		$this->connection = $connection;
		$this->logger = $logger;
		$this->timeFactory = $timeFactory;
	}

	/**
	 * Check if we've locally acquired a lock
	 *
	 * @param string $path
	 * @param int $type
	 * @return bool
	 */
	protected function hasAcquiredLock(string $path, int $type): bool {
		if ($type === self::LOCK_SHARED) {
			return isset($this->acquiredLocks['shared'][$path]) && $this->acquiredLocks['shared'][$path] > 0;
		} else {
			return isset($this->acquiredLocks['exclusive'][$path]) && $this->acquiredLocks['exclusive'][$path] === true;
		}
	}

	/**
	 * Insert a file locking row if it does not exists.
	 *
	 * @param string $key
	 * @param int $lock
	 * @return int number of inserted rows
	 */
	protected function initLockField(string $key, int $lock = 0): int {
		$expire = $this->getExpireTime();
		return $this->connection->insertIgnoreConflict('hancomoffice_locks', [
			'key' => $key,
			'lock' => $lock,
			'ttl' => $expire
		]);
	}

	/**
	 * @return int
	 */
	protected function getExpireTime(): int {
		return $this->timeFactory->getTime() + $this->ttl;
	}

	/**
	 * @param string $key
	 * @param int $type self::LOCK_SHARED or self::LOCK_EXCLUSIVE
	 * @return bool
	 */
	public function isLocked(string $key, int $type): bool {
		if ($this->hasAcquiredLock($key, $type)) {
			return true;
		}
		$query = $this->connection->prepare('SELECT `lock` from `*PREFIX*hancomoffice_locks` WHERE `key` = ?');
		$query->execute([$key]);
		$lockValue = (int)$query->fetchColumn();
		if ($type === self::LOCK_SHARED) {
			return $lockValue > 0;
		} elseif ($type === self::LOCK_EXCLUSIVE) {
			return $lockValue === -1;
		} elseif ($type === 0) {
			return $lockValue !== 0;
		} else {
			return false;
		}
	}

	/**
	 * @param string $key
	 * @param int $type self::LOCK_SHARED or self::LOCK_EXCLUSIVE
	 * @throws \OCP\Lock\LockedException
	 */
	public function acquireLock(string $key, int $type) {
		$expire = $this->getExpireTime();
		if ($type === self::LOCK_SHARED) {
			$result = $this->initLockField($key, 1);
			if ($result <= 0) {
				$result = $this->connection->executeUpdate(
					'UPDATE `*PREFIX*hancomoffice_locks` SET `lock` = `lock` + 1, `ttl` = ? WHERE `key` = ? AND `lock` >= 0',
					[$expire, $key]
				);
			}
		} else {
			$result = $this->initLockField($key, -1);
			if ($result <= 0) {
				$result = $this->connection->executeUpdate(
					'UPDATE `*PREFIX*hancomoffice_locks` SET `lock` = -1, `ttl` = ? WHERE `key` = ?',
					[$expire, $key]
				);
			}
		}
		if ($result !== 1) {
			throw new LockedException($key);
		}
	}

	/**
	 * @param string $key
	 * @param int $type self::LOCK_SHARED or self::LOCK_EXCLUSIVE
	 *
	 * @suppress SqlInjectionChecker
	 */
	public function releaseLock(string $key, int $type) {
		if ($type === self::LOCK_EXCLUSIVE) {
			$this->connection->executeUpdate(
				'UPDATE `*PREFIX*hancomoffice_locks` SET `lock` = 0 WHERE `key` = ? AND `lock` = -1',
				[$key]
			);
		} else {
			$query = $this->connection->getQueryBuilder();
			$query->update('hancomoffice_locks')
				->set('lock', $query->func()->subtract('lock', $query->createNamedParameter(1)))
				->where($query->expr()->eq('key', $query->createNamedParameter($key)))
				->andWhere($query->expr()->gt('lock', $query->createNamedParameter(0)));
			$query->execute();
		}
	}

	/**
	 * Change the type of an existing lock
	 *
	 * @param string $key
	 * @param int $targetType self::LOCK_SHARED or self::LOCK_EXCLUSIVE
	 * @throws \OCP\Lock\LockedException
	 */
	public function changeLock(string $key, int $targetType) {
		$expire = $this->getExpireTime();
		if ($targetType === self::LOCK_SHARED) {
			$result = $this->connection->executeUpdate(
				'UPDATE `*PREFIX*hancomoffice_locks` SET `lock` = 1, `ttl` = ? WHERE `key` = ? AND `lock` = -1',
				[$expire, $key]
			);
		} else {
			// since we only keep one shared lock in the db we need to check if we have more then one shared lock locally manually
			if (isset($this->acquiredLocks['shared'][$key]) && $this->acquiredLocks['shared'][$key] > 1) {
				throw new LockedException($key);
			}
			$result = $this->connection->executeUpdate(
				'UPDATE `*PREFIX*hancomoffice_locks` SET `lock` = -1, `ttl` = ? WHERE `key` = ? AND `lock` = 1',
				[$expire, $key]
			);
		}
		if ($result !== 1) {
			throw new LockedException($key);
		}
	}

	/**
	 * cleanup empty locks
	 */
	public function cleanExpiredLocks() {
		$expire = $this->timeFactory->getTime();
		try {
			$this->connection->executeUpdate(
				'DELETE FROM `*PREFIX*hancomoffice_locks` WHERE `ttl` < ?',
				[$expire]
			);
		} catch (\Exception $e) {
			// If the table is missing, the clean up was successful
			if ($this->connection->tableExists('hancomoffice_locks')) {
				throw $e;
			}
		}
	}

	/**
	 * release all lock acquired by this instance which were marked using the mark* methods
	 *
	 * @suppress SqlInjectionChecker
	 */
	public function releaseAll() {
		// we don't need to release locks, expired locks cleans with hooks
	}

}
