<?php
/**
 * @deprecated 2.5 Use ManyManyList or HasManyList
 */
class ComponentSet extends DataObjectSet {
	function setComponentInfo($type, $ownerObj, $ownerClass, $tableName, $childClass, $joinField = null) {
		user_error("ComponentSet is deprecated; use HasManyList or ManyManyList", E_USER_WARNING);
	}

}

?>
