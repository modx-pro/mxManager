<?php

require MODX_CORE_PATH . 'model/modx/processors/browser/file/remove.class.php';

class mxFileRemoveProcessor extends modBrowserFileRemoveProcessor {

	public function initialize() {
		$this->setProperty('file', $this->getProperty('path'));

		return parent::initialize();
	}

}

return 'mxFileRemoveProcessor';