<?php
/**
 *
 * (c) Copyright Hancom Inc
 *
 */

namespace OCA\HancomOffice\Service\Lock;

use OC\Files\Filesystem;
use OC\Files\Node\Root;
use OC\AppFramework\Utility\TimeFactory;

use OCP\Files\File;
use OCP\Files\View;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\ILogger;
use OCP\Lock\ILockingProvider;
use OCP\Lock\LockedException;

use OCA\HancomOffice\Service\Lock\FileLockingProvider;

class FileLock {

    /** @var FileLockingProvider */
    private $lockingProvider;

    /** @var \OCP\AppFramework\Utility\ITimeFactory */
	private $timeFactory;

    /** @var \OCP\ILogger */
    private $logger;

    public function __construct(
        FileLockingProvider $lockingProvider,
        ITimeFactory $timeFactory,
        ILogger $logger
    ) {
        $this->lockingProvider = $lockingProvider;
        $this->timeFactory = $timeFactory;
        $this->logger = $logger;
    }

    /**
     * Iterate over path to root folder
     * 
     * @param File $file - file
     * @param int $type - locking type
     * @param Callable $fn - function
     */
    private function overPathWithParents($file, $type, $fn) {
        $fn($file->getId(), $type);

        $parent = $file->getParent();
        while (!($parent instanceof Root)) {
            $fn($parent->getId(), ILockingProvider::LOCK_SHARED);
            $parent = $parent->getParent();
        }
    }

    /**
     * Lock file
     * 
     * @param File $file - file
     * @param int $type - locking type
     */
    public function lock($file, $type) {
        $this->overPathWithParents($file, $type, function($key, $type) {
            $this->lockingProvider->acquireLock($key, $type);
        });
    }

    /**
     * Unlock file
     * 
     * @param File $file - file
     * @param int $type - locking type
     */
    public function unlock($file, $type) {
        $this->overPathWithParents($file, $type, function($key, $type) {
            $this->lockingProvider->releaseLock($key, $type);
        });
    }

    /**
     * Change lock - no need
     * 
     * @param File $file - file
     * @param int $type - locking type
     */
    public function changeLock($file, $type) {
        return false;
    }

    /**
     * Get locked status
     * 
     * @param File $file - file
     * @param int $type - locking type
     * 
     * @return bool
     */
    public function isLocked($file, $type) {
        return $this->lockingProvider->isLocked($file->getId(), $type);
    }
    
}
