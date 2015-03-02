<?php

require MODX_CORE_PATH . 'model/modx/processors/browser/file/update.class.php';

class mxFileUpdateProcessor extends modBrowserFileUpdateProcessor {

	public function process() {
		$file = rawurldecode($this->getProperty('path', ''));
		$name = trim($this->getProperty('name', ''));
		if (!empty($name)) {
			$basename = basename($file);
			if (strtolower($basename) != strtolower($name)) {
				require_once 'rename.class.php';
				$processor = new mxFileRenameProcessor($this->modx, array(
					'path' => $file,
					'name' => $name,
					'source' => $this->getProperty('source', 1)
				));

				$processor->initialize();
				$rename = $processor->process();
				if (empty($rename['success'])) {
					return $rename;
				}

				$file = preg_replace('#' . $basename . '$#i', $name, $file);
			}
		}

		$content = $this->getProperty('content', false);
		if ($content !== false) {
			$content = base64_decode($content);
			$this->setProperty('file', $file);
			$this->setProperty('content', $content);
			$save = parent::process();
			if (empty($save['success'])) {
				return $save;
			}
		}

		require_once 'get.class.php';
		$processor = new mxFileGetProcessor($this->modx, array(
			'file' => $file,
			'source' => $this->getProperty('source', 1)
		));
		$processor->initialize();

		return $processor->process();
	}

}

return 'mxFileUpdateProcessor';