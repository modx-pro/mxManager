<?php

require MODX_CORE_PATH . 'model/modx/processors/system/log/getlist.class.php';

class mxSystemLogGetListProcessor extends modSystemLogGetListProcessor {

	public function initialize() {
		$this->setDefaultProperties(array(
			'dateFormat' => 'Y-m-d H:i:s',
		));

		return parent::initialize();
	}

	public function prepareLog(modManagerLog $log) {
		$logArray = parent::prepareLog($log);
		unset(
			$logArray['id'],
			$logArray['user'],
			$logArray['item'],
			$logArray['classKey']
		);
		$logArray['name'] = html_entity_decode($logArray['name'], ENT_QUOTES, $this->modx->getOption('modx_charset', null, 'UTF-8'));

		return $logArray;
	}

}

return 'mxSystemLogGetListProcessor';
