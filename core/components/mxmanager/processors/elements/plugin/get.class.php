<?php

require MODX_CORE_PATH . 'model/modx/processors/element/plugin/get.class.php';

class mxPluginGetProcessor extends modPluginGetProcessor {

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
			'id', 'name', 'description', 'category'
		));
		$data['content'] = base64_encode($this->object->get('content'));
		$data['disabled'] = (int)$this->object->get('disabled');
		/** @var mxManager $mxManager */
		if ($mxManager = $this->modx->getService('mxManager')) {
			$data['categories'] = $mxManager->getElementCategories();
		}
		$data['events'] = $this->_getEvents();

		return $this->success('', $data);
	}

	protected function _getEvents() {
		$items = array();
		/** @var modProcessorResponse $response */
		$response = $this->modx->runProcessor('element/plugin/event/getlist', array(
			'plugin' => (int)$this->object->get('id'),
			'start' => 0,
			'limit' => 0,
		));
		if (!$response->isError()) {
			$tmp = $this->modx->fromJSON($response->getResponse());
			foreach ($tmp['results'] as $value) {
				$items[] = array(
					'name' => $value['name'],
					'priority' => (int)$value['priority'],
					'enabled' => (int)$value['enabled'],
					'group' => $value['groupname']
				);
				usort($items, 'self::_compare');
			}
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

return 'mxPluginGetProcessor';