<?php

function active_bfox_tool() {
	$bfoxTools = BfoxBibleToolController::sharedInstance();
	return $bfoxTools->activeTool();
}

function has_bfox_tool() {
	return (!is_null(active_bfox_tool()));
}


/*
 * Reading Tool Meta Data functions
 */

/*
Bible Tool Source URL handling
*/

function bfox_tool_source_linker(BfoxRef $ref = null) {
	global $_bfox_tool_source_linker;
	if (is_null($_bfox_tool_source_linker)) {
		$_bfox_tool_source_linker = new BfoxBibleToolLink();
		if (is_null($ref)) $ref = bfox_ref();
	}

	if (!is_null($ref)) $_bfox_tool_source_linker->setRef($ref);
	return $_bfox_tool_source_linker;
}

function bfox_tool_source_url($post_id = 0, BfoxRef $ref = null) {
	if (empty($post_id)) $post_id = $GLOBALS['post']->ID;
	$template = bfox_tool_meta('url', $post_id);

	if (empty($template)) {
		if (is_null($ref)) $ref = bfox_ref();
		return add_query_arg('src', true, bfox_ref_url($ref->get_string(), $post_id));
	}

	$linker = bfox_tool_source_linker($ref);
	return $linker->urlForTemplate($template);
}

function is_bfox_tool_link() {
	$url = bfox_tool_meta('url');
	return !empty($url);
}

/*
Bible Tool Queries
*/

function selected_bfox_tool_post_id() {
	global $_selected_bfox_tool_post_id;

	if (!$_selected_bfox_tool_post_id) {
		// First try to get the selected BfoxTool from the cookies
		$post_id = $_COOKIE['selected_bfox_tool'];

		// Make sure that the cookied post id is actually a BfoxTool
		if ($post_id) {
			$post = &get_post($post_id);
			if ('bfox_tool' != $post->post_type) $post_id = 0;
		}

		// If we didn't get a BfoxTool from the cookies, just get the first one from a query
		if (!$post_id) {
			$tools = bfox_tool_query();
			if ($tools->have_posts()) {
				$post = $tools->next_post();
				$post_id = $post->ID;
			}
		}

		$_selected_bfox_tool_post_id = $post_id;
	}

	return $_selected_bfox_tool_post_id;
}

function the_selected_bfox_tool_post() {
	global $post;
	$post = get_post(selected_bfox_tool_post_id());
	setup_postdata($post);
}

?>