<?php

require MODX_CORE_PATH . 'model/modx/processors/browser/directory/remove.class.php';

class mxDirectoryRemoveProcessor extends modBrowserFolderRemoveProcessor {

	public function initialize() {
		$this->setProperty('dir', $this->getProperty('path'));

		return parent::initialize();
	}

}

return 'mxDirectoryRemoveProcessor';