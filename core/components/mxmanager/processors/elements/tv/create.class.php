<?php

require MODX_CORE_PATH . 'model/modx/processors/element/tv/create.class.php';

class mxTemplateVarCreateProcessor extends modTemplateVarCreateProcessor {

	public function beforeSet() {
		$content = $this->getProperty('content', false);
		if ($content !== false) {
			$this->setProperty('content', base64_decode($content));
		}

		$templates = array();
		$tmp = $this->getProperty('templates', array());
		foreach ($tmp as $id) {
			$templates[$id] = array(
				'id' => $id,
				'access' => true
			);
		}
		$this->setProperty('templates', $templates);

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

return 'mxTemplateVarCreateProcessor';