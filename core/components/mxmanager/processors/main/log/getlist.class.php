<?php

require MODX_CORE_PATH . 'model/modx/processors/system/log/getlist.class.php';

class mxSystemLogGetListProcessor extends modSystemLogGetListProcessor {

	public function prepareLog(modManagerLog $log) {
		$logArray = parent::prepareLog($log);
		unset(
			$logArray['id'],
			$logArray['user'],
			$logArray['item'],
			$logArray['classKey']
		);
		$logArray['occurred'] = str_replace(', ', "\n", $logArray['occurred']);
		$logArray['action'] = str_replace('_', "\n", $logArray['action']);
		$logArray['name'] = html_entity_decode($logArray['name'], ENT_QUOTES, $this->modx->getOption('modx_charset', null, 'UTF-8'));

		return $logArray;
	}

}

return 'mxSystemLogGetListProcessor';
