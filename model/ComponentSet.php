<?php
/**
 * @deprecated 3.0 Use ManyManyList or HasManyList
 */
class ComponentSet extends DataObjectSet {
	function setComponentInfo($type, $ownerObj, $ownerClass, $tableName, $childClass, $joinField = null) {
		Deprecation::notice('3.0', 'Use ManyManyList or HasManyList instead.');
	}
}
