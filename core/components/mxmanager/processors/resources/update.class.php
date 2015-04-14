<?php

require MODX_CORE_PATH . 'model/modx/processors/resource/update.class.php';

class mxResourceUpdateProcessor extends modResourceUpdateProcessor {
	/** @var modResourceUpdateProcessor $processor */
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
		if (get_class($instance) != 'mxResourceUpdateProcessor') {
			$properties['processor'] = $instance;
		}

		return new mxResourceUpdateProcessor($modx, $properties);
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
		}
		else {
			parent::cleanup();
			$id = $this->object->get('id');
		}

		$get = require 'get.class.php';
		/** @var mxResourceGetProcessor $processor */
		$processor = new $get($this->modx, array('id' => $id));
		$processor->initialize();

		return $processor->process();
	}

}

return 'mxResourceUpdateProcessor';