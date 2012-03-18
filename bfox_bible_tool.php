<?php

class BfoxBibleTool {
	/**
	 * @var BfoxBibleToolApi
	 */
	var $api;
	var $shortName;
	var $longName;

	function __construct(BfoxBibleToolApi $api, $shortName = '', $longName = '') {
		$this->api = $api;
		if (empty($shortName)) $shortName = $api->bible;
		if (empty($longName)) $longName = $shortName;
		$this->shortName = $shortName;
		$this->longName = $longName;
	}

	function echoContentForRef(BfoxRef $ref) {
		$this->api->echoContentForRef($ref);
	}
}

?>