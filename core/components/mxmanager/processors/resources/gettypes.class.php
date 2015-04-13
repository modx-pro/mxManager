<?php

class mxResourceGetTypeProcessor extends modObjectGetListProcessor {
	public $classKey = 'modContentType';
	public $languageTopics = array('content_type');
	public $defaultSortField = 'id';
	public $defaultSortDirection = 'ASC';


	public function prepareRow(xPDOObject $object) {
		return $object->get(array('id', 'name', 'description'));
	}

}

return 'mxResourceGetTypeProcessor';