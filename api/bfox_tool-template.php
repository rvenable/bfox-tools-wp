<?php

/*
Template Tags
*/

function bfox_tool_context_nonce($context) {
	$nonce = wp_create_nonce('bfox-tool-context-' . $context);
	return $nonce;
}

function bfox_tool_context_ajax_url($context, $nonce = '') {
	if (empty($nonce)) $nonce = bfox_tool_context_nonce($context);
	$url = add_query_arg(array('action' => 'bfox-tool-content', 'context' => $context, 'nonce' => $nonce), admin_url('admin-ajax.php'));
	return $url;
}

function bfox_tool_select_options($options = array()) {
	$bfoxTools = BfoxBibleToolController::sharedInstance();
	return $bfoxTools->selectOptions($options);
}

?>