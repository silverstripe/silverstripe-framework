<?php
/**
 * A special type Int field used for foreign keys in has_one relationships.
 * @uses ImageField
 * @uses SimpleImageField
 * @uses FileIFrameField
 * @uses DropdownField
 * 
 * @param string $name
 * @param DataObject $object The object that the foreign key is stored on (should have a relation with $name) 
 * 
 * @package framework
 * @subpackage model
 */
class ForeignKey extends Int {

	/**
	 * @var DataObject 
	 */
	protected $object;

	private static $default_search_filter_class = 'ExactMatchFilter';
	
	public function __construct($name, $object = null) {
		$this->object = $object;
		parent::__construct($name);
	}
	
	public function scaffoldFormField($title = null, $params = null) {
		$relationName = substr($this->name,0,-2);
		$hasOneClass = $this->object->has_one($relationName);

		if($hasOneClass && singleton($hasOneClass) instanceof Image) {
			$field = new UploadField($relationName, $title);
			$field->getValidator()->setAllowedExtensions(array('jpg', 'jpeg', 'png', 'gif'));
		} elseif($hasOneClass && singleton($hasOneClass) instanceof File) {
			$field = new UploadField($relationName, $title);
		} else {
			$titleField = (singleton($hasOneClass)->hasField('Title')) ? "Title" : "Name";
			$list = DataList::create($hasOneClass);
			// Don't scaffold a dropdown for large tables, as making the list concrete
			// might exceed the available PHP memory in creating too many DataObject instances
			if($list->count() < 100) {
				$field = new DropdownField($this->name, $title, $list->map('ID', $titleField));
				$field->setEmptyString(' ');
			} else {
				$field = new NumericField($this->name, $title);
			}
			
		}
		
		return $field;
	}
}


