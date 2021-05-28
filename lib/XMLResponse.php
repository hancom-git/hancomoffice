<?php
/**
 *
 * (c) Copyright Hancom Inc
 *
 */

namespace OCA\HancomOffice;

use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Http\Response;

class XMLResponse extends Response {

	/** @var array */
	protected $data;

	public function __construct($data) {
		parent::__construct();

		$this->data = $data;

        $this->addHeader(
            'Content-Type', 'application/xml; charset=utf-8'
        );
	}

	/**
	 * @return string
	 */
	public function render(): string {
		$writer = new \XMLWriter();
		$writer->openMemory();
		$writer->setIndent(true);
		$writer->startDocument('1.0', 'utf-8');
		$this->toXML($this->data, $writer);
		$writer->endDocument();

		return $writer->outputMemory(true);
	}

	/**
	 * @param array $array
	 * @param \XMLWriter $writer
	 */
	protected function toXML(array $array, \XMLWriter $writer) {
		foreach ($array as $k => $v) {
			if (\is_string($k) && strpos($k, '@') === 0) {
				$writer->writeAttribute(substr($k, 1), $v);
				continue;
            }
            
            if (\is_string($k) && strpos($k, '$') === 0) {
                $writer->startElement(substr($k, 1));
                $writer->writeCdata($v);
				$writer->endElement();
				continue;
			}

			if (\is_numeric($k)) {
				$k = 'property';
			}

			if (\is_array($v)) {
				$writer->startElement($k);
				$this->toXML($v, $writer);
				$writer->endElement();
			} else {
				$writer->writeElement($k, $v);
			}
		}
	}
	
}
