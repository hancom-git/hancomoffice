<?php
/**
 *
 * (c) Copyright Hancom Inc Inc
 *
 */

namespace OCA\HancomOffice\Controller;

use OC\Files\Filesystem;

use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataDownloadResponse;
use OCP\AppFramework\Http\JSONResponse;
use OCP\Files\File;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use OCP\Files\NotFoundException;
use OCP\Files\NotPermittedException;
use OCP\IL10N;
use OCP\ILogger;
use OCP\IRequest;
use OCP\IUserManager;
use OCP\IUserSession;
use OCP\Share\IManager;

use OCP\Lock\LockedException;
use OCP\Files\GenericFileException;

use OCA\HancomOffice\AppConfig;
use OCA\HancomOffice\Service\Lock\FileLock;
use OCA\HancomOffice\Service\Lock\FileLockingProvider;
use OCA\HancomOffice\XMLResponse;

/**
 * Callback handler for the document server.
 * Download the file without authentication.
 * Save the file without authentication.
 */
class CallbackController extends Controller {

    /** @var IRootFolder */
    private $root;

    /** @var IUserSession */
    private $userSession;

    /** @var IUserManager */
    private $userManager;

    /** @var IManager */
	private $shareManager;

    /** @var IL10N */
    private $trans;

    /** @var OCP\ILogger */
    private $logger;

    /** @var AppConfig */
    private $config;

    /** @var FileLock */
    private $filelock;

    /**
     * @param string $AppName - application name
     * @param IRequest $request - request object
     * @param IRootFolder $root - root folder
     * @param IUserSession $userSession - current user session
     * @param IUserManager $userManager - user manager
     * @param IL10N $trans - l10n service
     * @param ILogger $logger - logger
     * @param AppConfig $config - application configuration
     * @param FileLock $filelock - Locking Service
     */
    public function __construct($AppName,
                                    IRequest $request,
                                    IRootFolder $root,
                                    IUserSession $userSession,
                                    IUserManager $userManager,
                                    IManager $shareManager,
                                    IL10N $trans,
                                    ILogger $logger,
                                    AppConfig $config,
                                    FileLock $filelock
                                    ) {
        parent::__construct($AppName, $request);

        $this->root = $root;
        $this->userSession = $userSession;
        $this->userManager = $userManager;
        $this->shareManager = $shareManager;
        $this->trans = $trans;
        $this->logger = $logger;
        $this->config = $config;
        $this->filelock = $filelock;
    }

    /**
     * Get path info for api
     * 
     * @param File $file - file to look for info
     * 
     * @return array
     */
    private function getPathInfo($file)  {
        $isFile = $file instanceof File;
        $isLocked = $this->filelock->isLocked($file,
            $isFile ? FileLockingProvider::LOCK_EXCLUSIVE : FileLockingProvider::LOCK_SHARED
        );
        
        $data = [
            '$name' => $file->getName(),
            'type' => $isFile ? 'file' : 'folder',
            'modified' => $file->getMTime() * 1000,
            'read' => $file->isReadable() ? 'true' : 'false',
            'write' => $file->isUpdateable() ? 'true' : 'false',
            'locked' => $isLocked ? 'true' : 'false',
            'path' => $file->getPath(),
            'size' => $isFile ? $file->getSize() : -1
        ];

        return $data;
    }

    /**
     * Get file by file od
     * 
     * @param string $user - user id
     * @param string $fid - instance id + file id
     * 
     * @return File
     */
    private function getFileById($user, $fid) {
        list($instanceId, $fileId) = explode('_', $fid);
        $file = $this->root->getUserFolder($user)->getById($fileId)[0];

        return $file;
    }

    /**
     * Get user by share token
     * 
     * @param string $shareToken - share token
     * 
     * @return string
     */
    private function getUserByShareToken($user, $shareToken) {
        if ($shareToken) {
            $share = $this->shareManager->getShareByToken($shareToken);
            if ($share) {
                $user = $share->getShareOwner();
            }
        }
        return $user;
    }

