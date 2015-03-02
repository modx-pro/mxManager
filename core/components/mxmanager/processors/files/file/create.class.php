<?php

require MODX_CORE_PATH . 'model/modx/processors/browser/file/create.class.php';

class mxFileCreateProcessor extends modBrowserFileCreateProcessor {

	public function process() {
		$directory = rawurldecode($this->getProperty('path', ''));
		$this->setProperty('directory', rtrim($directory, '/') . '/');

		if ($content = $this->getProperty('content', false)) {
			$content = base64_decode($content);
			$this->setProperty('content', $content);
		}

		$response = parent::process();
		if (empty($response['success'])) {
			return $response;
		}

		require_once 'get.class.php';
		$processor = new mxFileGetProcessor($this->modx, array(
			'file' => $response['object']['file'],
			'source' => $this->getProperty('source', 1)
		));
		$processor->initialize();

		return $processor->process();
	}

}

return 'mxFileCreateProcessor';