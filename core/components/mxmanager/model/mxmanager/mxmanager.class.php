<?php

/**
 * The base class for mxManager.
 */
class mxManager {
	/* @var modX $modx */
	public $modx;
	protected $_version = "1.0.1-pl";


	/**
	 * @param modX $modx
	 * @param array $config
	 */
	function __construct(modX &$modx, array $config = array()) {
		$this->modx =& $modx;

		$corePath = $this->modx->getOption('mxmanager_core_path', $config, $this->modx->getOption('core_path') . 'components/mxmanager/');
		//$assetsUrl = $this->modx->getOption('mxmanager_assets_url', $config, $this->modx->getOption('assets_url') . 'components/mxmanager/');
		//$connectorUrl = $assetsUrl . 'connector.php';

		$this->config = array_merge(array(
			/*
			'assetsUrl' => $assetsUrl,
			'cssUrl' => $assetsUrl . 'css/',
			'jsUrl' => $assetsUrl . 'js/',
			'imagesUrl' => $assetsUrl . 'images/',
			'connectorUrl' => $connectorUrl,
			*/

			'corePath' => $corePath,
			'modelPath' => $corePath . 'model/',
			'chunksPath' => $corePath . 'elements/chunks/',
			'templatesPath' => $corePath . 'elements/templates/',
			'chunkSuffix' => '.chunk.tpl',
			'snippetsPath' => $corePath . 'elements/snippets/',
			'processorsPath' => $corePath . 'processors/'
		), $config);

		//$this->modx->addPackage('mxmanager', $this->config['modelPath']);
		$this->modx->lexicon->load('mxmanager:default');
	}


	/**
	 * @param array $data
	 *
	 * @return array
	 */
	public function handleRequest(array $data) {
		$version = $this->modx->stripTags($_REQUEST['mx_version']);
		if (!empty($version) && !version_compare($this->_version, $version, '>=')) {
			return $this->failure('mxmanager_err_version', array(), array('version' => $version));
		}

		//$this->modx->log(modX::LOG_LEVEL_ERROR, print_r($data, true));
		//$this->modx->log(modX::LOG_LEVEL_ERROR, http_build_query($data));

		$action = $this->modx->stripTags($_REQUEST['mx_action']);
		if ($action == 'auth') {
			$response = $this->getResponse($this->runProcessor('main/auth', $data));
		}
		elseif (!$this->modx->user->isAuthenticated('mgr')) {
			$response = $this->failure('mxmanager_err_access_denied');
		}
		elseif (!$response = $this->getResponse($this->runProcessor($action, $data))) {
			$response = $this->failure('mxmanager_err_unknown_action');
		}

		return $response;
	}


	/**
	 * @return array
	 */
	public function getUserPermissions() {
		return array(
			'save' => $this->modx->hasPermission('save_document'),
			'view' => $this->modx->hasPermission('view_document'),
			'edit' => $this->modx->hasPermission('edit_document'),
			'delete' => $this->modx->hasPermission('delete_document'),
			'undelete' => $this->modx->hasPermission('undelete_document'),
			'publish' => $this->modx->hasPermission('publish_document'),
			'unpublish' => $this->modx->hasPermission('unpublish_document'),
			'duplicate' => $this->modx->hasPermission('resource_duplicate'),

			'new_document' => $this->modx->hasPermission('new_document'),
			'new_weblink' => $this->modx->hasPermission('new_weblink'),
			'new_symlink' => $this->modx->hasPermission('new_symlink'),
			'new_static_resource' => $this->modx->hasPermission('new_static_resource'),
		);
	}


	/**
	 * @return array
	 */
	public function getElementCategories() {
		$categories = array();
		if (!class_exists('modElementCategoryGetListProcessor')) {
			require MODX_CORE_PATH . 'model/modx/processors/element/category/getlist.class.php';
		}
		$processor = new modElementCategoryGetListProcessor($this->modx, array(
			'limit' => 0,
			'query' => '',
			'showNone' => true,
		));
		$processor->initialize();
		$beforeQuery = $processor->beforeQuery();
		if ($beforeQuery === true) {
			$data = $processor->getData();
			$tmp = $processor->iterate($data);
			foreach ($tmp as $item) {
				$categories[] = array(
					'id' => (int)$item['id'],
					'name' => $item['name']
				);
			}
		}

		return $categories;
	}


