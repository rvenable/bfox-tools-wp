<?php

/*
Theme Templates
*/

function update_selected_bfox_tool() {
	if (is_singular('bfox_tool')) {
		global $post, $_selected_bfox_tool_post_id;
		$_selected_bfox_tool_post_id = $post->ID;
		$_COOKIE['selected_bfox_tool'] = $post->ID;
		setcookie('selected_bfox_tool', $_COOKIE['selected_bfox_tool'], /* 30 days from now: */ time() + 60 * 60 * 24 * 30, '/');
	}
}
add_action('wp', 'update_selected_bfox_tool');


function bfox_tool_shortcode($atts) {
	// [bible-tool tool="esv" ref="Matthew 1"]

	extract( shortcode_atts( array(
		'tool' => '',
		'ref' => '',
	), $atts ) );

	$bfoxTools = BfoxBibleToolController::sharedInstance();
	$tool = $bfoxTools->toolForShortName($tool);
	if (is_null($tool)) return;

	$ref = new BfoxRef($ref);
	if (!$ref->is_valid()) return;

	ob_start();
	$tool->echoContentForRef($ref);
	$content = ob_get_clean();

	return $content;
}
add_shortcode('bible-tool', 'bfox_tool_shortcode');

?>