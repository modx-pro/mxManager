<?php

require 'getlist.class.php';

class mxTemplateGetComboProcessor extends mxElementGetListProcessor {
	public $classKey = 'modTemplate';


	/**
	 * @return string
	 */
	public function process() {
		$type = $this->getProperty('type', '');
		if (empty($type)) {
			return $this->failure('mxmanager_err_no_elem_type');
		}
		$result = $this->getElements('template', 0);

		return $this->outputArray($result['rows'], $result['total']);
	}


	/**
	 * @param $type
	 * @param int $category_id
	 *
	 * @return array
	 */
	public function getElements($type, $category_id = 0) {
		$class = $this->_types[$type];
		$start = $this->getProperty('start', 0);
		$limit = 20;

		$rows = array();
		$c = $this->modx->newQuery($class);
		$query = trim($this->getProperty('query'));
		if (!empty($query)) {
			$c->where(array(
				($class == 'modTemplate' ? 'templatename' : 'name') . ':LIKE' => "%{$query}%"));
		}

		//$c->where(array('category' => $category_id));
		$c->limit($limit, $start);
		$c->sortby($class == 'modTemplate' ? 'templatename' : 'name', 'ASC');
		$elements = $this->modx->getIterator($class, $c);
		/** @var modElement $element */
		foreach ($elements as $element) {
			if (!$element->checkPolicy('list')) {
				continue;
			}

			$rows[] = $this->_prepareElementRow($element);
		}

		if ($start == 0) {
			$element = $this->modx->newObject($class);
			$element->set('id', 0);
			$element->set(($class == 'modTemplate' ? 'templatename' : 'name'), $this->modx->lexicon('no'));
			$rows = array_merge(array($this->_prepareElementRow($element)), $rows);
		}

		return array(
			'rows' => $rows,
			'total' => count($rows)
		);
	}


	/**
	 * @param modElement $element
	 *
	 * @return array|mixed|null
	 */
	protected function _prepareElementRow(modElement $element) {
		$row = parent::_prepareElementRow($element);
		$row['permissions'] = array();

		$categories = $this->_getElementCategories($element->get('category'));
		$row['description'] = $row['id'] > 0
			? '/' . implode('/', array_reverse($categories))
			: '';
		if ($element instanceof modTemplate) {
			$row['tvs'] = (bool)$this->modx->getCount('modTemplateVarTemplate', array('templateid' => $element->get('id')));
		}

		return $row;
	}


	/**
	 * @param int $category_id
	 * @param int $height
	 *
	 * @return array
	 */
	protected function _getElementCategories($category_id = 0, $height = 3) {
		$categories = array();
		if ($category_id > 0 && $category = $this->modx->getObject('modCategory', $category_id)) {
			$categories[] = $category->get('category');
			if ($height > 1 && $parent = $category->get('parent')) {
				$categories = array_merge($categories, $this->_getElementCategories($parent, $height - 1));
			}
		}
		return $categories;
	}

}

return 'mxTemplateGetComboProcessor';