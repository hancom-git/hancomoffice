<?php
/**
 *
 * (c) Copyright Hancom Inc
 *
 */

namespace OCA\HancomOffice\BackgroundJob;

use OCP\BackgroundJob\TimedJob;

use OCP\AppFramework\Utility\ITimeFactory;

use OCA\HancomOffice\Service\Lock\FileLockingProvider;

/**
 * Clean up all file locks that are expired for the DB file locking provider
 */
class CleanFileLocks extends TimedJob {

	/** @var FileLockingProvider */
	private $lockingProvider;

	public function __construct(
		ITimeFactory $time,
		FileLockingProvider $lockingProvider
	) {
		parent::__construct($time);

		$this->lockingProvider = $lockingProvider;

		$this->setInterval(5 * 60);
	}

	/**
	 * Makes the background job do its work
	 *
	 * @param array $argument unused argument
	 * @throws \Exception
	 */
	protected function run($argument) {
		$lockingProvider = $this->lockingProvider;
		if ($lockingProvider instanceof FileLockingProvider) {
			$lockingProvider->cleanExpiredLocks();
		}
	}
}
