<?php
/**
 *
 * (c) Copyright Hancom Inc
 *
 */

namespace OCA\HancomOffice\Settings;

use OCP\Settings\ISettings;

use OCA\HancomOffice\AppInfo\Application;
use OCA\HancomOffice\Controller\SettingsController;

class Admin implements ISettings {

    public function __construct() {
    }

    /**
     * Config section
     *
     * @return TemplateResponse
     */
    public function getForm() {
        $app = \OC::$server->query(Application::class);
        $container = $app->getContainer();
        $response = $container->query(SettingsController::class)->index();
        return $response;
    }

    /**
     * Get section ID
     *
     * @return string
     */
    public function getSection() {
        return "hancomoffice";
    }

    /**
     * Get priority order
     *
     * @return int
     */
    public function getPriority() {
        return 50;
    }
}
