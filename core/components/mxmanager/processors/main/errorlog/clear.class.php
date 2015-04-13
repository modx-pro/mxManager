<?php

require MODX_CORE_PATH . 'model/modx/processors/system/errorlog/clear.class.php';

class mxSystemErrorLogClearProcessor extends modSystemErrorLogClearProcessor {

	public function process() {
		parent::process();

		$name = require 'get.class.php';
		/** @var modObjectGetProcessor $processor */
		$processor = new $name($this->modx);
		$processor->initialize();

		return $processor->process();
	}

}

return 'mxSystemErrorLogClearProcessor';
