<?php

class mxElementGetListProcessor extends modProcessor {
	public $classKey = 'modResource';
	public $languageTopics = array('category', 'element');
	public $permission = 'element_tree';
	protected $_types = array(
		'template' => 'modTemplate',
		'tv' => 'modTemplateVar',
		'chunk' => 'modChunk',
		'snippet' => 'modSnippet',
		'plugin' => 'modPlugin',
		'category' => 'modCategory',
	);
	protected $_permissions = array();


	/**
	 * @return string
	 */
	public function process() {
		$create = false;
		foreach ($this->_types as $type => $class) {
			$this->_permissions['new_' . $type] = $this->modx->hasPermission('new_' . $type);
			if ($create === false && $this->_permissions['new_' . $type] === true) {
				$create = true;
			}
		}
		$this->_permissions['create'] = $create;
		$this->_permissions['update'] = $this->modx->hasPermission('save_category');
		$this->_permissions['remove'] = $this->modx->hasPermission('delete_category');

		$type = $this->getProperty('type', '');
		if (empty($type)) {
			$result = $this->getSections();
			if ($result['total'] == 1) {
				$type = $type['rows'][0]['type'];
				$this->setProperty('section', $type);
				$result = $this->getElements($type, 0);
			}
		}
		else {
			$category = $this->getProperty('category', 0);
			$result = $this->getElements($type, $category);
		}

		return $this->outputArray($result['rows'], $result['total']);
	}

	/**
	 * @return array
	 */
	public function getSections() {
		$rows = array();
		$sections = array(
			'view_template' => array(
				'type' => 'template',
				'name' => $this->modx->lexicon('templates'),
				'permissions' => $this->_permissions,
			),
			'view_tv' => array(
				'type' => 'tv',
				'name' => $this->modx->lexicon('tmplvars'),
				'permissions' => $this->_permissions,
			),
			'view_chunk' => array(
				'type' => 'chunk',
				'name' => $this->modx->lexicon('chunks'),
				'permissions' => $this->_permissions,
			),
			'view_snippet' => array(
				'type' => 'snippet',
				'name' => $this->modx->lexicon('snippets'),
				'permissions' => $this->_permissions,
			),
			'view_plugin' => array(
				'type' => 'plugin',
				'name' => $this->modx->lexicon('plugins'),
				'permissions' => $this->_permissions,
			),/*
			'view_category' => array(
				'type' => 'category',
				'name' => $this->modx->lexicon('categories'),
				'permissions' => $this->_permissions,
			),*/
		);

		foreach ($sections as $permission => $section) {
			if ($this->modx->hasPermission($permission)) {
				$rows[] = $section;
			}
		}

		return array(
			'rows' => $rows,
			'total' => count($rows)
		);
	}

	/**
	 * @param $type
	 * @param int $category_id
	 *
	 * @return array
	 */
	public function getElements($type, $category_id = 0) {
		if ($type == 'category') {
			return $this->getCategories($category_id);
		}
		$rows = array();
		$class = $this->_types[$type];

		// Get categories
		$c = $this->modx->newQuery('modCategory');
		$c->leftJoin($class, $class, $class . '.category = modCategory.id');
		$c->leftJoin('modCategory', 'Children');
		$c->select($this->modx->getSelectColumns('modCategory', 'modCategory'));
		$c->select('COUNT(' . $class . '.id) as elements, COUNT(Children.id) as categories');
		$c->where('modCategory.parent = ' . $category_id);
		//$c->having('elements > 0 OR categories > 0');
		$c->sortby('modCategory.category', 'ASC');
		$c->groupby('modCategory.id');
		$categories = $this->modx->getIterator('modCategory', $c);
		/** @var modCategory $category */
		foreach ($categories as $category) {
			if (!$category->checkPolicy('list')) {
				continue;
			}
			/*
			elseif ($category->get('categories') > 0 && $category->get('elements') < 1) {
				if (!$this->_haveElements($category->get('id'), $class)) {
					continue;
				}
			}
			*/
			$rows[] = $this->_prepareCategoryRow($category);
		}

		// Get elements
		$c = $this->modx->newQuery($class);
		$c->where(array('category' => $category_id));
		$c->sortby($class == 'modTemplate' ? 'templatename' : 'name', 'ASC');
		$elements = $this->modx->getIterator($class, $c);
		/** @var modElement $element */
		foreach ($elements as $element) {
			if (!$element->checkPolicy('list')) {
				continue;
			}

			$rows[] = $this->_prepareElementRow($element);
		}

		return array(
			'rows' => $rows,
			'total' => count($rows)
		);
	}

	/**
	 * @param int $category_id
	 *
	 * @return array
	 */
	public function getCategories($category_id = 0) {
		$rows = array();

		$c = $this->modx->newQuery('modCategory');
		$c->where(array(
			'parent' => $category_id
		));
		$c->sortby('modCategory.category', 'ASC');
		$c->select($this->modx->getSelectColumns('modCategory', 'modCategory'));
		$c->select('COUNT(Children.id) as categories');
		$c->leftJoin('modCategory', 'Children');
		$c->groupby('modCategory.id');

		$categories = $this->modx->getIterator('modCategory', $c);
		/** @var modCategory $category */
		foreach ($categories as $category) {
			if (!$category->checkPolicy('list')) {
				continue;
			}

			$rows[] = array(
				'id' => $category->get('id'),
				'name' => $category->get('category'),
				'categories' => (int)$category->get('categories'),
				'type' => 'category',
				'permissions' => $this->_permissions,
			);
		}

		return array(
			'rows' => $rows,
			'total' => count($rows)
		);
	}

	/**
	 * @param modCategory $category
	 *
	 * @return array
	 */
	protected function _prepareCategoryRow(modCategory $category) {
		$type = $this->getProperty('type');
		$row = array(
			'id' => $category->get('id'),
			'name' => $category->get('category'),
			'categories' => (int)$category->get('categories'),
			'elements' => (int)$category->get('elements'),
		);
		$row['type'] = 'category';
		$row['permissions'] = $this->_permissions;

		return $row;
	}

	/**
	 * @param modElement $element
	 *
	 * @return array|mixed|null
	 */
	protected function _prepareElementRow(modElement $element) {
		$type = $this->getProperty('type');
		$row = $element->get(array('id', 'description', 'disabled'));
		$row['name'] = $type == 'template'
			? $element->get('templatename')
			: $element->get('name');
		$row['type'] = $type;
		$row['permissions'] = array(
			'update' => $element->checkPolicy('save'),
			//'view' => $element->checkPolicy('view'),
			'remove' => $element->checkPolicy('remove'),
		);

		return $row;
	}

	/**
	 * @param $id
	 * @param $class
	 *
	 * @return bool
	 */
	protected function _haveElements($id, $class) {
		$categories = $this->modx->getIterator('modCategory', array('parent' => $id));
		/** @var modCategory $category */
		foreach ($categories as $category) {
			$c = $this->modx->newQuery('modCategory');
			$c->select('modCategory.id');
			$c->select('COUNT(' . $class . '.id) as elements, COUNT(Children.id) as categories');
			$c->leftJoin($class, $class, $class . '.category = modCategory.id');
			$c->leftJoin('modCategory', 'Children');
			$c->where(array('id' => $category->get('id')));
			$c->having('elements > 0 OR categories > 0');
			$c->groupby('modCategory.id');

			if ($subCategory = $this->modx->getObject('modCategory', $c)) {
				return $subCategory->get('elements') > 0
					? true
					: $this->_haveElements($subCategory->get('id'), $class);
			}
		}

		return false;
	}

}

return 'mxElementGetListProcessor';