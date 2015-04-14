<?php

require MODX_CORE_PATH . 'model/modx/processors/resource/get.class.php';

class mxResourceGetProcessor extends modResourceGetProcessor {

	public $fields = array(
		'id', 'pagetitle', 'longtitle', 'hidemenu',
		'published', 'parent', 'template', 'context_key', 'class_key',
		'alias', 'menutitle', 'link_attributes', 'content_type', 'content_dispo',
		'description', 'introtext', 'content', 'menuindex',
		'hidemenu', 'published', 'show_in_tree',
		'pub_date', 'unpub_date', 'publishedon',
		'isfolder', 'searchable', 'richtext', 'uri_override', 'uri',
		'cacheable', 'clearcache', 'deleted',
	);
	/** @var mxManager $mxManager */
	public $mxManager;
	protected $_permissions = array();


	/**
	 * @return bool|null|string
	 */
	public function initialize() {
		$primaryKey = $this->getProperty($this->primaryKeyField, 0);
		if (!empty($primaryKey)) {
			$this->object = $this->modx->getObject($this->classKey, $primaryKey);
		}
		else {
			$class_key = $this->getProperty('class_key', $this->classKey);
			$this->object = $this->modx->newObject($class_key);
			$this->object->fromArray($this->getDefaultValues(), '', true, true);

		}
		if (empty($this->object)) {
			return $this->modx->lexicon($this->objectType . '_err_nfs', array($this->primaryKeyField => $primaryKey));
		}
		elseif ($this->checkViewPermission && $this->object instanceof modAccessibleObject && !$this->object->checkPolicy('view')) {
			return $this->modx->lexicon('access_denied');
		}

		if (!$this->mxManager = $this->modx->getService('mxmanager', 'mxManager', MODX_CORE_PATH . 'components/mxmanager/model/mxmanager/')) {
			return false;
		}
		$this->_permissions = array(
			'new_document' => $this->modx->hasPermission('new_document'),
			'new_weblink' => $this->modx->hasPermission('new_weblink'),
			'new_symlink' => $this->modx->hasPermission('new_symlink'),
			'new_static_resource' => $this->modx->hasPermission('new_static_resource'),
		);

		return true;
	}

	/**
	 * @return array|string
	 */
	public function process() {
		$resource = $this->object->get($this->fields);
		//$this->formatDates($resource);
		$resource['preview_url'] = $this->getPreviewUrl();
		$resource['permissions'] = $this->getPermissions();
		$resource['type'] = $this->modx->getCount('modResource', array('parent' => $this->object->get('id')))
			? 'folder'
			: 'resource';
		$resource['syncsite'] = (bool)$this->modx->getOption('syncsite_default');

		/** @var modResource $parent */
		if (!empty($resource['parent']) && $parent = $this->modx->getObject('modResource', $resource['parent'])) {
			$resource['parent_title'] = $parent->get('pagetitle');
		}
		else {
			$resource['parent_title'] = $this->modx->lexicon('no');
		}

		/** @var modTemplate $template */
		if (!empty($resource['template']) && $template = $this->modx->getObject('modTemplate', $resource['template'])) {
			$resource['template_title'] = $template->get('templatename');
			$resource['tvs'] = $this->modx->getCount('modTemplateVarTemplate', array('templateid' => $template->get('id'))) > 0;
		}
		else {
			$resource['template_title'] = $this->modx->lexicon('no');
			$resource['tvs'] = false;
		}

		if (!empty($resource['content_type']) && $content_type = $this->modx->getObject('modContentType', $resource['content_type'])) {
			$resource['content_type_title'] = $content_type->get('name');
		}
		else {
			$resource['content_type_title'] = $this->modx->lexicon('no');
		}
		if (!empty($resource['class_key']) && $resource['class_key'] == 'modWebLink') {
			if ($properties = $this->object->get('properties')) {
				$resource['responseCode'] = @$properties['core']['responseCode'];
			}
		}
		if (!empty($resource['parent']) && $parent = $this->modx->getObject('modResource', $resource['parent'])) {
			$resource['classes'] = $this->mxManager->getSubClasses($parent->get('class_key'), $this->_permissions);
		}
		else {
			$resource['classes'] = $this->mxManager->getSubClasses('', $this->_permissions);
		}

		$resource = $this->_prepareResource($resource);

		return $this->success('', $resource);
	}

