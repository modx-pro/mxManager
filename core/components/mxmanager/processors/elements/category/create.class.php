<?php

require MODX_CORE_PATH . 'model/modx/processors/element/category/create.class.php';

class mxElementCategoryCreateProcessor extends modElementCategoryCreateProcessor {

	/**
	 * @return bool
	 */
	public function beforeSet() {
		$this->setProperty('parent', $this->getProperty('category', 0));
		$this->setProperty('category', $this->getProperty('name'));
		$this->unsetProperty('title');

		return parent::beforeSet();
	}

}

return 'mxElementCategoryCreateProcessor';