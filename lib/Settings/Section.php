<?php
/**
 *
 * (c) Copyright Hancom Inc
 *
 */

namespace OCA\HancomOffice\Settings;

use OCP\IURLGenerator;
use OCP\Settings\IIconSection;

/**
 * Settings section for the administration page
 */
class Section implements IIconSection {

    /** @var IURLGenerator */
    private $urlGenerator;

    /**
     * @param IURLGenerator $urlGenerator - url generator service
     */
    public function __construct(IURLGenerator $urlGenerator) {
        $this->urlGenerator = $urlGenerator;
    }

    /**
     * Path to an 16*16 icons
     *
     * @return strings
     */
    public function getIcon() {
        return $this->urlGenerator->imagePath("hancomoffice", "app-dark.svg");
    }

    /**
     * ID of the section
     *
     * @returns string
     */
    public function getID() {
        return "hancomoffice";
    }

    /**
     * Name of the section
     *
     * @return string
     */
    public function getName() {
        return "Hancom Office Online";
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
