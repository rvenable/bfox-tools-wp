<?php

/*
Template Tags
*/

function bfox_tool_select_options($options = array()) {
	$bfoxTools = BfoxBibleToolController::sharedInstance();
	return $bfoxTools->selectOptions($options);
}

?>