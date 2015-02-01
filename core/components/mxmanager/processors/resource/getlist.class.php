<?php

class mxResourceGetListProcessor extends modObjectGetListProcessor {
	public $classKey = 'modResource';
	//public $defaultSortField = 'modResource.menuindex';
	//public $defaultSortDirection = 'ASC';
	public $defaultSortField = 'modResource.menuindex';
	public $defaultSortDirection = 'ASC';
	public $checkListPermission = true;
	protected $_permissions = array();


	/**
	 * {@inheritDoc}
	 * @return boolean
	 */
	public function initialize() {
		$this->setDefaultProperties(array(
			'start' => $this->getProperty('offset', 0),
			'limit' => $this->getProperty('parent') ? 20 : 0,
			'sort' => $this->defaultSortField,
			'dir' => $this->defaultSortDirection,
			'combo' => false,
			'query' => '',
		));
		if ($mxManager = $this->modx->getService('mxManager')) {
			$this->_permissions = $mxManager->getUserPermissions();
		}

		return parent::initialize();
	}

	/**
	 * @param xPDOQuery $c
	 * @return xPDOQuery
	 */
	public function prepareQueryBeforeCount(xPDOQuery $c) {
		$c->leftJoin($this->classKey, 'Child', array($this->classKey . '.id = Child.parent'));
		$c->where(array(
			'parent' => $this->getProperty('parent')
		));
		$c->select($this->modx->getSelectColumns($this->classKey, $this->classKey, '',
			array(
				'id','pagetitle','longtitle','hidemenu','deleted',
				'published','parent','context_key','class_key'
			))
			. ', COUNT(`Child`.`id`) as `children`'
		);
		$c->groupby($this->classKey . '.id');

		return $c;
	}

	/**
	 * @param xPDOObject $object
	 * @return array
	 */
	public function prepareRow(xPDOObject $object) {
		$row = $object->toArray('', true, true);
		$row['isfolder'] = (int)!empty($row['children']);
		$row['permissions'] = $this->_permissions;

		return $row;
	}


	/**
	 * @param array $array
	 * @param bool $count
	 * @return string
	 */
	public function outputArray(array $array, $count = false) {
		if (!$this->getProperty('parent')) {
			$c = $this->modx->newQuery('modContext', array('key:!=' => 'mgr'));
			$c->select($this->modx->getSelectColumns('modContext', 'modContext', '', array('rank', 'description'), true));
			$ctx = array();
			if ($c->prepare() && $c->stmt->execute()) {
				while ($context = $c->stmt->fetch(PDO::FETCH_ASSOC)) {
					foreach ($array as $k => $row) {
						if ($row['context_key'] != $context['key']) {
							continue;
						}
						if (!empty($context['name'])) {
							$row['context_key'] = $context['name'];
						}
						if (!isset($ctx[$context['key']])) {
							$ctx[$context['key']] = array($row);
						}
						else {
							$ctx[$context['key']][] = $row;
						}
						unset($array[$k]);
					}
				}
				$array = array();
				foreach ($ctx as $tmp) {
					$array = array_merge($array, $tmp);
				}
			}
		}

		return parent::outputArray($array, $count);
	}


}

return 'mxResourceGetListProcessor';