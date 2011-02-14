<?php

/**
 * Description of DataService
 *
 * @author marcus@silverstripe.com.au
 * @license BSD License http://silverstripe.org/bsd-license/
 */
class DataService {
	
	public function save(DataObject $object) {
		$object->write();
	}
}
