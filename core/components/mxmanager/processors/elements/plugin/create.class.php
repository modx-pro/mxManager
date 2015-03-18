<?php

require MODX_CORE_PATH . 'model/modx/processors/element/plugin/create.class.php';

class mxPluginCreateProcessor extends modPluginCreateProcessor {

	public function beforeSet() {
		$content = $this->getProperty('content', false);
		if ($content !== false) {
			$this->setProperty('content', base64_decode($content));
		}

		$events = array();
		$tmp = $this->getProperty('events', array());
		foreach ($tmp as $event) {
			$events[$event] = array(
				'name' => $event,
				'enabled' => true
			);
		}
		$this->setProperty('events', $events);

		return parent::beforeSet();
	}


	public function cleanup() {
		$name = require 'get.class.php';
		/** @var modObjectGetProcessor $processor */
		$processor = new $name($this->modx, array(
			'id' => $this->object->get('id')
		));
		$processor->initialize();

		return $processor->process();
	}

}

return 'mxPluginCreateProcessor';