    /**
     * Get file info
     * 
     * @param string $user - user id
     * @param string $path - file path
     * @param string $fid - file id
     * 
     * @return string
     */
    private function getInfo($user, $path, $fid, $shareToken) {
        $this->logger->debug("Callback INFO $fid", ["app" => $this->appName]);
        try {
            $owner = $this->getUserByShareToken($user, $shareToken);
            $file = $this->getFileById($owner, $fid);
            $data = [
                'property' => $this->getPathInfo($file)
            ];
        } catch (NotFoundException $e)  {
            return new DataDownloadResponse("NotExist", "", "text/html");
        } catch (\Exception $e) {
            $this->logger->debug($e, ["app" => $this->appName]);
            $data = [];
        }

        return new XMLResponse($data);
    }

    /**
     * API Get file info
     * 
     * @param string $path - file path
     * @param string $user_id - user id
     * @param string $fid - file id
     * 
     * @return string
     * 
     * @NoAdminRequired
     * @NoCSRFRequired
     * @PublicPage
     * @CORS
     */
    public function info($path, $user_id, $fid, $share_token) {
        return $this->getInfo($user_id, $path, $fid, $share_token);
    }

    /**
     * API Get root folder info
     * 
     * @param string $user_id - user id
     * @param string $fid - file id
     * 
     * @return string
     * 
     * @NoAdminRequired
     * @NoCSRFRequired
     * @PublicPage
     * @CORS
     */
    public function infoRoot($user_id, $fid, $share_token) {
        return $this->getInfo($user_id, "", $fid, $share_token);
    }

    /**
     * Get folder list
     * 
     * @param string $user - user id
     * @param string $path - file path
     * @param string $fid - file id
     * 
     * @return string
     */
    private function getList($user, $path, $fid, $shareToken) {
        $this->logger->debug("Callback LIST $fid", ["app" => $this->appName]);
        try {
            $owner = $this->getUserByShareToken($user, $shareToken);
            $file = $this->getFileById($owner, $fid);
            $subs = $file->getDirectoryListing();
        } catch (\Exception $e) {
            $subs = [];
        }
        
        $data = [
            'list' => array_map(function ($file) {
                return $this->getPathInfo($file);
            }, $subs)
        ];

        return new XMLResponse($data);
    }

    /**
     * API Get folder list
     * 
     * @param string $path - file path
     * @param string $user_id - user id
     * @param string $fid - file id
     * 
     * @return string
     * 
     * @NoAdminRequired
     * @NoCSRFRequired
     * @PublicPage
     * @CORS
     */
    public function list($path, $user_id, $fid, $share_token) {
        return $this->getList($user_id, $path, $fid, $share_token);
    }

    /**
     * API Get root folder list
     * 
     * @param string $user_id - user id
     * @param string $fid - file id
     * 
     * @NoAdminRequired
     * @NoCSRFRequired
     * @PublicPage
     * @CORS
     */
    public function listRoot($user_id, $fid, $share_token) {
        return $this->getList($user_id, "", $fid, $share_token);
    }

