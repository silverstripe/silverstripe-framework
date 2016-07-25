<?php
/**
 * Lets you include a nested group of fields inside a template.
 * This control gives you more flexibility over form layout.
 *
 * Note: the child fields within a field group aren't rendered using FieldHolder().  Instead,
 * SmallFieldHolder() is called, which just prefixes $Field with a <label> tag, if the Title is set.
 *
 * <b>Usage</b>
 *
 * <code>
 * FieldGroup::create(
 * 	FieldGroup::create(
 * 		HeaderField::create('FieldGroup 1'),
 * 		TextField::create('Firstname')
 * 	),
 * 	FieldGroup::create(
 * 		HeaderField::create('FieldGroup 2'),
 * 		TextField::create('Surname')
 * 	)
 * )
 * </code>
 *
 * <b>Adding to existing FieldGroup instances</b>
 *
 * <code>
 * function getCMSFields() {
 * 	$fields = parent::getCMSFields();
 *
 * 	$fields->addFieldToTab(
 * 		'Root.Main',
 * 		FieldGroup::create(
 * 			TimeField::create("StartTime","What's the start time?"),
 * 			TimeField::create("EndTime","What's the end time?")
 * 		),
 * 		'Content'
 * 	);
 *
 * 	return $fields;
 *
 * }
 * </code>
 *
 * <b>Setting a title to a FieldGroup</b>
 *
 * <code>
 * $fields->addFieldToTab("Root.Main",
 * 		FieldGroup::create(
 * 			TimeField::create('StartTime','What's the start time?'),
 * 			TimeField::create('EndTime', 'What's the end time?')
 * 		)->setTitle('Time')
 * );
 * </code>
 *
 * @package forms
 * @subpackage fields-structural
 */
class FieldGroup extends CompositeField {

	protected $zebra;

	/**
	 * Create a new field group.
	 *
	 * Accepts any number of arguments.
	 *
	 * @param mixed $titleOrField Either the field title, list of fields, or first field
	 * @param mixed ...$otherFields Subsequent fields or field list (if passing in title to $titleOrField)
	 */
	public function __construct($titleOrField = null, $otherFields = null) {
		$title = null;
		if(is_array($titleOrField) || $titleOrField instanceof FieldList) {
			$fields = $titleOrField;

			// This would be discarded otherwise
			if($otherFields) {
				throw new InvalidArgumentException(
					'$otherFields is not accepted if passing in field list to $titleOrField'
				);
			}

		} else if(is_array($otherFields) || $otherFields instanceof FieldList) {
			$title = $titleOrField;
			$fields = $otherFields;

		} else {
			$fields = func_get_args();
			if(!is_object(reset($fields))) {
				$title = array_shift($fields);
			}
		}

		parent::__construct($fields);

		if($title) {
			$this->setTitle($title);
		}
	}

	/**
	 * Returns the name (ID) for the element.
	 * In some cases the FieldGroup doesn't have a title, but we still want
	 * the ID / name to be set. This code, generates the ID from the nested children
	 */
	public function getName(){
		if($this->name) {
			return $this->name;
		}

		if(!$this->title) {
			$fs = $this->FieldList();
			$compositeTitle = '';
			$count = 0;
			foreach($fs as $subfield){
				/** @var FormField $subfield */
				$compositeTitle .= $subfield->getName();
				if($subfield->getName()) $count++;
			}
			/** @skipUpgrade */
			if($count == 1) $compositeTitle .= 'Group';
			return preg_replace("/[^a-zA-Z0-9]+/", "", $compositeTitle);
		}

		return preg_replace("/[^a-zA-Z0-9]+/", "", $this->title);
	}

	/**
	 * Set an odd/even class
	 *
	 * @param string $zebra one of odd or even.
	 * @return $this
	 */
	public function setZebra($zebra) {
		if($zebra == 'odd' || $zebra == 'even') $this->zebra = $zebra;
		else user_error("setZebra passed '$zebra'.  It should be passed 'odd' or 'even'", E_USER_WARNING);
		return $this;
	}

	/**
	 * @return string
	 */
	public function getZebra() {
		return $this->zebra;
	}

	/**
	 * @return string
	 */
	public function Message() {
		$fs = array();
		$this->collateDataFields($fs);

		foreach($fs as $subfield) {
			if($m = $subfield->Message()) $message[] = rtrim($m, ".");
		}

		return (isset($message)) ? implode(",  ", $message) . "." : "";
	}

	/**
	 * @return string
	 */
	public function MessageType() {
		$fs = array();
		$this->collateDataFields($fs);

		foreach($fs as $subfield) {
			if($m = $subfield->MessageType()) $MessageType[] = $m;
		}

		return (isset($MessageType)) ? implode(".  ", $MessageType) : "";
	}
}
