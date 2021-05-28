<?php
/**
 *
 * (c) Copyright Hancom Inc
 *
 */

namespace OCA\HancomOffice\Hooks;

use OCA\HancomOffice\Hooks\Hooks;

class HooksStatic {
    /**
	 * @return Hooks
	 */
	static protected function getHooks() {
		return \OC::$server->query(Hooks::class);
	}

    /**
     * Invoke rename/move check and prevent it
     * 
     * @param array $arguments - hook arguments
     */
    static public function rename($arguments) {
        $result = self::getHooks()->rename($arguments['oldpath'], $arguments['newpath']);
        if ($result === false) {
            $arguments['run'] = false;
        }
    }
}
