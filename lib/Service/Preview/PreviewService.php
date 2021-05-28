<?php
/**
 *
 * (c) Copyright Hancom Inc
 *
 */

namespace OCA\HancomOffice\Service\Preview;

use OCP\ILogger;
use OCP\IURLGenerator;

use OCA\HancomOffice\AppConfig;

/**
 * Preview helper service
 */
class PreviewService {

    /**
     * Url generator service
     *
     * @var IURLGenerator
     */
    private $urlGenerator;

    /**
     * Logger
     *
     * @var ILogger
     */
    private $logger;

    /**
     * Application configuration
     *
     * @var AppConfig
     */
    private $config;

    /**
     * @param IURLGenerator $urlGenerator - url generator service
     * @param ILogger $logger - logger
     * @param AppConfig $config - application configuration
     */
    public function __construct(
        $AppName,
        IURLGenerator $urlGenerator,
        ILogger $logger,
        AppConfig $config
    ) {
        $this->appName = $AppName;
        $this->urlGenerator = $urlGenerator;
        $this->logger = $logger;
        $this->config = $config;
    }

    /** 
     * Request to DocConverter
     * 
     * @param string $url - requested url
     * 
     * @return string
     */
    private function convert($url) {
        $options = [
            "ssl" => [
                "verify_peer"=>false,
                "verify_peer_name"=>false,
            ],
        ];
        $context  = stream_context_create($options);
        $result = file_get_contents($url, false, $context);

        return $result;
    }

    /**
     * Detect convert mode from Office Online -> DocsConverter
     * @param string @filePath - file path
     * 
     * @return string
     */
    private function detectMode($filePath) {
        $ext = array_slice(explode('.', $filePath), -1)[0];
        $formats = $this->config->FormatsSetting();
        $options = $formats[$ext];
        return $this->modes[$options['type']];
    }

    /**
     * Retrieve DocsConverter url
     *
     * @param string $fileId - file id
     * @param number $userId - user id
     * @param string $filePath - file path
     * @param string $output - output mode
     * 
     * @return string
     */
    private function getConversionUrl($fileId, $userId, $filePath = null, $output, $options) {
        $docsconverterUrl = $this->config->GetDocsConverterUrl();
        $instanceId = $this->config->getSystemValue('instanceid');

        $fullFileId = $instanceId . '_' . $fileId;

        $fileUrl = $this->urlGenerator->linkToRouteAbsolute($this->appName . ".callback.get", [
            "path" => substr($filePath, 1),
            "fid" => $fullFileId,
            "user_id" => $userId
        ]);
        $mode = $this->detectMode($filePath);
        $queryDefaultOptions = [
            'inputfile' => $fileUrl,
            'filter' => $mode . '-' . $output,
            'ignorecache' => 'true',
            'responsetype' => 'json',
        ];

        if (isset($options)) {
            $queryOptions = (object) array_merge((array) $queryDefaultOptions, (array) $options);
        } else {
            $queryOptions = $queryDefaultOptions;
        }

        $query = http_build_query($queryOptions);
        
        return $docsconverterUrl . 'hermes/convert.hs?' . $query;
    }

    /**
     * Get HView url in DocsConverter storage
     * 
     * @param string $fileId - file id
     * @param string $userId - user id
     * @param string $filePath - file path
     * 
     * @return string|null
     */
    public function getHViewUrl($fileId, $userId, $filePath) {
        $docsconverterUrl = $this->config->GetDocsConverterUrl();
        $url = $this->getConversionUrl($fileId, $userId, $filePath, 'html', [
            'viewtype' => 'HView'
        ]);
        $this->logger->debug($url);
        $response = $this->convert($url);
        $data = json_decode($response);

        $resourceId = $data ? $data->response->resources[0] : null;
        $hviewId = $resourceId ? join('/', array_slice(explode('/', urldecode($resourceId)), 0, 3)) : null;

        return $hviewId ?
            ($docsconverterUrl . 'hermes/resource/store/' . $hviewId . '/hview.html') :
            null;
    }

    /**
     * Get image url in DocsConverter storage
     * 
     * @param string $fileId - file id
     * @param string $userId - user id
     * @param string $filePath - file path
     * 
     * @return string|null
     */
    public function getImageUrl($fileId, $userId, $filePath) {
        $docsconverterUrl = $this->config->GetDocsConverterUrl();
        $url = $this->getConversionUrl($fileId, $userId, $filePath, 'image', [
            'firstpage' => 1,
            'lastpage' => 1
        ]);
        $response = $this->convert($url);
        $data = json_decode($response);

        $resourcePath = $data ? $data->response->resources[0] : null;

        return $resourcePath ?
            ($docsconverterUrl . 'hermes/resource/store/' . $resourcePath) :
            null;
    }

    /**
     * Supported types from Office Online to input modes DocsConverter
     */
    private $modes = [
        'WRITE' => 'doc',
        'CALC' => 'xls',
        'SHOW' => 'ppt',
        'HWP' => 'hwp'
    ];
}
