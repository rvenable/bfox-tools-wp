<?php

class BfoxToolContext extends BfoxObject {
	var $name;
	var $dependencyName;

	/**
	 * @var BfoxToolsController
	 */
	var $toolsController;

	private $_activeShortName = '';

	function __construct($name, BfoxToolsController $toolsController) {
		$this->name = $name;
		$this->toolsController = $toolsController;
		$this->dependencyName = 'depends-bfox-tool-context-' . $this->name;

		parent::__construct();
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

	function lastViewedToolName() {
		return $this->toolsController->options->userOptionOrCookie('lastViewedToolName-' . $this->name);
	}

	function setLastViewedToolName($toolName) {
		return $this->toolsController->options->setUserOptionOrCookie('lastViewedToolName-' . $this->name, $toolName);
	}
}

?>