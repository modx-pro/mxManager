<?php

require MODX_CORE_PATH . 'model/modx/processors/element/category/update.class.php';

class mxElementCategoryUpdateProcessor extends modElementCategoryUpdateProcessor {

	/**
	 * @return bool
	 */
	public function beforeSet() {
		$this->setProperty('category', $this->getProperty('name'));
		$this->unsetProperty('title');

		return parent::beforeSet();
	}

}

return 'mxElementCategoryUpdateProcessor';