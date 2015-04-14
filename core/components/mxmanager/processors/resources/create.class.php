<?php

require MODX_CORE_PATH . 'model/modx/processors/resource/create.class.php';

class mxResourceCreateProcessor extends modResourceCreateProcessor {
	/** @var modResourceCreateProcessor $processor */
	protected $_processor;


	/**
	 * @param modX $modx
	 * @param string $className
	 * @param array $properties
	 *
	 * @return modProcessor
	 */
	public static function getInstance(modX &$modx, $className, $properties = array()) {
		$instance = parent::getInstance($modx, $className, $properties);
		if (get_class($instance) != 'mxResourceCreateProcessor') {
			$properties['processor'] = $instance;
		}

		return new mxResourceCreateProcessor($modx, $properties);
	}


	/**
	 * @param modX $modx
	 * @param array $properties
	 */
	function __construct(modX & $modx, array $properties = array()) {
		$processor = null;
		if (!empty($properties['processor'])) {
			$processor = $properties['processor'];
			/** @var modResourceUpdateProcessor $processor */
			$processor->initialize();
			unset($properties['processor']);
		}

		parent::__construct($modx, $properties);
		$this->_processor = $processor;
	}


	/**
	 * @return array|string
	 */
	public function process() {
		if (!empty($this->_processor)) {
			$this->beforeSet();
			$this->_processor->setProperties($this->getProperties());
			$this->_processor->process();
			return $this->_processor->hasErrors()
				? $this->failure()
				: $this->cleanup();
		}
		else {
			return parent::process();
		}
	}


	/**
	 * @return array|string
	 */
	public function beforeSet() {
		foreach (array('description', 'introtext', 'content') as $key) {
			if ($field = $this->getProperty($key, false)) {
				$field = base64_decode($field);
				$this->setProperty($key, $field);
			}
		}
		foreach (array('createdon', 'publishedon', 'pub_date', 'unpub_date') as $key) {
			if ($field = $this->getProperty($key, false)) {
				$this->setProperty($key, date('Y-m-d H:i:s', strtotime($field)));
			}
		}

		return parent::beforeSet();
	}


	/**
	 * @return array|string
	 */
	public function cleanup() {
		if (!empty($this->_processor)) {
			$this->_processor->cleanup();
			$id = $this->_processor->object->get('id');
			$context_key = $this->_processor->object->get('context_key');
		}
		else {
			parent::cleanup();
			$id = $this->object->get('id');
			$context_key = $this->object->get('context_key');
		}

		/** @var modContext $context */
		if ($this->getProperty('syncsite') && $context = $this->modx->getObject('modContext', array('key' => $context_key))) {
			$context->prepare(true);

			$sessionEnabled = '';
			if (isset($context->config['session_enabled'])) {
				$sessionEnabled = $context->config['session_enabled'] == 0
					? array('preview' => 'true')
					: '';
			}
			$previewUrl = $context->makeUrl($id, $sessionEnabled, 'full');
		}
		else {
			$previewUrl = '';
		}


		$get = require 'get.class.php';
		/** @var mxResourceGetProcessor $processor */
		$processor = new $get($this->modx, array('id' => $id, 'previewUrl' => $previewUrl));
		$processor->initialize();

		return $processor->process();
	}

}

return 'mxResourceCreateProcessor';