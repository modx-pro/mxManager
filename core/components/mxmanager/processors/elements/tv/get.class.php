<?php

require MODX_CORE_PATH . 'model/modx/processors/element/tv/get.class.php';

class mxTemplateVarGetProcessor extends modTemplateVarGetProcessor {

	public function initialize() {
		$primaryKey = $this->getProperty($this->primaryKeyField, 0);
		if (!empty($primaryKey)) {
			$this->object = $this->modx->getObject($this->classKey, $primaryKey);
		}
		else {
			$this->object = $this->modx->newObject($this->classKey, array(
				'description' => '',
				'type' => 'text',
				'display' => 'default',
			));
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
			'id', 'name', 'description', 'caption',  'category', 'display', 'type'
		));
		$data['content'] = base64_encode($this->object->get('content'));
		/** @var mxManager $mxManager */
		if ($mxManager = $this->modx->getService('mxManager')) {
			$data['categories'] = $mxManager->getElementCategories();
		}
		$data['types'] = $this->_getTypes();
		$data['displays'] = $this->_getDisplays();
		$data['templates'] = $this->_getTemplates();

		return $this->success(' ', $data);
	}


	protected function _getTypes() {
		$items = array();
		/** @var modProcessorResponse $response */
		$response = $this->modx->runProcessor('element/tv/renders/getinputs');
		if (!$response->isError()) {
			$tmp = $this->modx->fromJSON($response->getResponse());
			$items = $tmp['results'];
		}

		return $items;
	}


	protected function _getDisplays() {
		$items = array();
		/** @var modProcessorResponse $response */
		$response = $this->modx->runProcessor('element/tv/renders/getoutputs');
		if (!$response->isError()) {
			$tmp = $this->modx->fromJSON($response->getResponse());
			$items = $tmp['results'];
		}

		return $items;
	}


	protected function _getTemplates() {
		$items = array();
		/** @var modProcessorResponse $response */
		$response = $this->modx->runProcessor('element/tv/template/getlist', array(
			'tv' => (int)$this->object->get('id'),
			'start' => 0,
			'limit' => 0,
		));
		if (!$response->isError()) {
			$tmp = $this->modx->fromJSON($response->getResponse());
			foreach ($tmp['results'] as $value) {
				$items[] = array(
					'id' => (int)$value['id'],
					'name' => $value['templatename'],
					'description' => $value['description'],
					'enabled' => (int)$value['access'],
				);
			}
			usort($items, 'self::_compare');
		}

		return $items;
	}

	protected static function _compare($arr1, $arr2) {
		if ($arr1['enabled'] == $arr2['enabled']) {
			return strcasecmp($arr1['name'], $arr2['name']);
		}
		else {
			return $arr1['enabled'] < $arr2['enabled']
				? 1
				: -1;
		}
	}

}

return 'mxTemplateVarGetProcessor';