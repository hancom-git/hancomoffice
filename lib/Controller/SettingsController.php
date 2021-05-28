<?php
/**
 *
 * (c) Copyright Hancom Inc
 *
 */

namespace OCA\HancomOffice\Controller;

use OCP\App;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\IRequest;
use OCP\ILogger;

use OCA\HancomOffice\AppConfig;

/**
 * Settings controller for the administration page
 */
class SettingsController extends Controller {

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
     * @param string $AppName - application name
     * @param ILogger $logger - logger
     * @param AppConfig $config - application configuration
     */
    public function __construct($AppName,
                                    IRequest $request,
                                    ILogger $logger,
                                    AppConfig $config
                                    ) {
        parent::__construct($AppName, $request);

        $this->logger = $logger;
        $this->config = $config;
    }

    /**
     * Print config section
     *
     * @return TemplateResponse
     */
    public function index() {
        $data = [
            "documentserver" => $this->config->GetDocumentServerUrl(true),
            "demoserver" => $this->config->GetDemoServerUrl(),
            "docsconverter" => $this->config->GetDocsConverterUrl(),
            "formats" => $this->config->FormatsSetting(),
            "apps" => $this->config->AppsSetting(),
            "hosts" => $this->config->getHosts(),
            "type" => $this->config->GetServerType(),
        ];
        return new TemplateResponse($this->appName, "settings", $data, "blank");
    }

    /**
     * Save address settings
     *
     * @param string $documentserver - document service address
     *
     * @return array
     */
    public function SaveAddress($type, $documentserver, $demoserver, $docsconverter) {
        $error = null;
        $this->config->SetServerType($type);
        if (isset($documentserver)) {
            $this->config->SetDocumentServerUrl($documentserver);
        }
        if (isset($demoserver)) {
            $this->config->SetDemoServerUrl($demoserver);
        }
        if (isset($docsconverter)) {
            $this->config->SetDocsConverterUrl($docsconverter);
        }

        return [
            "type" => $this->config->GetServerType(),
            "documentserver" => $this->config->GetDocumentServerUrl(true),
            "demoserver" => $this->config->GetDemoServerUrl(),
            "docsconverter" => $this->config->GetDocsConverterUrl(),
            "error" => $error
        ];
    }

    /**
     * Get settings
     *
     * @return array
     *
     * @NoAdminRequired
     * @PublicPage
     */
    public function GetSettings() {
        $result = [
            "formats" => $this->config->FormatsSetting(),
            "apps" => $this->config->AppsSetting(),
            "webofficeHost" => $this->config->GetHost(),
            "docsconverterHost" => $this->config->GetDocsConverterUrl(),
            "instanceid" => $this->config->getSystemValue('instanceid'),
        ];
        return $result;
    }
}
