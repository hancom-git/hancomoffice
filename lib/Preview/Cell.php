<?php
/**
 *
 * (c) Copyright Hancom Inc
 *
 */

namespace OCA\HancomOffice\Preview;

class Cell extends Office {
	/**
	 * {@inheritDoc}
	 */
	public function getMimeType() {
		return '/application\/haansoftcell/';
	}
}
