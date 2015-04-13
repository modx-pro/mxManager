<?php

require MODX_CORE_PATH . 'model/modx/processors/browser/file/get.class.php';

class mxFileGetProcessor extends modBrowserFileGetProcessor {

	public function process() {
		$process = parent::process();
		$max_filesize = $this->modx->getOption('mxmanager_max_filesize', null, 1000000);

		if (!empty($process['object'])) {
			if ($process['object']['size'] === false) {
				return $this->failure($this->modx->lexicon('file_err_nf'));
			}
			if ($this->_isBinary($process['object']['content']) && !$process['object']['image']) {
				$process['object']['content'] = '';
				$process['object']['is_readable'] = false;
				$process['object']['is_writable'] = false;
			}
			else {
				$process['object']['content'] = base64_encode($process['object']['content']);
			}

			if (!empty($process['object']['content']) && $process['object']['size'] > $max_filesize) {
				return $this->failure($this->modx->lexicon('mxmanager_err_file_big', array(
					'size' => $this->_formatSize($process['object']['size'])
				)));
			}

			$process['object']['pathRelative'] = $process['object']['name'];
			$process['object']['path'] = rtrim(preg_replace('#' . $process['object']['basename'] . '$#', '', $process['object']['name']), '/') . '/';
			$process['object']['name'] = $process['object']['basename'];
			unset($process['object']['basename']);

			$process['object']['last_accessed'] = date('Y-m-d H:i:s', strtotime($process['object']['last_accessed']));
			$process['object']['last_modified'] = date('Y-m-d H:i:s', strtotime($process['object']['last_modified']));
		}

		return $process;
	}


	protected function _formatSize($size = 0) {

		if ($size >= 1000000) {
			$size = round($size / 1000000, 2) . ' Mb';
		}
		elseif ($size >= 1000) {
			$size = round($size / 1000, 2) . ' Kb';
		}
		else {
			$size .= ' b';
		}

		return $size;
	}

	protected function _isBinary($file) {
		return (substr_count($file, "^ -~") / 512 > 0.3) || (substr_count($file, "\x00") > 0);
	}

}

return 'mxFileGetProcessor';