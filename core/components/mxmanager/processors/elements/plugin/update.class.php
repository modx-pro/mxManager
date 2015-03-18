<?php

require MODX_CORE_PATH . 'model/modx/processors/element/plugin/update.class.php';

class mxPluginUpdateProcessor extends modPluginUpdateProcessor {

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
		$c = $this->modx->newQuery('modPluginEvent', array('pluginid' => $this->object->get('id')));
		if (!empty($tmp)) {
			$c->where(array('event:NOT IN' => $tmp));
		}
		$c->select('event');
		if ($c->prepare() && $c->stmt->execute()) {
			while ($event = $c->stmt->fetchColumn()) {
				$events[$event] = array(
					'name' => $event,
					'enabled' => false
				);
			}
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

return 'mxPluginUpdateProcessor';