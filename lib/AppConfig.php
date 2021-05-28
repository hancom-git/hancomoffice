<?php
/**
 *
 * (c) Copyright Hancom Inc
 *
 */

namespace OCA\HancomOffice;

use \DateInterval;
use \DateTime;

use OCP\IConfig;
use OCP\ILogger;

/**
 * Application configutarion
 *
 * @package OCA\HancomOffice
 */
class AppConfig {

    /** @var string */
    private $appName;

    /** @var IConfig */
    private $config;

    /** @var ILogger */
    private $logger;

    /**
     * The config key for the document server address
     *
     * @var string
     */
    private $_documentserver = "DocumentServerUrl";

    /**
     * The config key for type of server
     *
     * @var string
     */
    private $_servertype = "ServerType";

    /**
     * The config key for the demo server
     *
     * @var string
     */
    private $_demoserver = "DemoServerUrl";

    /**
     * The config key for the DocsConverter server address
     *
     * @var string
     */
    private $_docsconverter = "DocsConverterUrl";

    /**
     * @param string $AppName - application name
     */
    public function __construct($AppName) {

        $this->appName = $AppName;

        $this->config = \OC::$server->getConfig();
        $this->logger = \OC::$server->getLogger();
    }

    /**
     * Get value from the system configuration
     *
     * @param string $key - key
     * @param bool $system - get from root or from app section
     *
     * @return string
     */
    public function GetSystemValue($key) {
        return $this->config->getSystemValue($key);
    }

    /**
     * Get user value
     *
     * @param string $uid - user id
     * @param string app - application
     * @param string $key - key
     * @param string $default - default value
     *
     * @return string
     */
    public function GetUserValue($uid, $app, $key, $default) {
        return $this->config->getUserValue($uid, $app, $key, $default);
    }

    /**
     * Save the weboffice address to the application configuration
     *
     * @param string $documentServer - weboffice address
     */
    public function SetDocumentServerUrl($documentServer) {
        $documentServer = trim($documentServer);
        if (strlen($documentServer) > 0) {
            $documentServer = rtrim($documentServer, "/") . "/";
            if (!preg_match("/(^https?:\/\/)|^\//i", $documentServer)) {
                $documentServer = "http://" . $documentServer;
            }
        }

        $this->logger->info("Hancom Office Online URL: $documentServer", ["app" => $this->appName]);

        $this->config->setAppValue($this->appName, $this->_documentserver, $documentServer);
    }

    /**
     * Get the weboffice address from the application configuration
     *
     * @param bool $origin - take origin
     *
     * @return string
     */
    public function GetDocumentServerUrl($origin = false) {
        $url = $this->config->getAppValue($this->appName, $this->_documentserver, "");
        if ($url !== "/") {
            $url = rtrim($url, "/");
            if (strlen($url) > 0) {
                $url = $url . "/";
            }
        }
        return $url;
    }

    /**
     * Save the type of document server
     *
     * @param string $serverType - type
     */
    public function SetServerType($serverType) {
        $this->config->setAppValue($this->appName, $this->_servertype, $serverType);
    }

    /**
     * Retrieve the type of document server
     * 
     * @return string
     */
    public function GetServerType() {
        return $this->config->getAppValue($this->appName, $this->_servertype, "own");
    }

    /**
     * Save the url of demo server
     *
     * @param string $serverType - type
     */
    public function SetDemoServerUrl($demoServerUrl) {
        $this->config->setAppValue($this->appName, $this->_demoserver, $demoServerUrl);
    }

    /**
     * Retrieve the url of demo server
     * 
     * @return string
     */
    public function GetDemoServerUrl() {
        return $this->config->getAppValue($this->appName, $this->_demoserver, "");
    }

    /**
     * Save the DocsConverter address to the application configuration
     *
     * @param string $documentServer - weboffice address
     */
    public function SetDocsConverterUrl($docsconverter) {
        $docsconverter = trim($docsconverter);
        if (strlen($docsconverter) > 0) {
            $docsconverter = rtrim($docsconverter, "/") . "/";
            if (!preg_match("/(^https?:\/\/)|^\//i", $docsconverter)) {
                $docsconverter = "http://" . $docsconverter;
            }
        } 

        $this->logger->info("DocsConverter URL: $docsconverter", ["app" => $this->appName]);

        $this->config->setAppValue($this->appName, $this->_docsconverter, $docsconverter);
    }

    /**
     * Get the DocsConverter address from the application configuration
     *
     * @param bool $origin - take origin
     *
     * @return string
     */
    public function GetDocsConverterUrl($origin = false) {
        $url = $this->config->getAppValue($this->appName, $this->_docsconverter, "");
        if ($url !== "/") {
            $url = rtrim($url, "/");
            if (strlen($url) > 0) {
                $url = $url . "/";
            }
        }
        return $url;
    }

    /**
     * Get host
     *
     * @return string
     */
    public function GetHost() {
        $type = $this->GetServerType();
        if ($type === "own") {
            $documentserver = $this->GetDocumentServerUrl();
        }
        if ($type === "demo") {
            $documentserver = $this->GetDemoServerUrl();
        }
        return $documentserver;
    }

    /**
     * Check access for group
     *
     * @return bool
     */
    public function isUserAllowedToUse() {
        // no user -> no
        $userSession = \OC::$server->getUserSession();
        if ($userSession === null || !$userSession->isLoggedIn()) {
            return false;
        }

        return true;
    }

    /**
     * Get supported formats
     *
     * @return array
     *
     * @NoAdminRequired
     */
    public function FormatsSetting() {
        return $this->formats;
    }

    /**
     * Get supported app modes
     *
     * @return array
     */
    public function AppsSetting() {
        return $this->apps;
    }

    /**
     * Get supported demo hosts
     *
     * @return array
     */
    public function getHosts() {
        return $this->hosts;
    }

    /**
     * Demo hosts
     *
     * @var array
     */
    private $hosts = [
        'Hancom Office Online Demo - Not available' => 'https://serveraddress/'        
    ];

    /**
     * Additional data about apps
     *
     * @var array
     */
    private $apps = [
        "WRITE" => ["name" => "webwordApp"],
        "CALC" => ["name" => "webcellApp"],
        "SHOW" => ["name" => "webshowApp"],
        "HWP" => ["name" => "webwordApp"]
    ];

    /**
     * Additional data about formats
     *
     * @var array
     */
    private $formats = [
        // text
        "doc" => ["mime" => "application/msword", "type" => "WRITE"],
        "docx" => ["mime" => "application/vnd.openxmlformats-officedocument.wordprocessingml.document", "type" => "WRITE"],
        "hwp" => ["mime" => "application/haansofthwp", "type" => "HWP"],
        "hwpx" => ["mime" => "application/haansofthwpx", "type" => "HWP"],
        "owpml" => ["mime" => "application/haansoftowpml", "type" => "HWP"],

        // presentation
        "show" => ["mime" => "application/haansoftshow", "type" => "SHOW"],
        "ppt" => ["mime" => "application/vnd.ms-powerpoint", "type" => "SHOW"],
        "pptx" => ["mime" => "application/vnd.openxmlformats-officedocument.presentationml.presentation", "type" => "SHOW"],

        // tables
        "cell" => ["mime" => "application/haansoftcell", "type" => "CALC"],
        "xls" => ["mime" => "application/vnd.ms-excel", "type" => "CALC"],
        "xlsx" => ["mime" => "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet", "type" => "CALC"],
    ];
}
