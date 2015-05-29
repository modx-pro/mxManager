<?php

class mxResourceGetListProcessor extends modProcessor {
	public $classKey = 'modResource';
	public $languageTopics = array('resource', 'context');
	public $permission = 'resource_tree';
	public $fields = array(
		'id', 'pagetitle', 'longtitle', 'hidemenu', 'deleted',
		'published', 'parent', 'context_key', 'class_key'//, 'createdon'
	);
	/** @var mxManager $mxManager */
	public $mxManager;
	protected $_permissions = array();


	public function initialize() {
		if (!$this->mxManager = $this->modx->getService('mxmanager', 'mxManager', MODX_CORE_PATH . 'components/mxmanager/model/mxmanager/')) {
			return false;
		}
		$this->_permissions = array(
			'new_document' => $this->modx->hasPermission('new_document'),
			'new_weblink' => $this->modx->hasPermission('new_weblink'),
			'new_symlink' => $this->modx->hasPermission('new_symlink'),
			'new_static_resource' => $this->modx->hasPermission('new_static_resource'),
		);

		return parent::initialize();
	}


	/**
	 * @return string
	 */
	public function process() {
		$context = $this->getProperty('context', '');
		if (empty($context)) {
			$result = $this->getContexts();
			if ($result['total'] == 1) {
				$context = $result['rows'][0];
				$this->setProperty('context', $context['key']);
				$result = $this->getResources($context['key'], 0);

				return $this->modx->toJSON(array(
					'success' => true,
					'total' => $result['total'],
					'results' => $result['rows'],
					'context_key' => $context['key'],
					'permissions' => $context['permissions'],
					'classes' => $this->modx->hasPermission('new_document_in_root')
						? $this->mxManager->getSubClasses('', $this->_permissions)
						: array()
				));
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

		$permissions = array(
			//'new' => 'new_context',
			//'edit' => $this->modx->hasPermission('edit_context'),
			//'delete' => $this->modx->hasPermission('delete_context'),
		);
		$classes = $this->modx->hasPermission('new_document_in_root')
			? $this->mxManager->getSubClasses('', $this->_permissions)
			: array();

		$c->select($this->modx->getSelectColumns('modContext', 'modContext', '', array('rank'), true));
		$contexts = $this->modx->getIterator('modContext', $c);
		/** @var modContext $context */
		foreach ($contexts as $context) {
			if (!$context->checkPolicy('list')) {
				$total -= 1;
				continue;
			}
			$context->set('permissions', $permissions);
			$context->set('classes', $classes);
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
	 *
	 * @return array
	 */
	public function getResources($context, $parent_id = 0) {
		$rows = array();
		$start = $this->getProperty('start', 0);
		$limit = 20;

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

		$c = $this->modx->newQuery($this->classKey);
		$c->where(array(
			'context_key' => $context,
			'parent' => $parent_id
		));
		$query = trim($this->getProperty('query'));
		if (!empty($query)) {
			$c->where(array(
				'pagetitle:LIKE' => "%{$query}%",
				'OR:longtitle:LIKE' => "%{$query}%",
			));
		}
		$total = $this->modx->getCount($this->classKey, $c);

		$c->limit($limit, $start);
		$c = $this->_sortResources($c);
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
			$resource->set('permissions', $permissions);
			$rows[] = $this->_prepareResourceRow($resource);
		}
		return array(
			'rows' => $rows,
			'total' => $total
		);
	}


	/**
	 * @param modContext $context
	 *
	 * @return array
	 */
	protected function _prepareContextRow(modContext $context) {
		$row = $context->toArray('', false, true);
		$row['type'] = 'context';
		if (empty($row['name'])) {
			$row['name'] = $row['key'];
		}

		return $row;
	}


	/**
	 * @param modResource $resource
	 *
	 * @return array
	 */
	protected function _prepareResourceRow(modResource $resource) {
		$row = $resource->toArray('', false, true);
		$row['type'] = $row['children'] > 0
			? 'folder'
			: 'resource';
		$row['classes'] = $this->mxManager->getSubClasses($resource->get('class_key'), $this->_permissions);
		$row['pagetitle'] = html_entity_decode($row['pagetitle'], ENT_QUOTES, $this->modx->getOption('modx_charset', null, 'UTF-8'));
		unset($row['children']);

		return $row;
	}


	/**
	 * @param xPDOQuery $c
	 *
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