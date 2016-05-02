<?php
/**
 * @package framework
 * @subpackage testing
 */
class BehatFixtureFactory extends \FixtureFactory {
	public function createObject($name, $identifier, $data = null) {
		if(!$data) $data = array();

		// Copy identifier to some visible property unless its already defined.
		// Exclude files, since they generate their own named based on the file path.
		if(!$name != 'File' && !is_subclass_of($name, 'File')) {
			foreach(array('Name', 'Title') as $fieldName) {
				if(singleton($name)->hasField($fieldName) && !isset($data[$fieldName])) {
					$data[$fieldName] = $identifier;
					break;
				}
			}
		}

		return parent::createObject($name, $identifier, $data);
	}
}
