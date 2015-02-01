<?php

/**
 * The home manager controller for mxManager.
 *
 */
class mxManagerHomeManagerController extends mxManagerMainController {
	/* @var mxManager $mxManager */
	public $mxManager;


	/**
	 * @param array $scriptProperties
	 */
	public function process(array $scriptProperties = array()) {
	}


	/**
	 * @return null|string
	 */
	public function getPageTitle() {
		return $this->modx->lexicon('mxmanager');
	}


	/**
	 * @return void
	 */
	public function loadCustomCssJs() {
		$this->addCss($this->mxManager->config['cssUrl'] . 'mgr/main.css');
		$this->addCss($this->mxManager->config['cssUrl'] . 'mgr/bootstrap.buttons.css');
		$this->addJavascript($this->mxManager->config['jsUrl'] . 'mgr/misc/utils.js');
		$this->addJavascript($this->mxManager->config['jsUrl'] . 'mgr/widgets/items.grid.js');
		$this->addJavascript($this->mxManager->config['jsUrl'] . 'mgr/widgets/items.windows.js');
		$this->addJavascript($this->mxManager->config['jsUrl'] . 'mgr/widgets/home.panel.js');
		$this->addJavascript($this->mxManager->config['jsUrl'] . 'mgr/sections/home.js');
		$this->addHtml('<script type="text/javascript">
		Ext.onReady(function() {
			MODx.load({ xtype: "mxmanager-page-home"});
		});
		</script>');
	}


	/**
	 * @return string
	 */
	public function getTemplateFile() {
		return $this->mxManager->config['templatesPath'] . 'home.tpl';
	}
}