<?php
/**
 * A special type Int field used for primary keys.
 * 
 * @todo Allow for custom limiting/filtering of scaffoldFormField dropdown
 * 
 * @package sapphire
 * @subpackage model
 */
class PrimaryKey extends Int {
	/**
	 * @var DataObject 
	 */
	protected $object;

	public static $default_search_filter_class = 'ExactMatchMultiFilter';
	
	/**
	 * @param string $name
	 * @param DataOject $object The object that this is primary key for (should have a relation with $name)
	 */
	function __construct($name, $object) {
		$this->object = $object;
		parent::__construct($name);
	}
	
	public function scaffoldFormField($title = null, $params = null) {
		$titleField = ($this->object->hasField('Title')) ? "Title" : "Name";
		$map = new SQLMap($this->object->extendedSQL(), "ID", $titleField);
		return new DropdownField($this->name, $title, $map, null, null, ' ');
	}
}

?>