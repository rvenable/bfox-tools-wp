<?php

class BfoxToolsController extends BfoxRootPluginController {

	var $postTypeSlug = 'bible';

	function init() {
		parent::init();

		require_once $this->dir . '/bfox_tool_context.php';
		require_once $this->dir . '/bfox_bible_tool.php';
		require_once $this->dir . '/bfox_tools_ajax_div.php';

		require_once $this->core->refDir . '/bfox_bible_tool_link.php';
		require_once $this->core->refDir . '/bfox_bible_tool_api.php';
		require_once $this->dir . '/bible-tool-apis/bfox_local_wp_bible_tool_api.php';
		require_once $this->dir . '/bible-tool-apis/bfox_wp_bible_tool_iframe_api.php';

		require_once $this->dir . '/bfox_tool.php';
		require_once $this->apiDir . '/bfox_tool-functions.php';
		require_once $this->apiDir . '/bfox_tool-template.php';

		$this->contextStack = new BfoxStack();
	}

	function urlForToolName($shortName = '') {
		$url = get_post_type_archive_link('bfox_tool');
		if (!empty($shortName)) $url = add_query_arg('tool', $shortName, $url);
		return $url;
	}

	function urlForRefStr($refStr) {
		return add_query_arg('ref', urlencode(strtolower($refStr)), $this->urlForToolName());
	}

	function addToolApi(BfoxBibleToolApi $api, $shortName, $longName = '') {
		$context = $this->mainContext();
		$context->addTool(new BfoxBibleTool($api, $shortName, $longName));
	}

	function hasTools() {
		$context = $this->mainContext();
		return $context->hasTools();
	}

	function postTypeQuery($args = array()) {
		$args['post_type'] = 'bfox_tool';
		return new WP_Query($args);
	}

	/**
	 * Loops through Bible Tool links and adds a Bible Tool for each (loaded in Iframe)
	 */
	function addToolsFromToolsPostType() {
		$query = $this->postTypeQuery();

		while ($query->have_posts()) {
			$query->the_post();

			$url = $this->postMeta('url', $post_id);
			$title = get_the_title();
			$post = &get_post($id);

			$this->addToolApi(new BfoxWPBibleToolIframeApi($url), $post->post_name, $title);
		}
	}

	function pushContext(BfoxToolContext $item) {
		$this->core->stackGroup->push('toolContext', $item);
	}

	/**
	 * @return BfoxToolContext
	 */
	function popContext() {
		return $this->core->stackGroup->pop('toolContext');
	}

	/**
	 * @return BfoxToolContext
	 */
	function currentContext() {
		return $this->core->stackGroup->current('toolContext');
	}

	function wpInit() {
		register_post_type('bfox_tool',
			array(
				'description' => __('Bible Tools', 'bfox'),
				'labels' => array(
					'name' => __('Bible Tools', 'bfox'),
					'singular_name' => __('Bible Tool', 'bfox'),
					'edit_item' => __('Edit Bible Tool', 'bfox'),
					'new_item' => __('New Bible Tool', 'bfox'),
					'view_item' => __('View Tool', 'bfox')
				),
				'public' => true,
				'has_archive' => true,
				'rewrite' => array('slug' => $this->postTypeSlug),
				'supports' => array('title', 'excerpt', 'thumbnail'),
				'register_meta_box_cb' => $this->functionWithName('registerMetaBox'),
			)
		);

		$this->loadTemplate('config-bfox_tool');

		// Scripts

		/*
		* Javascript for changing the bible tool via ajax.
		* Doesn't work for Bible APIs loaded via javascript because we can't inject javascript via javascript
		* TODO: figure out why our Script element injection isn't working
		*/
		wp_enqueue_script('bfox_tool', $this->url . '/bfox_tool.js', array('bfox_ajax', 'jquery'), $this->version);
	}

	function wpAdminInit() {
		$this->registerAjaxDiv('edit-post', 'edit-post', 'edit-post');

		// Add a meta box for reading Bible passages to each post type that is indexed
		$this->addEditPostMetaBoxForAllIndexedPostTypes();
	}