	/**
	 * @param string $class
	 * @param array $permissions
	 *
	 * @return array
	 */
	public function getSubClasses($class = '', array $permissions = array()) {
		$classes = array();

		switch ($class) {
			case 'TicketsSection':
				$classes = array(
					'Ticket'
				);
				break;
			case 'Ticket':
				break;
			case 'msCategory':
				$classes = array(
					'msCategory',
					'msProduct',
				);
				break;
			case 'msProduct':
				break;
			case 'ArticlesContainer':
				$classes = array(
					'Article'
				);
				break;
			case 'Article':
				break;
			default:
				if (empty($permissions) || !empty($permissions['new_document'])) {
					$classes[] = 'modDocument';
				}
				if (empty($permissions) || !empty($permissions['new_weblink'])) {
					$classes[] = 'modWebLink';
				}
				if (empty($permissions) || !empty($permissions['new_symlink'])) {
					$classes[] = 'modSymLink';
				}
				if (empty($permissions) || !empty($permissions['new_static_resource'])) {
					$classes[] = 'modStaticResource';
				}
				if (!empty($this->modx->classMap['modResource']) && in_array('TicketsSection', $this->modx->classMap['modResource'])) {
					$classes[] = 'TicketsSection';
				}
				if (!empty($this->modx->classMap['modResource']) && in_array('msCategory', $this->modx->classMap['modResource'])) {
					$classes[] = 'msCategory';
				}
				if (!empty($this->modx->classMap['modResource']) && in_array('ArticlesContainer', $this->modx->classMap['modResource'])) {
					$classes[] = 'ArticlesContainer';
				}
		}

		return $classes;
	}


	/**
	 * @param $name
	 * @param $data
	 *
	 * @return mixed
	 */
	protected function runProcessor($name, $data) {
		return $this->modx->runProcessor($name, $data, array(
			'processors_path' => $this->config['processorsPath']
		));
	}


	/**
	 * @param string $message
	 * @param array $data
	 * @param array $placeholders
	 *
	 * @return array
	 */
	protected function success($message = '', $data = array(), $placeholders = array()) {
		return array(
			'success' => true,
			'message' => $this->modx->lexicon($message, $placeholders),
			'data' => $data,
		);
	}


	/**
	 * @param string $message
	 * @param array $data
	 * @param array $placeholders
	 *
	 * @return array
	 */
	protected function failure($message = '', $data = array(), $placeholders = array()) {
		return array(
			'success' => false,
			'message' => $this->modx->lexicon($message, $placeholders),
			'data' => $data,
		);
	}

	/**
	 * @param $response
	 *
	 * @return array|bool
	 */
	protected function getResponse($response) {
		if (!($response instanceof modProcessorResponse)) {
			return false;
		}
		elseif ($response->isError()) {
			$message = $response->getMessage();
			$all = $response->getAllErrors();
			if (!empty($all[0]) && $all[0] == $message) {
				unset($all[0]);
				sort($all);
			}
			elseif (!empty($all[0]) && empty($message)) {
				$message = implode("\n", $all);
			}
			return $this->failure($message, $all);

		}

		$res = $response->getResponse();
		if (is_string($res) && $res[0]) {
			$res = $this->modx->fromJSON($res);
			// Response from GetList processors
			if (is_array($res) && isset($res['results'])) {
				$data = array(
					'total' => (int)$res['total'],
					'count' => count($res['results']),
					'rows' => !empty($res['results'])
						? $res['results']
						: array(),
				);
				unset($res['total'], $res['results'], $res['success']);
				if (count($res) > 0) {
					foreach ($res as $key => $value) {
						$data[$key] = $value;
					}
				}
				return $this->success($response->getMessage(), $data);
			}
			else {
				return $this->failure('mxmanager_err_wrong_response');
			}
		}

		return $this->success($response->getMessage(), $response->getObject());
	}
}