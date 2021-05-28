<?php
/**
 *
 * (c) Copyright Hancom Inc
 *
 */

namespace OCA\HancomOffice\Preview;

class OOXML extends Office {
	/**
	 * {@inheritDoc}
	 */
	public function getMimeType() {
		return '/application\/vnd.openxmlformats-officedocument.*/';
	}
}
