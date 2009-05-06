<?php
/**
 * A special type Int field used for foreign keys in has_one relationships.
 * @uses ImageField
 * @uses SimpleImageField
 * @uses FileIFrameField
 * @uses DropdownField
 * 
 * @param string $name
 * @param DataOject $object The object that the foreign key is stored on (should have a relation with $name) 
 * 
 * @package sapphire
 * @subpackage model
 */
class ForeignKey extends Int {

	/**
	 * @var DataObject 
	 */
	protected $object;

	public static $default_search_filter_class = 'ExactMatchMultiFilter';
	
	function __construct($name, $object = null) {
		$this->object = $object;
		parent::__construct($name);
	}
	
	public function scaffoldFormField($title = null, $params = null) {
		$relationName = substr($this->name,0,-2);
		$hasOneClass = $this->object->has_one($relationName);

		if($hasOneClass && singleton($hasOneClass) instanceof Image) {
			if(isset($params['ajaxSafe']) && $params['ajaxSafe']) {
				$field = new ImageField($relationName, $title, $this->value);
			} else {
				$field = new SimpleImageField($relationName, $title, $this->value);
			}
		} elseif($hasOneClass && singleton($hasOneClass) instanceof File) {
			if(isset($params['ajaxSafe']) && $params['ajaxSafe']) {
				$field = new FileIFrameField($relationName, $title, $this->value);
			} else {
				$field = new FileField($relationName, $title, $this->value);
			}
		} else {
			$titleField = (singleton($hasOneClass)->hasField('Title')) ? "Title" : "Name";
			$map = new SQLMap(singleton($hasOneClass)->extendedSQL(), "ID", $titleField);
			$field =  new DropdownField($this->name, $title, $map, null, null, ' ');
		}
		
		return $field;
	}
}

?>
