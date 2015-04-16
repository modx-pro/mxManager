<?php

class mxResourceGetTVsProcessor extends modObjectProcessor {
	public $classKey = 'modResource';
	public $languageTopics = array('resource');
	public $objectType = 'resource';


	/**
	 * @return array|string
	 */
	public function process() {
		$tvs = $this->getTVs();

		return $this->success('', $tvs);
	}


	/**
	 * @return array
	 */
	public function getTVs() {
		$result = array();

		$resource_id = (int)$this->getProperty('id', 0);
		$template_id = (int)$this->getProperty('template', 0);
		/** @var modResource $resource */
		$resource = $this->modx->getObject('modResource', $resource_id);
		if ($resource && !$template_id) {
			$template_id = $resource->get('template');
		}
		/** @var modTemplate $template */
		$template = $this->modx->getObject('modTemplate', $template_id);
		if (!$template) {
			return $result;
		}

		$c = $this->modx->newQuery('modTemplateVar');
		$c->select($this->modx->getSelectColumns('modTemplateVar', 'modTemplateVar'));
		$c->innerJoin('modTemplateVarTemplate', 'modTemplateVarTemplate', array(
			"modTemplateVarTemplate.tmplvarid = modTemplateVar.id",
			'modTemplateVarTemplate.templateid' => $template->get('id')
		));
		$c->leftJoin('modCategory', 'Category');
		$c->select(array(
			"IF(ISNULL(modTemplateVarTemplate.rank),0,modTemplateVarTemplate.rank) AS tv_rank",
			'category_name' => 'Category.category',
		));
		$c->sortby('tv_rank', 'ASC');

		$list = $this->modx->getIterator('modTemplateVar', $c);
		/** @var modTemplateVar $tv */
		foreach ($list as $tv) {
			$result[] = array(
				'id' => $tv->get('id'),
				'field' => 'tv' . $tv->get('id'),
				'category' => $tv->get('category_name'),
				'type' => $tv->get('type'),
				'name' => $tv->get('name'),
				'caption' => $tv->get('caption'),
				'value' => $this->_getValue($tv, !empty($resource)
					? $resource->get('id')
					: 0),
				'elements' => $this->_getElements($tv),
				'properties' => $this->_getProperties($tv),
			);
		}
		//echo '<pre>';print_r($result);die;

		return $result;
	}


	/**
	 * @param modTemplateVar $tv
	 * @param int $resource_id
	 *
	 * @return array|mixed
	 */
	protected function _getValue(modTemplateVar $tv, $resource_id = 0) {
		$value = $tv->getValue($resource_id);
		$type = $tv->get('type');
		$multiple = array(
			'list-multiple-legacy' => '||',
			'listbox-multiple' => '||',
			//'tag' => ',',
			//'autotag' => ',',
			'checkbox' => '||'
		);

		if (in_array($type, array_keys($multiple))) {
			$value = trim($value) != ''
				? array_map('trim', explode($multiple[$type], $value))
				: array();
		}
		if ($type == 'date') {
			$value = date('Y-m-d H:i:s O', strtotime($value));
		}

		return $value;
	}


	/**
	 * @param modTemplateVar $tv
	 *
	 * @return array|mixed
	 */
	protected function _getElements(modTemplateVar $tv) {
		switch ($tv->get('type')) {
			case 'resourcelist':
				$elements = $this->_getResourcesList($tv);
				break;
			/*
			case 'autotag':
				$elements = $this->_getAutoTags($tv);
				break;
			*/
			default:
				$elements = $tv->parseInputOptions($tv->processBindings($tv->get('elements')));
		}
		if (count($elements) == 1 && empty($elements[0])) {
			$elements = array();
		}

		return $elements;
	}


	/**
	 * @param modTemplateVar $tv
	 *
	 * @return array|mixed|null
	 */
	protected function _getProperties(modTemplateVar $tv) {
		$properties = $tv->get('input_properties');
		if (!empty($properties)) {
			foreach ($properties as &$value) {
				if ($value === 'true' || $value === true) {
					$value = 1;
				}
				elseif ($value === 'false' || $value === false) {
					$value = 0;
				}
			}
		}

		return $properties;
	}


