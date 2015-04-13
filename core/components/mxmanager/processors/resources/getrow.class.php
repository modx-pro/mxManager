<?php

require 'getlist.class.php';

class mxResourceGetRowProcessor extends mxResourceGetListProcessor {


	/**
	 * @return string
	 */
	public function process() {
		$resource = $this->getResource((int)$this->getProperty('id', 0));

		return $this->success('', $resource);
	}


	/**
	 * @param $id
	 *
	 * @return array
	 */
	public function getResource($id) {
		if ($id != 0) {
			$permissions = array(
				'save' => $this->modx->hasPermission('save_document'),
				'view' => $this->modx->hasPermission('view_document'),
				'edit' => $this->modx->hasPermission('edit_document'),
				'delete' => $this->modx->hasPermission('delete_document'),
				'undelete' => $this->modx->hasPermission('undelete_document'),
				'publish' => $this->modx->hasPermission('publish_document'),
				'unpublish' => $this->modx->hasPermission('unpublish_document'),
				//'duplicate' => $this->modx->hasPermission('resource_duplicate'),
			);

			$c = $this->modx->newQuery($this->classKey, $id);
			$c->leftJoin($this->classKey, 'Child', array($this->classKey . '.id = Child.parent'));
			$c->select($this->modx->getSelectColumns($this->classKey, $this->classKey, '', $this->fields) . ', COUNT(`Child`.`id`) as `children`');
			/** @var modResource $resource */
			if ($resource = $this->modx->getObject($this->classKey, $c)) {
				$resource->set('permissions', $permissions);

				return $this->_prepareResourceRow($resource);
			}
		}

		return array();
	}

}

return 'mxResourceGetRowProcessor';