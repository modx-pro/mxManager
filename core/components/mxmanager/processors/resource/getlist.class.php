<?php

class mxResourceGetListProcessor extends modProcessor {
	public $classKey = 'modResource';
	protected $_permissions = array();
	protected $_fields = array(
		'id', 'pagetitle', 'longtitle', 'hidemenu', 'deleted',
		'published', 'parent', 'context_key', 'class_key'
	);


	/**
	 * @return string
	 */
	public function process() {
		$context = $this->getProperty('context', '');
		if (empty($context)) {
			$result = $this->getContexts();
			if ($result['total'] == 1) {
				$context = $result['rows'][0]['key'];
				$result = $this->getResources($context, 0);
			}
		}
		else {
			$parent = $this->getProperty('parent', 0);
			$result = $this->getResources($context, $parent);
		}

		return $this->outputArray($result['rows'], $result['total']);
	}

	/**
	 * @return array
	 */
	public function getContexts() {
		$rows = array();
		$c = $this->modx->newQuery('modContext', array('key:!=' => 'mgr'));
		$total = $this->modx->getCount('modContext', $c);

		$c->select($this->modx->getSelectColumns('modContext', 'modContext', '', array('rank'), true));
		$contexts = $this->modx->getIterator('modContext', $c);
		/** @var modContext $context */
		foreach ($contexts as $context) {
			if (!$context->checkPolicy('list')) {
				$total -= 1;
				continue;
			}
			$rows[] = $this->_prepareContextRow($context);
		}

		return array(
			'rows' => $rows,
			'total' => $total
		);
	}


	/**
	 * @param $context
	 * @param int $parent_id
	 * @return array
	 */
	public function getResources($context, $parent_id = 0) {
		$rows = array();
		$start = $this->getProperty('start', 0);
		$limit = 20;

		if ($mxManager = $this->modx->getService('mxManager')) {
			$this->_permissions = $mxManager->getUserPermissions();
		}

		$c = $this->modx->newQuery($this->classKey);
		$c->where(array(
			'context_key' => $context,
			'parent' => $parent_id
		));
		$total = $this->modx->getCount($this->classKey, $c);

		$c->limit($limit, $start);
		$c = $this->_sortResources($c);
		$c->leftJoin($this->classKey, 'Child', array($this->classKey . '.id = Child.parent'));
		$c->select($this->modx->getSelectColumns($this->classKey, $this->classKey, '', $this->_fields) . ', COUNT(`Child`.`id`) as `children`');
		$c->groupby($this->classKey . '.id');
		$resources = $this->modx->getIterator($this->classKey, $c);
		/** @var modResource $resource */
		foreach ($resources as $resource) {
			if (!$resource->checkPolicy('list')) {
				$total -= 1;
				continue;
			}
			$rows[] = $this->_prepareResourceRow($resource);
		}

		return array(
			'rows' => $rows,
			'total' => $total
		);
	}

	/**
	 * @param modContext $context
	 * @return array
	 */
	protected function _prepareContextRow(modContext $context) {
		$row = $context->toArray('', true, true);
		$row['type'] = 'context';

		return $row;
	}

	/**
	 * @param modResource $resource
	 * @return array
	 */
	protected function _prepareResourceRow(modResource $resource) {
		$row = $resource->toArray('', true, true);

		$row['type'] = $resource->get('children') > 0
			? 'folder'
			: 'resource';
		$row['permissions'] = $this->_permissions;
		$row['pagetitle'] = html_entity_decode($row['pagetitle'], ENT_QUOTES, $this->modx->getOption('modx_charset', null, 'UTF-8'));

		return $row;
	}

	/**
	 * @param xPDOQuery $c
	 * @return xPDOQuery
	 */
	protected function _sortResources(xPDOQuery $c) {
		$parent_id = $this->getProperty('parent', 0);
		$sort = $this->classKey . '.menuindex';
		$dir = 'ASC';

		if ($parent_id && $parent = $this->modx->getObject($this->classKey, $parent_id)) {
			$class_key = $parent->get('class_key');
			if ($class_key == 'TicketsSection') {
				$sort = $this->classKey . '.createdon';
				$dir = 'DESC';
			}
		}
		$c->sortby($sort, $dir);

		return $c;
	}

}

return 'mxResourceGetListProcessor';