    /**
     * API Get file
     * 
     * @param string $path - file path
     * @param string $user_id - user id
     * @param string $fid - file id
     * 
     * @return string
     * 
     * @NoAdminRequired
     * @NoCSRFRequired
     * @PublicPage
     * @CORS
     */
    public function get($path, $user_id, $fid, $share_token) {
        $this->logger->debug("Callback GET $fid", ["app" => $this->appName]);
        try {
            $owner = $this->getUserByShareToken($user_id, $share_token);
            $file = $this->getFileById($owner, $fid);
            $content = $file->getContent();
            return new DataDownloadResponse($content, $file->getName(), $file->getMimeType());
        } catch (NotFoundException $e)  {
            return new JSONResponse("File Not Found", Http::STATUS_NOT_FOUND);
        } catch (NotPermittedException  $e) {
            $this->logger->logException($e, ["message" => "Download Not permitted: $fileId", "app" => $this->appName]);
            return new JSONResponse("Internal Server Error", Http::STATUS_INTERNAL_SERVER_ERROR);
        } catch (\Exception $e) {
            $this->logger->logException($e, ["message" => "Download file error: $fileId", "app" => $this->appName]);
            return new JSONResponse("Internal Server Error", Http::STATUS_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * API Put file
     * 
     * @param string $path - file path
     * @param string $user_id - user id
     * @param string $fid - file id
     * 
     * @return string
     * 
     * @NoAdminRequired
     * @NoCSRFRequired
     * @PublicPage
     * @CORS
     */
    public function put($path, $user_id, $fid, $share_token) {
        $this->logger->debug("Callback PUT $fid", ["app" => $this->appName]);
        try {
            $this->setUser($user_id);
            $this->setUserFS($user_id);

            $content = fopen('php://input', 'rb');

            $owner = $this->getUserByShareToken($user_id, $share_token);
            $file = $this->getFileById($owner, $fid);
            $this->retryOperation(function () use ($file, $content){
                return $file->putContent($content);
            });
            return new DataDownloadResponse((string) $file->getId(), "", "text/html");
        } catch (NotPermittedException $e) {
            $this->logger->logException($e, ["Can't save file: $name", "app" => $this->appName]);
            return new JSONResponse("Internal Server Error", Http::STATUS_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * API Lock file
     * 
     * @param string $path - file path
     * @param string $user_id - user id
     * @param string $fid - file id
     * 
     * @return string
     * 
     * @NoAdminRequired
     * @NoCSRFRequired
     * @PublicPage
     * @CORS
     */
    public function lock($path, $user_id, $fid, $share_token) {
        $this->logger->debug("Callback LOCK $fid", ["app" => $this->appName]);
        try {
            $owner = $this->getUserByShareToken($user_id, $share_token);
            $file = $this->getFileById($owner, $fid);
            $this->filelock->lock($file, FileLockingProvider::LOCK_EXCLUSIVE);
        } catch (NotFoundException $e)  {
            return new JSONResponse("File Not Found", Http::STATUS_NOT_FOUND);
        } catch (\Exception $e) {
            return new JSONResponse("Internal Server Error", Http::STATUS_INTERNAL_SERVER_ERROR);
        }

        return '';
    }

    /**
     * API Unlock file
     * 
     * @param string $path - file path
     * @param string $user_id - user id
     * @param string $fid - file id
     * 
     * @return string
     * 
     * @NoAdminRequired
     * @NoCSRFRequired
     * @PublicPage
     * @CORS
     */
    public function unlock($path, $user_id, $fid, $share_token) {
        $this->logger->debug("Callback UNLOCK $fid", ["app" => $this->appName]);
        try {
            $owner = $this->getUserByShareToken($user_id, $share_token);
            $file = $this->getFileById($owner, $fid);
            $this->filelock->unlock($file, FileLockingProvider::LOCK_EXCLUSIVE);
        } catch (NotFoundException $e)  {
            return new JSONResponse("File Not Found", Http::STATUS_NOT_FOUND);
        } catch (\Exception $e) {
            return new JSONResponse("Internal Server Error", Http::STATUS_INTERNAL_SERVER_ERROR);
        }

        return '';
    }

    /**
	 * Retry operation if a LockedException occurred
	 * Other exceptions will still be thrown
	 * @param callable $operation
	 * @throws LockedException
	 * @throws GenericFileException
	 */
	private function retryOperation(callable $operation) {
		for ($i = 0; $i < 5; $i++) {
			try {
				if ($operation() !== false) {
					return;
				}
			} catch (LockedException $e) {
				if ($i === 4) {
					throw $e;
				}
				usleep(600000);
			}
		}
		throw new GenericFileException('Operation failed');
    }
    
    /**
     * Set user to make changes from his name (activity etc.)
     * @param string $user_id
     * @throws \InvalidArgumentException
     */
    private function setUser(string $user_id) {
        $user = $this->userManager->get($user_id);
        if ($user === null) {
			throw new \InvalidArgumentException('No user found for the uid ' . $uid);
		}
        $this->userSession->setUser($user);
    }

    /**
     * Set user fs to make changes from his name (versions etc)
     * @param string $user_id
     */
    private function setUserFS(string $user_id) {
        \OC_Util::tearDownFS();
        \OC_Util::setupFS($user_id);
    }
}
