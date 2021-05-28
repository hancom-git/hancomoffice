<?php
/**
 *
 * (c) Copyright Hancom Inc
 *
 */

namespace OCA\HancomOffice\AppInfo;

use OC\AppFramework\Utility\TimeFactory;

use OCP\AppFramework\App;
use OCP\AppFramework\Http\ContentSecurityPolicy;
use OCP\Util;
use OCP\Lock\ILockingProvider;
use OCP\IPreview;

use OCA\Viewer\Event\LoadViewer;

use OCA\HancomOffice\AppConfig;
use OCA\HancomOffice\Controller\CallbackController;
use OCA\HancomOffice\Controller\EditorController;
use OCA\HancomOffice\Controller\SettingsController;
use OCA\HancomOffice\Controller\PreviewController;
use OCA\HancomOffice\Service\Lock\FileLock;
use OCA\HancomOffice\Service\Lock\FileLockingProvider;
use OCA\HancomOffice\BackgroundJob\CleanFileLocks;
use OCA\HancomOffice\Preview\OOXML;
use OCA\HancomOffice\Preview\MSWord;
use OCA\HancomOffice\Preview\MSExcel;
use OCA\HancomOffice\Preview\MSPowerPoint;
use OCA\HancomOffice\Preview\Cell;
use OCA\HancomOffice\Preview\Show;
use OCA\HancomOffice\Preview\HWP;
use OCA\HancomOffice\Preview\OWPML;

use OCA\HancomOffice\Hooks\HooksStatic;

class Application extends App {

    /**
     * Application configuration
     *
     * @var AppConfig
     */
    public $appConfig;

    public function __construct(array $urlParams = []) {
        $appName = "hancomoffice";

        parent::__construct($appName, $urlParams);

        $this->appConfig = new AppConfig($appName);

        $this->bootstrap();
        $this->registerServices();
        $this->registerPreviewProviders();
        $this->registerHooks();
    }

    /**
     * Bootstrap with scripts and styles
     */
    private function bootstrap() {
        $eventDispatcher = \OC::$server->getEventDispatcher();
        $eventDispatcher->addListener("OCA\Files::loadAdditionalScripts", function() {
            if (!empty($this->appConfig->GetDocumentServerUrl())
                && $this->appConfig->isUserAllowedToUse()) {
                Util::addScript("hancomoffice", "main");
                Util::addStyle("hancomoffice", "main");
            }
        });

        if (class_exists(LoadViewer::class)) {
            $eventDispatcher->addListener(LoadViewer::class,
                function() {
                    if (!empty($this->appConfig->GetDocumentServerUrl())
                        && $this->appConfig->isUserAllowedToUse()) {
                        Util::addScript("hancomoffice", "main");
                        Util::addStyle("hancomoffice", "main");

                        $csp = new ContentSecurityPolicy();
                        $csp->addAllowedFrameDomain("'self'");
                        $cspManager = $this->getContainer()->getServer()->getContentSecurityPolicyManager();
                        $cspManager->addDefaultPolicy($csp);
                    }
                });
        }

        $eventDispatcher->addListener("OCA\Files_Sharing::loadAdditionalScripts", function() {
            if (!empty($this->appConfig->GetDocumentServerUrl())) {
                Util::addScript("hancomoffice", "main");
                Util::addStyle("hancomoffice", "main");
            }
        });
    }

    /**
     * Register services
     */
    private function registerServices() {
        $container = $this->getContainer();

        $container->registerService("L10N", function($c) {
            return $c->query("ServerContainer")->getL10N($c->query("AppName"));
        });

        $container->registerService("RootStorage", function($c) {
            return $c->query("ServerContainer")->getRootFolder();
        });

        $container->registerService("UserSession", function($c) {
            return $c->query("ServerContainer")->getUserSession();
        });

        $container->registerService("UserManager", function($c) {
            return $c->query("ServerContainer")->getUserManager();
        });

        $container->registerService("Logger", function($c) {
            return $c->query("ServerContainer")->getLogger();
        });

        $container->registerService("URLGenerator", function($c) {
            return $c->query("ServerContainer")->getURLGenerator();
        });

        $container->registerService(FileLockingProvider::class, function($c) {
            return new FileLockingProvider(
                $c->query("ServerContainer")->getDatabaseConnection(),
                $c->query('Logger'),
                new TimeFactory()
            );
        });

        $container->registerService(FileLock::class, function($c) {
            return new FileLock(
                $c->query(FileLockingProvider::class),
                new TimeFactory(),
                $c->query('Logger')
            );
        });
    }

    /**
     * Register hooks
     */
    private function registerHooks() {
        // hook to prevent rename file or folder when it's locked
        Util::connectHook('OC_Filesystem', 'rename', HooksStatic::class, 'rename');
    }

    /**
     * Register file preview providers
     */
    private function registerPreviewProviders() {
		$container = $this->getContainer();

		/** @var IPreview $previewManager */
        $previewManager = $container->query(IPreview::class);
        
        // .docx, .xlsx, .pptx
        $previewManager->registerProvider('/application\/vnd.openxmlformats-officedocument.*/', function() use ($container) {
            return $container->query(OOXML::class);
        });

        // .doc
		$previewManager->registerProvider('/application\/msword/', function() use ($container) {
			return $container->query(MSWord::class);
        });

        // .xls
        $previewManager->registerProvider('/application\/vnd.ms-excel/', function() use ($container) {
			return $container->query(MSExcel::class);
        });

        // .ppt
        $previewManager->registerProvider('/application\/vnd.ms-powerpoint/', function() use ($container) {
			return $container->query(MSPowerPoint::class);
        });

        // show
        $previewManager->registerProvider('/application\/haansoftshow/', function() use ($container) {
			return $container->query(Show::class);
        });

        // cell
        $previewManager->registerProvider('/application\/haansoftcell/', function() use ($container) {
			return $container->query(Cell::class);
        });

        // hwp
        $previewManager->registerProvider('/application\/haansofthwp.*/', function() use ($container) {
			return $container->query(HWP::class);
        });

        // owpml
        $previewManager->registerProvider('/application\/haansoftowpml/', function() use ($container) {
			return $container->query(OWPML::class);
        });
    }
}
