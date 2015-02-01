<?php

class mxResourceGetListProcessor extends modObjectGetListProcessor {
	public $classKey = 'modResource';
	public $defaultSortField = 'modResource.menuindex';
	public $defaultSortDirection = 'ASC';
	public $checkListPermission = true;
	protected $_permissions = array();
	protected $_fields = array(
		'id','pagetitle','longtitle','hidemenu','deleted',
		'published','parent','context_key','class_key'
	);


	/**
	 * {@inheritDoc}
	 * @return boolean
	 */
	public function initialize() {
		$this->setDefaultProperties(array(
			'start' => $this->getProperty('start', 0),
			'limit' => $this->getProperty('parent') ? 20 : 0,
			'sort' => $this->defaultSortField,
			'dir' => $this->defaultSortDirection,
			'combo' => false,
			'query' => '',
		));
		if ($mxManager = $this->modx->getService('mxManager')) {
			$this->_permissions = $mxManager->getUserPermissions();
		}

		$pid = (int)$this->getProperty('parent');
		if ($pid && $parent = $this->modx->getObject($this->classKey, $pid)) {
			$class_key = $parent->get('class_key');
			if ($class_key == 'TicketsSection') {
				$this->setProperty('sort', $this->classKey . '.createdon');
				$this->setProperty('dir', 'DESC');
			}
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
		$c->select($this->modx->getSelectColumns($this->classKey, $this->classKey, '', $this->_fields) . ', COUNT(`Child`.`id`) as `children`');
		$c->groupby($this->classKey . '.id');

		return $c;
	}

	/**
	 * @param xPDOObject $object
	 * @return array
	 */
	public function prepareRow(xPDOObject $object) {
		$row = $object->get($this->_fields);
		$row['isfolder'] = $object->get('children') > 0;
		$row['permissions'] = $this->_permissions;
		$row['pagetitle'] = html_entity_decode($row['pagetitle'], ENT_QUOTES, $this->modx->getOption('modx_charset', null, 'UTF-8'));

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