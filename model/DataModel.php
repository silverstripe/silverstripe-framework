<?php

/**
 * Representation of a DataModel - a collection of DataLists for each different data type.
 * 
 * Usage:
 * 
 * $model = new DataModel;
 * $mainMenu = $model->SiteTree->where('"ParentID" = 0 AND "ShowInMenus" = 1');
 */
class DataModel {
	protected static $inst;
	
	/**
	 * Get the global DataModel.
	 */
	static function inst() {
		if(!self::$inst) self::$inst = new self;
		return self::$inst;
	}
	
	/**
	 * Set the global DataModel, used when data is requested from static methods.
	 */
	static function set_inst(DataModel $inst) {
		self::$inst = $inst;
	}
	
	////////////////////////////////////////////////////////////////////////

	protected $customDataLists = array();
	
	function __get($class) {
		if(isset($this->customDataLists[$class])) {
			return clone $this->customDataLists[$class];
		} else {
			$list = DataList::create($class);
			$list->setDataModel($this);
			return $list;
		}
	}
	
	function __set($class, $item) {
		$item = clone $item;
		$item->setDataModel($this);
		$this->customDataLists[$class] = $item;
	}
	
}
