<?php

require MODX_CORE_PATH . 'model/modx/processors/system/errorlog/get.class.php';

class mxSystemErrorLogGetProcessor extends modSystemErrorLogGetProcessor {

	public function process() {
		$response = parent::process();
		if (!empty($response['object']) && array_key_exists('log', $response['object'])) {
			$response['object']['log'] = base64_encode($response['object']['log']);
		}

		return $response;
	}
}

return 'mxSystemErrorLogGetProcessor';