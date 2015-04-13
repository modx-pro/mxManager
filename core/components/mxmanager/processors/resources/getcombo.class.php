<?php

require 'getlist.class.php';

class mxResourceGetComboProcessor extends mxResourceGetListProcessor {
	public $classKey = 'modResource';
	public $languageTopics = array('resource', 'context');


	/**
	 * @return string
	 */
	public function process() {
		$context = $this->getProperty('context', '');
		$parent = $this->getProperty('parent', 0);
		$result = $this->getResources($context, $parent);

		return $this->outputArray($result['rows'], $result['total']);
	}


	/**
	 * @param $context
	 * @param int $parent_id
	 *
	 * @return array
	 */
	public function getResources($context = '', $parent_id = 0) {
		$rows = array();
		$start = $this->getProperty('start', 0);
		$limit = 20;

		$c = $this->modx->newQuery($this->classKey);
		$c->where(array('id:!=' => $this->getProperty('id', 0)));
		if (!empty($context)) {
			$c->where(array('context_key' => $context));
		}
		if (!empty($parent_id)) {
			$c->where(array('parent' => $parent_id));
		}
		if ($id = (int)$this->getProperty('id')) {
			$c->where(array('id:!=' => $id));
		}
		$query = trim($this->getProperty('query'));
		if (!empty($query)) {
			$c->where(array(
				'pagetitle:LIKE' => "%{$query}%",
				'OR:longtitle:LIKE' => "%{$query}%",
			));
		}
		$total = $this->modx->getCount($this->classKey, $c);

		$c->limit($limit, $start);
		$c->sortby($this->classKey . '.parent ASC,' . $this->classKey . '.menuindex', 'ASC');
		$c->leftJoin($this->classKey, 'Child', array($this->classKey . '.id = Child.parent'));
		$c->select($this->modx->getSelectColumns($this->classKey, $this->classKey, '', $this->fields) . ', COUNT(`Child`.`id`) as `children`');
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

		if ($start == 0) {
			$resource = $this->modx->newObject('modResource');
			$resource->set('id', 0);
			$resource->set('pagetitle', $this->modx->lexicon('no'));
			$resource->set('published', true);
			$resource = $resource->get($this->fields);
			$resource['children'] = 0;
			$rows = array_merge(array($this->_prepareResourceRow($resource)), $rows);
		}

		return array(
			'rows' => $rows,
			'total' => $total
		);
	}


	/**
	 * @param $resource
	 *
	 * @return array
	 */
	protected function _prepareResourceRow($resource) {
		$row = is_object($resource)
			? $resource->toArray('', false, true)
			: $resource;

		$row['type'] = $row['children'] > 0
			? 'folder'
			: 'resource';
		$row['pagetitle'] = html_entity_decode($row['pagetitle'], ENT_QUOTES, $this->modx->getOption('modx_charset', null, 'UTF-8'));
		unset($row['children']);

		$titles = array();
		$parents = $this->modx->getParentIds($row['id'], 3, array('context' => $row['context_key']));
		if (count($parents)) {
			$parents = array_reverse($parents);
			foreach ($parents as $parent) {
				if ($parent > 0 && $resource = $this->modx->getObject('modResource', $parent)) {
					$titles[] = $resource->get('pagetitle');
				}
			}
		}

		$row['longtitle'] = $row['id'] > 0
			? $row['context_key'] . ' /' . implode('/', $titles)
			: '';

		return $row;
	}

}

return 'mxResourceGetComboProcessor';