	function registerMetaBox() {
		add_meta_box('bfox-tool-link', __('External Link', 'bfox'), $this->functionWithName('linkMetaBox'), 'bfox_tool', 'normal', 'high');
	}

	function linkMetaBox() {
		?>
		<p><?php _e('Link to any Bible tool on the internet. You can even link to tools for specific resources.', 'bfox') ?></p>

		<p><label for="bfox-tool-url"><?php _e( 'Link URL', 'bfox' ) ?></label>
		<input type="text" name="bfox-tool-url" id="bfox-tool-url" value="<?php echo bfox_tool_meta('url') ?>" /></p>

		<?php
	}

	private $contexts = array();
	var $mainContextName = 'main';

	/**
	 * @param string $name
	 * @return BfoxToolContext
	 */
	function contextForName($name) {
		if (empty($name)) return null;

		if (!isset($this->contexts[$name])) {
			$context = new BfoxToolContext($name, $this);

			if ($name != $this->mainContextName) {
				$context->addToolsFromContext($this->mainContext());
			}

			$this->contexts[$name] = $context;
		}
		return $this->contexts[$name];
	}

	/**
	 * @return BfoxToolContext
	 */
	function mainContext() {
		return $this->contextForName($this->mainContextName);
	}

	/**
	 * @return BfoxRefLinker
	 */
	function linkerForSelector($selector, $useTooltips = false) {
		$linker = $this->core->refs->basicLinker($useTooltips);
		$linker->addClass('bfox-ref-update');
		$linker->attributeValues['data-selector'] = $selector;

		return $linker;
	}

	function ajaxContent() {
		header('Content-Type: application/json');

		$context = $this->contextForName($_REQUEST['context']);
		if (is_null($context)) {
			echo json_encode(array('html' => 'Invalid Context'));
			exit;
		}

		if (!wp_verify_nonce($_REQUEST['nonce'], $context->nonceName)) {
			echo json_encode(array('html' => 'Context failed nonce verification'));
			exit;
		}

		$ref = new BfoxRef(urldecode($_REQUEST['ref']));
		$context->setRef($ref);
		$context->setActiveTool(urldecode($_REQUEST['tool']));

		if (!empty($_REQUEST['id'])) {
			// Bible links update the same id that was just updated
			$this->core->refs->pushLinker($this->linkerForSelector('#' . $_REQUEST['id']));
		}

		ob_start();
		$this->loadTemplate('content-bfox_tool');
		$html = ob_get_clean();
		$nonce = $context->nonce();

		$response = json_encode(array(
				'html' => $html,
				'dataUrl' => $context->ajaxUrl($nonce),
				'nonce' => $nonce,
		));

		echo $response;

		exit;
	}

	function postMeta($key, $post_id = 0) {
		if (empty($post_id)) $post_id = $GLOBALS['post']->ID;
		$value = get_post_meta($post_id, '_bfox_tool_' . $key, true);
		return $value;
	}

	function updatePostMeta($key, $value, $post_id = 0) {
		if (empty($post_id)) $post_id = $GLOBALS['post']->ID;
		return update_post_meta($post_id, '_bfox_tool_' . $key, $value);
	}

