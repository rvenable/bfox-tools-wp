<?php

class BfoxToolContext extends BfoxObject {
	var $name;
	var $nonceName;

	/**
	 * @var BfoxRef
	 */
	var $ref;

	/**
	 * @var BfoxToolsController
	 */
	var $toolsController;

	private $_activeShortName = '';

	function __construct($name, BfoxToolsController $toolsController, $initRefStr = 'Genesis 1') {
		$this->name = $name;
		$this->toolsController = $toolsController;
		$this->nonceName = 'bfox-tool-context-' . $this->name;

		parent::__construct();

		$this->initRef($initRefStr);
	}

	protected function initRef($initRefStr) {
		$ref = new BfoxRef($this->lastViewedRefStr());
		if (!$ref->is_valid()) {
			$ref = new BfoxRef($initRefStr);
			if ($ref->is_valid()) $this->setLastViewedRefStr($ref->get_string());
		}

		$this->ref = $ref;
	}

	function setRef(BfoxRef $ref) {
		if ($ref->is_valid()) {
			$this->ref = $ref;
			$this->setLastViewedRefStr($ref->get_string());
		}
	}

	protected $tools = array();

	function hasTools() {
		return !empty($this->tools);
	}

	function addTool(BfoxBibleTool $tool) {
		$this->tools[$tool->shortName] = $tool;
		if (empty($this->_activeShortName)) $this->_activeShortName = $tool->shortName;
	}

	function allTools() {
		return $this->tools;
	}

	function addToolsFromContext(BfoxToolContext $context) {
		foreach ($context->allTools() as $tool) {
			$this->addTool($tool);
		}
	}

	function finishedAddingTools() {
		$this->setActiveTool($this->lastViewedToolName());
	}

	/**
	 * @param string $shortName
	 * @return BfoxBibleTool
	 */
	function toolForShortName($shortName = '') {
		if (empty($shortName)) $shortName = $this->_activeShortName;
		if (isset($this->tools[$shortName])) return $this->tools[$shortName];
		return null;
	}

	function setActiveTool($shortName) {
		$tool = $this->toolForShortName($shortName);
		if (!is_null($tool)) $this->_activeShortName = $tool->shortName;
	}

	function activeTool() {
		return $this->toolForShortName($this->_activeShortName);
	}

	function selectOptions($options = array()) {
		extract($options);

		$content = '';
		$activeTool = $this->activeTool();
		foreach ($this->tools as $tool) {
			if ($tool == $activeTool) $selected = " selected='selected'";
			else $selected = '';

			$content .= "<option name='$tool->shortName' value='$tool->shortName'$selected>$tool->longName</option>";
		}

		return $content;
	}

	function lastViewedRefStr() {
		return $this->toolsController->options->userOptionOrCookie('lastViewedRefStr');
	}

	function setLastViewedRefStr($refStr) {
		return $this->toolsController->options->setUserOptionOrCookie('lastViewedRefStr', $refStr);
	}

	function lastViewedToolName() {
		return $this->toolsController->options->userOptionOrCookie('lastViewedToolName');
	}

	function setLastViewedToolName($toolName) {
		return $this->toolsController->options->setUserOptionOrCookie('lastViewedToolName', $toolName);
	}

	function nonce() {
		$nonce = wp_create_nonce($this->nonceName);
		return $nonce;
	}

	function ajaxUrl($nonce = '') {
		if (empty($nonce)) $nonce = $this->nonce();
		$url = add_query_arg(array('action' => 'bfox-tool-content', 'context' => $this->name, 'nonce' => $nonce), admin_url('admin-ajax.php'));
		return $url;
	}

}

?>