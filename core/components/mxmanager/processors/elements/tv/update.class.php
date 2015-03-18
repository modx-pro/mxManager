<?php

require MODX_CORE_PATH . 'model/modx/processors/element/tv/update.class.php';

class mxTemplateVarUpdateProcessor extends modElementTvUpdateProcessor {

	public function beforeSet() {
		$content = $this->getProperty('content', false);
		if ($content !== false) {
			$this->setProperty('content', base64_decode($content));
		}

		$templates = array();
		$tmp = $this->getProperty('templates', array());
		foreach ($tmp as $id) {
			$templates[$id] = array(
				'id' => $id,
				'access' => true
			);
		}
		$c = $this->modx->newQuery('modTemplateVarTemplate', array('tmplvarid' => $this->object->get('id')));
		if (!empty($tmp)) {
			$c->where(array('templateid:NOT IN' => $tmp));
		}
		$c->select('templateid');
		if ($c->prepare() && $c->stmt->execute()) {
			while ($id = $c->stmt->fetchColumn()) {
				$templates[$id] = array(
					'id' => $id,
					'access' => false
				);
			}
		}
		$this->setProperty('templates', $templates);

		return parent::beforeSet();
	}


	public function cleanup() {
		$name = require 'get.class.php';
		/** @var modObjectGetProcessor $processor */
		$processor = new $name($this->modx, array(
			'id' => $this->object->get('id')
		));
		$processor->initialize();

		return $processor->process();
	}

}

return 'mxTemplateVarUpdateProcessor';