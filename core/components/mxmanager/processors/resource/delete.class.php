<?php

require MODX_CORE_PATH . 'model/modx/processors/resource/delete.class.php';

class mxResourceDeleteProcessor extends modResourceDeleteProcessor {

	/**
	 * @return array|string
	 */
	public function process() {
		$result = parent::process();
		if (empty($result['success'])) {
			return $result;
		}
		else {
			$resource = $this->resource->get(array(
				'id','pagetitle','longtitle','hidemenu','deleted',
				'published','parent','context_key','class_key'
			));
			$resource['isfolder'] = $this->modx->getCount('modResource', array('parent' => $resource['id']));
			if ($mxManager = $this->modx->getService('mxManager')) {
				$resource['permissions'] = $mxManager->getUserPermissions();
			}

			return $this->success('', $resource);
		}
	}

}
return 'mxResourceDeleteProcessor';