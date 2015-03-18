<?php

class mxAuthProcessor extends modProcessor {
	protected $ctx = 'mgr';

	/**
	 * @return array|string
	 */
	public function process() {

		$login = $this->Login();
		if ($login !== true) {
			return $this->failure($login);
		}

		$sections = $this->getSections();
		if (is_array($sections)) {
			$version = $this->modx->getVersionData();
			return $this->success('', array(
				'rows' => $sections,
				'site_url' => $this->modx->getOption('site_url'),
				'version' => $version['full_appname'],
			));
		}
		else {
			return $this->failure($sections);
		}
	}

	/**
	 * @return array|string
	 */
	protected function getSections() {
		if (!$this->modx->user->isAuthenticated($this->ctx)) {
			return $this->modx->lexicon('access_denied');
		}
		elseif (!$this->modx->hasPermission('frames')) {
			return $this->modx->lexicon('mxmanager_err_no_frames');
		}

		$permissions = array(
			'view_site' => '',
			'resources' => 'resource_tree',
			'elements' => 'element_tree',
			'files' => 'file_tree',
			'error_log' => 'error_log_view',
			'manager_log' => 'logs',
			'clear_cache' => 'empty_cache',
		);

		$sections = array();
		foreach ($permissions as $section => $permission) {
			if (empty($permission) || $this->modx->hasPermission($permission)) {
				$sections[] = $section;
			}
		}

		return $sections;
	}

	/**
	 * @return bool|string
	 */
	protected function Login() {
		$username = trim($this->getProperty('username'));
		$password = trim($this->getProperty('password'));

		/** @var modProcessorResponse $res */
		$response = $this->modx->runProcessor('security/login', array(
			'username' => $username,
			'password' => $password,
			'rememberme' => true,
			'login_context' => 'mgr',
		));

		if ($response->isError()) {
			$message = $response->getMessage();
			$this->modx->log(modX::LOG_LEVEL_ERROR, '[mxManager] Could not authorize user "' . $username . '": ' . $message);
			return $message;
		}
		$this->modx->user = null;
		$this->modx->user = $this->modx->getUser($this->ctx, true);

		return true;
	}

}

return 'mxAuthProcessor';