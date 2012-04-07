<?php

class BfoxToolsAjaxDiv extends BfoxAjaxDiv {

	/**
	 * @var BfoxCoreController
	 */
	var $core;

	private $toolContextName;
	private $refContextName;

	var $template;

	function __construct($name, BfoxToolsController $tools, $toolContextName, $refContextName) {
		$this->core = $tools->core;

		parent::__construct($name);

		$this->toolContextName = $toolContextName;
		$this->refContextName = $refContextName;
		$this->template = 'bfox-tool-content-' . $name;
	}

	function init() {
		$this->id = $this->core->tools->idForToolAjaxDiv($this->name);
		parent::init();
	}

	function setContextNames($toolContextName, $refContextName) {
		$this->toolContextName = $toolContextName;
		$this->refContextName = $refContextName;
	}

	/**
	 * @return BfoxToolContext
	 */
	function toolContext() {
		return $this->core->tools->contextForName($this->toolContextName);
	}

	/**
	 * @return BfoxRefContext
	 */
	function refContext() {
		return $this->core->refs->contextForName($this->refContextName);
	}

	function echoContent() {
		global $bfox;

		$bfox->refs->pushContext($this->refContext());
		$bfox->tools->pushContext($this->toolContext());

		require $this->core->tools->templatePath($this->template);
	}

	function echoInitialContent() {
		$refContext = $this->refContext();
		$this->addClass($refContext->dependencyName);

		$toolContext = $this->toolContext();
		$this->addClass($toolContext->dependencyName);

		parent::echoInitialContent();
	}

	function sendResponse() {
		if (isset($_REQUEST['ref'])) {
			$ref = new BfoxRef($_REQUEST['ref']);

			$refContext = $this->refContext();
			$refContext->setRef($ref);
		}

		if (isset($_REQUEST['tool'])) {
			$toolContext = $this->toolContext();
			$toolContext->setActiveTool($_REQUEST['tool']);
		}

		parent::sendResponse();
	}
}

?>