	/**
	 * @param modTemplateVar $tv
	 *
	 * @return array
	 */
	protected function _getResourcesList(modTemplateVar $tv) {
		$params = $tv->get('input_properties');
		$parents = !empty($params['parents']) || $params['parents'] === '0'
			? explode(',', $params['parents'])
			: 0;
		$params['depth'] = !empty($params['depth'])
			? $params['depth']
			: 10;
		if (empty($parents) || (empty($parents[0]) && $parents[0] !== '0')) {
			$parents = array();
		}
		$parentList = array();
		foreach ($parents as $parent) {
			/** @var modResource $parent */
			$parent = $this->modx->getObject('modResource', $parent);
			if ($parent) {
				$parentList[] = $parent;
			}
		}

		/* get all children */
		$ids = array();
		if (!empty($parentList)) {
			foreach ($parentList as $parent) {
				if (!empty($params['includeParent'])) $ids[] = $parent->get('id');
				$children = $this->modx->getChildIds($parent->get('id'), $params['depth'], array(
					'context' => $parent->get('context_key'),
				));
				$ids = array_merge($ids, $children);
			}
			$ids = array_unique($ids);
		}

		$c = $this->modx->newQuery('modResource');
		$c->leftJoin('modResource', 'Parent');
		if (!empty($ids)) {
			$c->where(array('modResource.id:IN' => $ids));
		}
		else {
			if (!empty($parents) && $parents[0] == 0) {
				$c->where(array('modResource.parent' => 0));
			}
		}
		if (!empty($params['where'])) {
			$params['where'] = $this->modx->fromJSON($params['where']);
			$c->where($params['where']);
		}
		if (!empty($params['limitRelatedContext']) && ($params['limitRelatedContext'] == 1 || $params['limitRelatedContext'] == 'true')) {
			$context_key = $this->modx->resource->get('context_key');
			$c->where(array('modResource.context_key' => $context_key));
		}
		$c->sortby('Parent.menuindex,modResource.menuindex', 'ASC');
		if (!empty($params['limit'])) {
			$c->limit($params['limit']);
		}

		$resources = $this->modx->getIterator('modResource', $c);
		$list = array();
		if (!empty($params['showNone'])) {
			$list[] = $this->modx->lexicon('no') . '==';
		}
		/** @var modResource $resource */
		foreach ($resources as $resource) {
			$list[] = $resource->get('pagetitle') . ' (' . $resource->get('id') . ')==' . $resource->get('id');
		}

		return $list;
	}


	/**
	 * @param modTemplateVar $tv
	 *
	 * @return array
	 */
	protected function _getAutoTags(modTemplateVar $tv) {
		$params = $tv->get('input_properties');
		if (empty($params['parent_resources'])) {
			$params['parent_resources'] = '';
		}

		/** @var xPDOQuery $c */
		$c = $this->modx->newQuery('modTemplateVarResource');
		$c->innerJoin('modTemplateVar', 'TemplateVar');
		$c->innerJoin('modResource', 'Resource');
		$c->where(array(
			'tmplvarid' => $tv->get('id'),
		));
		if (!empty($params['parent_resources'])) {
			$ids = array();
			$parents = explode(',', $params['parent_resources']);

			$currCtx = 'web';
			$this->modx->switchContext('web');
			foreach ($parents as $id) {
				/** @var modResource $r */
				$r = $this->modx->getObject('modResource', $id);
				if ($r && $currCtx != $r->get('context_key')) {
					$this->modx->switchContext($r->get('context_key'));
					$currCtx = $r->get('context_key');
				}
				if ($r) {
					$pids = $this->modx->getChildIds($id, 10, array('context' => $r->get('context_key')));
					$ids = array_merge($ids, $pids);
				}
				$ids[] = $id;
			}
			$this->modx->switchContext('mgr');
			$ids = array_unique($ids);
			$c->where(array(
				'Resource.id:IN' => $ids,
			));
		}

		$tvs = $this->modx->getIterator('modTemplateVarResource', $c);
		$list = array();
		/** @var modTemplateVarResource $tv */
		foreach ($tvs as $tv) {
			$list = array_merge($list, explode(',', $tv->get('value')));
		}

		return array_unique($list);
	}

}

return 'mxResourceGetTVsProcessor';