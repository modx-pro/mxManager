<?php

require MODX_CORE_PATH . 'model/modx/processors/system/clearcache.class.php';

class mxSystemClearCacheProcessor extends modSystemClearCacheProcessor {

	/**
	 * @return array|string
	 */
	public function process() {
		$connectorRequestClass = $this->modx->getOption('modConnectorRequest.class', null, 'modConnectorRequest');
		$this->modx->config['modRequest.class'] = $connectorRequestClass;
		$this->modx->getRequest();
		$this->modx->request->registerLogging(array(
			'register' => 'mgr',
			'topic' => '/clearcache/',
			'media_sources' => true,
			'menu' => true,
			'action_map' => true,
		));

		$clear = parent::process();
		if (empty($clear['success'])) {
			return $this->failure($clear['message']);
		}

		return $this->success('', $this->getLog());
	}

	/**
	 * @return array
	 */
	protected function getLog() {
		$registerClass = trim($this->getProperty('register_class', 'registry.modFileRegister'));
		$register = 'mgr';
		$topic = '/clearcache/';

		$options = array(
			'poll_limit' => $this->getProperty('poll_limit', 1),
			'poll_interval' => $this->getProperty('poll_interval', 1),
			'time_limit' => $this->getProperty('time_limit', 10),
			'msg_limit' => $this->getProperty('message_limit', 200),
			'remove_read' => true,
			'clear' => true,
		);

		$this->modx->getService('registry', 'registry.modRegistry');
		$this->modx->registry->addRegister($register, $registerClass, array('directory' => $register));
		if (!$this->modx->registry->$register->connect()) {
			return array();
		}
		$this->modx->registry->$register->subscribe($topic);

		$data = array();
		$messages = $this->modx->registry->$register->read($options);
		foreach ($messages as $message) {
			if ($message['msg'] == 'COMPLETED') {
				continue;
			}
			$data[] = array(
				'timestamp' => $message['timestamp'],
				'level' => strtolower($message['level']),
				'message' => trim(strip_tags($message['msg'])),
			);
		}

		return $data;
	}

}

return 'mxSystemClearCacheProcessor';