	/**
	 * @return array
	 */
	public function getPermissions() {
		$permissions = array(
			'save' => $this->modx->hasPermission('save_document'),
			'view' => $this->modx->hasPermission('view_document'),
			'edit' => $this->modx->hasPermission('edit_document'),
			'delete' => $this->modx->hasPermission('delete_document'),
			'undelete' => $this->modx->hasPermission('undelete_document'),
			'publish' => $this->modx->hasPermission('publish_document'),
			'unpublish' => $this->modx->hasPermission('unpublish_document'),
			//'duplicate' => $this->modx->hasPermission('resource_duplicate'),
		);

		return $permissions;
	}

	/**
	 * @return string
	 */
	public function getPreviewUrl() {
		$previewUrl = $this->getProperty('previewUrl', '');
		if (!empty($previewUrl)) {
			return $previewUrl;
		}

		if ($this->object->get('id') && !$this->object->get('deleted')) {
			$sessionEnabled = '';
			$ctxSetting = $this->modx->getObject('modContextSetting', array(
				'context_key' => $this->object->get('context_key'),
				'key' => 'session_enabled'
			));

			if ($ctxSetting) {
				$sessionEnabled = $ctxSetting->get('value') == 0
					? array('preview' => 'true')
					: '';
			}

			$previewUrl = $this->modx->makeUrl($this->object->get('id'), $this->object->get('context_key'), $sessionEnabled, 'full');
		}

		return $previewUrl;
	}


	/**
	 * @return array
	 */
	public function getDefaultValues() {
		$context_key = $this->getProperty('context_key', 'web');
		/** @var modContext $context */
		if (!$context = $this->modx->getObject('modContext', array('key' => $context_key))) {
			return array();
		}
		$context->prepare($this->modx->config);
		$settings = $this->modx->user->getSettings();

		if ($parent_id = (int)$this->getProperty('parent', 0)) {
			$parent = $this->modx->getObject('modResource', $parent_id);
		}
		if (!$template = (int)$this->getProperty('template', 0)) {
			if (!empty($parent)) {
				$template = $parent->get('template');
			}
			elseif (!empty($context)) {
				$template = $context->getOption('default_template', 0, $settings);
			}
		}

		$values = array(
			'id' => 0,
			'template' => $template,
			'content_type' => $context->getOption('default_content_type', 1, $settings),
			'class_key' => $this->getProperty('class_key', 'modDocument'),
			'context_key' => $context_key,
			'parent' => $parent_id,
			'richtext' => $context->getOption('richtext_default', true, $settings),
			'hidemenu' => $context->getOption('hidemenu_default', 0, $settings),
			'published' => $context->getOption('publish_default', 0, $settings),
			'searchable' => $context->getOption('search_default', 1, $settings),
			'cacheable' => $context->getOption('cache_default', 1, $settings),
			'syncsite' => $context->getOption('syncsite_default', 1, $settings),
		);

		return $values;
	}


	protected function _prepareResource(array $resource) {
		foreach (array('description', 'introtext', 'content') as $field) {
			if (array_key_exists($field, $resource)) {
				$resource[$field] = base64_encode($resource[$field]);
			}
		}
		foreach (array('createdon', 'publishedon', 'pub_date', 'unpub_date') as $field) {
			if (array_key_exists($field, $resource) && $resource[$field] != '0000-00-00 00:00:00') {
				$resource[$field] = date('Y-m-d H:i:s O', strtotime($resource[$field]));
			}
		}

		return $resource;
	}

}

return 'mxResourceGetProcessor';