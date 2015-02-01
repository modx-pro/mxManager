<?php

switch ($modx->event->name) {

	case 'OnManagerPageInit':
		/** @var array $scriptProperties */
		/** @var mxManager $mxManager */
		if (empty($_REQUEST['mx_action'])) {
			return;
		}
		elseif (!$mxManager = $modx->getService('mxmanager', 'mxManager', MODX_CORE_PATH . 'components/mxmanager/model/mxmanager/', $scriptProperties)) {
			$this->modx->log(modX::LOG_LEVEL_ERROR, 'Could not load class msManager.');
			return;
		}

		$response = $mxManager->handleRequest($_REQUEST);
		@session_write_close();
		exit($modx->toJSON($response));
		break;

}