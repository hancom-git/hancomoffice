<?php
/**
 *
 * (c) Copyright Hancom Inc
 *
 */

namespace OCA\HancomOffice\Hooks;

use OCP\IUserSession;
use OCP\Files\IRootFolder;

use OCA\HancomOffice\Service\Lock\FileLock;

class Hooks {
    /** @var IRootFolder */
    private $root;

    /** @var IUserSession */
    private $userSession;

    /** @var FileLock */
    private $filelock;

    public function __construct(
        IRootFolder $root,
        IUserSession $userSession,
        FileLock $filelock
    ) {
        $this->root = $root;
        $this->userSession = $userSession;
        $this->filelock = $filelock;
    }

    /**
     * Check if user can rename/move file or folder
     * 
     * @param string $oldpath - oldpath
     * @param string $newpath - newpath
     * 
     * @return bool
     */
    public function rename($oldpath, $newpath) {
        try {
            if ($this->userSession->isLoggedIn()) {
                $userId = $this->userSession->getUser()->getUID();
                $file = $this->root->getUserFolder($userId)->get($oldpath);
                $isLocked = $this->filelock->isLocked($file, 0);
                if ($isLocked) {
                    return false;
                }
            }
        } catch (\Exception $e) {
            return false;
        }
        return true;
    }
}
