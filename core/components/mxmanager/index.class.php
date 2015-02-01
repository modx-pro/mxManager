<?php

/**
 * Class mxManagerMainController
 */
abstract class mxManagerMainController extends modExtraManagerController {
	/** @var mxManager $mxManager */
	public $mxManager;


	/**
	 * @return void
	 */
	public function initialize() {
		$corePath = $this->modx->getOption('mxmanager_core_path', null, $this->modx->getOption('core_path') . 'components/mxmanager/');
		require_once $corePath . 'model/mxmanager/mxmanager.class.php';

		$this->mxManager = new mxManager($this->modx);
		$this->addCss($this->mxManager->config['cssUrl'] . 'mgr/main.css');
		$this->addJavascript($this->mxManager->config['jsUrl'] . 'mgr/mxmanager.js');
		$this->addHtml('
		<script type="text/javascript">
			mxManager.config = ' . $this->modx->toJSON($this->mxManager->config) . ';
			mxManager.config.connector_url = "' . $this->mxManager->config['connectorUrl'] . '";
		</script>
		');

		parent::initialize();
	}


	/**
	 * @return array
	 */
	public function getLanguageTopics() {
		return array('mxmanager:default');
	}


	/**
	 * @return bool
	 */
	public function checkPermissions() {
		return true;
	}
}


/**
 * Class IndexManagerController
 */
class IndexManagerController extends mxManagerMainController {

	/**
	 * @return string
	 */
	public static function getDefaultController() {
		return 'home';
	}
}