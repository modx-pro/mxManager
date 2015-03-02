<?php

require MODX_CORE_PATH . 'model/modx/processors/browser/directory/create.class.php';

class mxDirectoryCreateProcessor extends modBrowserFolderCreateProcessor {

	public function process() {
		$this->setProperty('parent', $this->getProperty('path'));

		return parent::process();
	}

}

return 'mxDirectoryCreateProcessor';