	function wp2SavePost($post_id, $post) {
		if (isset($_POST['post_type']) && 'bfox_tool' == $_POST['post_type']) {
			// See: http://codex.wordpress.org/Function_Reference/add_meta_box

			// verify this came from the our screen and with proper authorization,
			// because save_post can be triggered at other times
	//		if ( !wp_verify_nonce( $_POST['bfox_tool_edit_schedule_nonce'], 'bfox' )) {
	//			return $post_id;
	//		}

			// verify if this is an auto save routine. If it is our form has not been submitted, so we dont want
			// to do anything
			if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE )
				return $post_id;

			// Check permissions
			if ( 'page' == $_POST['post_type'] ) {
				if ( !current_user_can( 'edit_page', $post_id ) )
					return $post_id;
			} else {
				if ( !current_user_can( 'edit_post', $post_id ) )
					return $post_id;
			}

			$this->updatePostMeta('url', $_POST['bfox-tool-url'], $post_id);
			$this->updatePostMeta('local_db', $_POST['bfox-tool-localdb'], $post_id);
		}
	}

	function wpTemplateRedirect($template) {
		if ('bfox_tool' == get_query_var('post_type')) {
			if (is_singular('bfox_tool')) {
				$this->loadTemplate('single-bfox_tool');
				exit;
			}
			if (is_archive()) {
				$this->loadTemplate('archive-bfox_tool');
				exit;
			}
		}
	}

	function wpQueryVars($query_vars) {
		$query_vars []= 'ref';
		$query_vars []= 'tool';

		return $query_vars;
	}

	function wpParseRequest($wp) {
		if (isset($wp->query_vars['post_type']) && 'bfox_tool' == $wp->query_vars['post_type']) {
			$refContext = $this->core->refs->mainContext();

			if (isset($wp->query_vars['ref'])) {
				$refContext->setRef(new BfoxRef($wp->query_vars['ref']));
			}
			$wp->query_vars['ref'] = $refContext->ref->get_string();

			if (isset($wp->query_vars['tool'])) {
				$toolContext = $this->mainContext();
				$toolContext->setActiveTool($wp->query_vars['tool']);
			}
		}
	}

	function idForToolAjaxDiv($name) {
		return 'bfox-tools-ajax-div-' . $name;;
	}

	/**
	 * @return BfoxToolsAjaxDiv
	 */
	function ajaxDiv($name) {
		$id = $this->idForToolAjaxDiv($name);
		$div = $this->core->ajaxDiv($id);
		return $div;
	}

	function echoAjaxDiv($name) {
		$div = $this->ajaxDiv($name);

		if (!is_null($div)) {
			$div->echoInitialContent();
		}
	}

	function registerAjaxDiv($name, $toolContextName, $refContextName, $enableNoPrivilege = false) {
		$div = new BfoxToolsAjaxDiv($name, $this, $toolContextName, $refContextName);
		return $this->core->registerAjaxDiv($div, $enableNoPrivilege);
	}

	/**
	 * Add a Bible quick view meta box
	 *
	 * @param $postType
	 * @param $context
	 * @param $priority
	 */
	function addEditPostMetaBoxForPostType($postType, $context = 'normal', $priority = 'core') {
		add_meta_box('bfox-tools-edit-post-meta-box', __('Bible References', 'bfox'), $this->functionWithName('echoEditPostMetaBoxContent'), $postType, $context, $priority);
	}

	function addEditPostMetaBoxForAllIndexedPostTypes($context = 'normal', $priority = 'core') {
		$postTypes = $this->core->index->indexedPostTypes();
		foreach ($postTypes as $postType) {
			$this->addEditPostMetaBoxForPostType($postType, $context, $priority);
		}
	}

	/**
	 * Creates the form displaying the scripture quick view
	 *
	 */
	function echoEditPostMetaBoxContent() {
		global $post;

		$div = $this->ajaxDiv('edit-post');

		$div->setContextNames('main', 'main');
		$div->loadDynamically(); // Don't load the content until the page is loaded, then load it via AJAX

		$ref = $this->core->index->refForPost($post);
		$refContext = $div->refContext();

		$this->core->refs->pushContext($refContext);

		?>
		<p>
		<?php if ($ref->is_valid()): ?>
			<?php _e('This post is currently referencing:', 'bfox'); ?> <?php echo $this->core->refs->links($ref) ?><br/>
		<?php else: ?>
			<?php _e('This post currently has no Bible references.', 'bfox'); ?>
		<?php endif ?>
			<?php _e('Add more bible references by typing them into the post, or adding them to the post tags.', 'bfox'); ?>
		</p>
		<?php

		$this->core->refs->popContext();

		$div->echoInitialContent();
	}
}

?>