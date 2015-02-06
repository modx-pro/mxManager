<?php

class mxElementGetListProcessor extends modProcessor {
	public $classKey = 'modResource';
	public $languageTopics = array('category','element');
	public $permission = 'element_tree';
	protected $_types = array(
		'template' => 'modTemplate',
		'tv' => 'modTemplateVar',
		'chunk' => 'modChunk',
		'snippet' => 'modSnippet',
		'plugin' => 'modPlugin',
		'category' => 'modCategory',
	);


	/**
	 * @return string
	 */
	public function process() {
		$section = $this->getProperty('section', '');
		if (empty($section)) {
			$result = $this->getSections();
			if ($result['total'] == 1) {
				$section = $result['rows'][0]['type'];
				$this->setProperty('section', $section);
				$result = $this->getElements($section, 0);
			}
		}
		else {
			$category = $this->getProperty('category', 0);
			$result = $this->getElements($section, $category);
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
				'section' => 'template',
				'type' => 'section',
				'title' => $this->modx->lexicon('templates'),
				'permissions' => array(
					'new_template' => $this->modx->hasPermission('new_template'),
					'new_category' =>$this->modx->hasPermission('new_category'),
				),
			),
			'view_tv' => array(
				'section' => 'tv',
				'type' => 'section',
				'title' => $this->modx->lexicon('tmplvars'),
				'permissions' => array(
					'new_tv' => $this->modx->hasPermission('new_tv'),
					'new_category' =>$this->modx->hasPermission('new_category'),
				),
			),
			'view_chunk' => array(
				'section' => 'chunk',
				'type' => 'section',
				'title' => $this->modx->lexicon('chunks'),
				'permissions' => array(
					'new_chunk' => $this->modx->hasPermission('new_chunk'),
					'new_category' =>$this->modx->hasPermission('new_category'),
				),
			),
			'view_snippet' => array(
				'section' => 'snippet',
				'type' => 'section',
				'title' => $this->modx->lexicon('snippets'),
				'permissions' => array(
					'new_snippet' => $this->modx->hasPermission('new_snippet'),
					'new_category' =>$this->modx->hasPermission('new_category'),
				),
			),
			'view_plugin' => array(
				'section' => 'plugin',
				'type' => 'section',
				'title' => $this->modx->lexicon('plugins'),
				'permissions' => array(
					'new_plugin' => $this->modx->hasPermission('new_plugin'),
					'new_category' => $this->modx->hasPermission('new_category'),
				),
			),
			'view_category' => array(
				'section' => 'category',
				'type' => 'section',
				'title' => $this->modx->lexicon('categories'),
				'permissions' => array(
					'new_category' => $this->modx->hasPermission('new_category'),
				),
			),
		);

		foreach ($sections as $permission => $row) {
			if ($this->modx->hasPermission($permission)) {
				$rows[] = $row;
			}
		}

		return array(
			'rows' => $rows,
			'total' => count($rows)
		);
	}

	/**
	 * @param $section
	 * @param int $category_id
	 * @return array
	 */
	public function getElements($section, $category_id = 0) {
		if ($section == 'category') {
			return $this->getCategories($category_id);
		}
		$rows = array();
		$class = $this->_types[$section];

		// Get categories
		$c = $this->modx->newQuery('modCategory');
		$c->select($this->modx->getSelectColumns('modCategory', 'modCategory'));
		$c->select('COUNT(' . $class . '.id) as elements, COUNT(Children.id) as categories');
		$c->leftJoin($class, $class, $class . '.category = modCategory.id');
		$c->leftJoin('modCategory', 'Children');
		$c->where('modCategory.parent = ' . $category_id);
		$c->having('elements > 0 OR categories > 0');
		$c->sortby('modCategory.category', 'ASC');
		$c->groupby('modCategory.id');
		$categories = $this->modx->getIterator('modCategory',$c);
		/** @var modCategory $category */
		foreach ($categories as $category) {
			if (!$category->checkPolicy('list')) {
				continue;
			}
			elseif ($category->get('categories') > 0 && $category->get('elements') < 1) {
				if (!$this->_haveElements($category->get('id'), $class)) {
					continue;
				}
			}
			$rows[] = $this->_prepareCategoryRow($category);
		}

		// Get elements
		$c = $this->modx->newQuery($class);
		$c->where(array('category' => $category_id));
		$c->sortby($class == 'modTemplate' ? 'templatename' : 'name','ASC');
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
	 * @return array
	 */
	public function getCategories($category_id = 0) {
		$rows = array();

		$c = $this->modx->newQuery('modCategory');
		$c->where(array(
			'parent' => $category_id
		));
		$c->sortby('modCategory.category', 'ASC');
		$c->select($this->modx->getSelectColumns('modCategory','modCategory'));
		$c->select('COUNT(Children.id) as categories');
		$c->leftJoin('modCategory','Children');
		$c->groupby('modCategory.id');

		$permissions = array();
		$types = array('template','tv','chunk','snippet','plugin');
		foreach ($types as $type) {
			$permissions['new_' . $type] = $this->modx->hasPermission('new_' . $type);
		}
		$permissions['new_category'] = $this->modx->hasPermission('new_category');
		$permissions['edit_category'] = $this->modx->hasPermission('edit_category');
		$permissions['delete_category'] = $this->modx->hasPermission('delete_category');

		$categories = $this->modx->getIterator('modCategory',$c);
		/** @var modCategory $category */
		foreach ($categories as $category) {
			if (!$category->checkPolicy('list')) {
				continue;
			}

			$rows[] = array(
				'id' => $category->get('id'),
				'title' => $category->get('category'),
				'categories' => (int)$category->get('categories'),
				'section' => 'category',
				'type' => 'category',
				'permissions' => $permissions,
			);
		}

		return array(
			'rows' => $rows,
			'total' => count($rows)
		);
	}

	/**
	 * @param modCategory $category
	 * @return array
	 */
	protected function _prepareCategoryRow(modCategory $category) {
		$section = $this->getProperty('section');
		$row = array(
			'id' => $category->get('id'),
			'title' => $category->get('category'),
			'categories' => (int)$category->get('categories'),
		);
		$row['section'] = $section;
		$row['type'] = 'category';
		$row['permissions'] = array(
			'new_category' => $this->modx->hasPermission('new_category'),
			'edit_category' => $this->modx->hasPermission('edit_category'),
			'delete_category' => $this->modx->hasPermission('delete_category'),
		);
		if ($section = $this->getProperty('section', '')) {
			$row['permissions']['new_' . $section] = $this->modx->hasPermission('new_' . $section);
		}

		return $row;
	}

	/**
	 * @param modElement $element
	 * @return array|mixed|null
	 */
	protected function _prepareElementRow(modElement $element) {
		$section = $this->getProperty('section');
		$row = $element->get(array('id', 'description', 'disabled'));
		$row['title'] = $section == 'template'
			? $element->get('templatename')
			: $element->get('name');
		$row['section'] = $section;
		$row['type'] = 'element';
		$row['permissions'] = array(
			'save' => $element->checkPolicy('save'),
			'view' => $element->checkPolicy('view'),
			'remove' => $element->checkPolicy('remove'),
		);

		return $row;
	}

	/**
	 * @param $id
	 * @param $class
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