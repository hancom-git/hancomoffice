<?php
/**
 *
 * (c) Copyright Hancom Inc
 *
 */

namespace OCA\HancomOffice\Preview;

use GuzzleHttp\Psr7\LimitStream;
use function GuzzleHttp\Psr7\stream_for;
use OC\Preview\Provider;
use OCP\Http\Client\IClientService;
use OCP\IConfig;
use OCP\ILogger;
use OCP\Image;
use OCP\IUserSession;

use OCA\HancomOffice\AppConfig;
use OCA\HancomOffice\Service\Preview\PreviewService;

abstract class Office extends Provider {

	/** @var IClientService */
	private $clientService;

	/** @var AppConfig */
	private $config;

	/** @var ILogger */
	private $logger;

    public function __construct(
        $UserId,
        IUserSession $userSession,
        IClientService $clientService,
        ILogger $logger,
        AppConfig $config,
        PreviewService $previewService
    ) {
        $this->uid = $UserId;
        $this->userSession = $userSession;
		$this->clientService = $clientService;
        $this->config = $config;
        $this->logger = $logger;
        $this->previewService = $previewService;
	}

	/**
	 * Check if DocsConverter url configured
	 */
	public function isAvailable(\OCP\Files\FileInfo $file) {
        $docsconverterurl = $this->config->GetDocsConverterUrl();
		return isset($docsconverterurl);
	}

	/**
	 * {@inheritDoc}
	 */
	public function getThumbnail($path, $maxX, $maxY, $scalingup, $fileview) {
        $isLoggedIn = $this->userSession->isLoggedIn();
        if ($isLoggedIn) {
            $userId = $this->userSession->getUser()->getUid();
        }

		$fileInfo = $fileview->getFileInfo($path);
		if (!$fileInfo || $fileInfo->getSize() === 0) {
			return false;
        }
        
        $imageUrl = $this->previewService->getImageUrl($fileInfo->getId(), $userId, $fileInfo->getPath());
        $client = $this->clientService->newClient();

        try {
			$response = $client->get($imageUrl, ['verify' => false]);
		} catch (\Exception $e) {
			$this->logger->logException($e, [
				'message' => 'Failed to convert file to preview',
				'level' => ILogger::INFO,
				'app' => 'hancomoffice',
			]);
			return false;
        }
        
        $image = new Image();
		$image->loadFromData($response->getBody());

		if ($image->valid()) {
			$image->scaleDownToFit($maxX, $maxY);
			return $image;
        }
        
		return false;
	}

}
