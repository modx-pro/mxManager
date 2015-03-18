<?php

require MODX_CORE_PATH . 'model/modx/processors/element/template/get.class.php';

class mxTemplateGetProcessor extends modTemplateGetProcessor {

	public function initialize() {
		$primaryKey = $this->getProperty($this->primaryKeyField, 0);
		if (!empty($primaryKey)) {
			$this->object = $this->modx->getObject($this->classKey, $primaryKey);
		}
		else {
			$this->object = $this->modx->newObject($this->classKey, array('description' => ''));
		}
		if (empty($this->object)) {
			return $this->modx->lexicon($this->objectType.'_err_nfs', array($this->primaryKeyField => $primaryKey));
		}
		elseif ($this->checkViewPermission && $this->object instanceof modAccessibleObject && !$this->object->checkPolicy('view')) {
			return $this->modx->lexicon('access_denied');
		}

		return true;
	}

	public function cleanup() {
		$data = $this->object->get(array(
			'id', 'description', 'category'
		));
		$data['name'] = $this->object->get('templatename');
		$data['content'] = base64_encode($this->object->get('content'));
		/** @var mxManager $mxManager */
		if ($mxManager = $this->modx->getService('mxManager')) {
			$data['categories'] = $mxManager->getElementCategories();
		}

		return $this->success('', $data);
	}

}

return 'mxTemplateGetProcessor';