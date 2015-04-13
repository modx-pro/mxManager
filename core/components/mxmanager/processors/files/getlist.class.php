<?php

class mxFileGetListProcessor extends modProcessor {
	public $classKey = 'sources.modMediaSource';
	public $languageTopics = array('sources', 'file');
	public $permission = 'file_tree';
	protected $_images = array();

	/**
	 * @return string
	 */
	public function process() {
		$source = (int)$this->getProperty('source', 0);
		if (!$source) {
			$result = $this->getSources();
			if ($result['total'] == 1) {
				$source = $result['rows'][0];
				$this->setProperty('source', $source['id']);
				$result = $this->getPath($source['id'], '/');

				return $this->modx->toJSON(array(
					'success' => true,
					'total' => $result['total'],
					'results' => $result['rows'],
					'source' => $source['id'],
					'permissions' => $source['permissions'],
				));
			}
		}
		else {
			$path = $this->getProperty('path', '/');
			$result = $this->getPath($source, $path);
		}

		return $this->outputArray($result['rows'], $result['total']);
	}


	/**
	 * @return array
	 */
	public function getSources() {
		$rows = array();
		$c = $this->modx->newQuery($this->classKey);
		$total = $this->modx->getCount($this->classKey);

		$c->select('id, name, description');
		$c->sortby('id', 'ASC');
		$sources = $this->modx->getIterator($this->classKey, $c);
		/** @var modMediaSource $source */
		foreach ($sources as $source) {
			if (!$source->checkPolicy('list')) {
				$total -= 1;
				continue;
			}
			$rows[] = $this->_prepareSourceRow($source);
		}

		return array(
			'rows' => $rows,
			'total' => $total
		);
	}

	/**
	 * @param $source_id
	 * @param $path
	 * @return array
	 */
	public function getPath($source_id, $path) {
		$rows = array();
		if ($source = $this->_getSource($source_id)) {
			if ($source->checkPolicy('list')) {
				$source->setRequestProperties($this->getProperties());
				$source->initialize();
				$list = $source->getContainerList($path);
				foreach ($list as $item) {
					$rows[] = $this->_preparePathRow($item);
				}
			}
		}

		return array(
			'rows' => $rows,
			'total' => count($rows)
		);
	}

	/**
	 * @param modMediaSource $source
	 * @return array
	 */
	protected function _prepareSourceRow(modMediaSource $source) {
		$row = $source->toArray('', true, true);
		$row['type'] = 'source';
		$row['permissions'] = array(
			'view' => true,
			'create' => true,
		);

		return $row;
	}

	/**
	 * @param array $item
	 * @return array
	 */
	protected function _preparePathRow(array $item) {
		$row = array(
			'source' => (int)$this->getProperty('source', 0),
			'path' => $item['path'],
			'pathRelative' => $item['pathRelative'],
			'name' => $item['text'],
			'type' => $item['type'],
			'chmod' => $item['perms'],
			'permissions' => array()
		);
		$classes = explode(' ', $item['cls']);
		foreach ($classes as $class) {
			if (!empty($class) && $class[0] == 'p') {
				$row['permissions'][substr($class, 1)] = true;
			}
		}

		if ($row['type'] == 'file') {
			$row['ext'] = strtolower(pathinfo($row['name'], PATHINFO_EXTENSION));
			if (in_array($row['ext'], $this->_images)) {
				$row['type'] = 'image';
				$row['permissions']['update'] = false;
			}
			else {
				$icons = explode(' ', $item['iconCls']);
				if (in_array('icon-lock', $icons) && !empty($row['permissions']['update'])) {
					$row['permissions']['update'] = false;
				}
			}
		}

		return $row;
	}

	/**
	 * @param $source_id
	 * @return bool|modFileMediaSource|modMediaSource|null
	 */
	protected function _getSource($source_id) {
		$this->modx->loadClass('sources.modMediaSource');
		$source = modMediaSource::getDefaultSource($this->modx, $source_id);
		if (empty($source) || !$source->getWorkingContext()) {
			return false;
		}
		$images = $this->modx->getOption('imageExtensions', $source->getPropertyList(), 'jpg,jpeg,png,gif');
		$this->_images = array_map('trim', explode(',', $images));

		return $source;
	}

}

return 'mxFileGetListProcessor';