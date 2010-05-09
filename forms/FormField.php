<?php
/**
 * Represents a field in a form. 
 *  
 * A FieldSet contains a number of FormField objects which make up the whole of a form.
 * In addition to single fields, FormField objects can be "composite", for example, the {@link TabSet}
 * field.  Composite fields let us define complex forms without having to resort to custom HTML.
 * 
 * <b>Subclassing</b>
 * 
 * Define a {@link dataValue()} method that returns a value suitable for inserting into a single database field. 
 * For example, you might tidy up the format of a date or currency field.
 * Define {@link saveInto()} to totally customise saving. 
 * For example, data might be saved to the filesystem instead of the data record, 
 * or saved to a component of the data record instead of the data record itself.
 * 
 * @package forms
 * @subpackage core
 */
class FormField extends RequestHandler {
	protected $form;
	protected $name, $title, $value ,$message, $messageType, $extraClass;
	
	/**
	 * @var $description string Adds a "title"-attribute to the markup.
	 * @todo Implement in all subclasses
	 */
	protected $description;
	
	/**
	 * @var $extraClasses array Extra CSS-classes for the formfield-container
	 */
	protected $extraClasses;
	
	public $dontEscape;
	
	/**
	 * @var $rightTitle string Used in SmallFieldHolder() to force a right-aligned label.
	 */
	protected $rightTitle;
	
	/**
	 * @var $leftTitle string Used in SmallFieldHolder() to force a left-aligned label with correct spacing.
	 * Please use $title for FormFields rendered with FieldHolder().
	 */
	protected $leftTitle;
	
	/**
	 * Set the "tabindex" HTML attribute on the field.
	 *
	 * @var int
	 */
	protected $tabIndex;

	/**
	 * Stores a reference to the FieldSet that contains this object.
	 * @var FieldSet
	 */ 
	protected $containerFieldSet;
	
	/**
	 * @var $readonly boolean
	 */
	protected $readonly = false;

	/**
	 * @var $disabled boolean
	 */
	protected $disabled = false;
	
	/**
	 * @var Custom Validation Message for the Field
	 */
	protected $customValidationMessage = "";
	
	/**
	 * Create a new field.
	 * @param name The internal field name, passed to forms.
	 * @param title The field label.
	 * @param value The value of the field.
	 * @param form Reference to the container form
	 * @param maxLength The Maximum length of the attribute
	 */
	function __construct($name, $title = null, $value = null, $form = null, $rightTitle = null) {
		$this->name = $name;
		$this->title = ($title === null) ? $name : $title;

		if($value !== NULL) $this->setValue($value);
		if($form) $this->setForm($form);

		parent::__construct();
	}
	
	/**
	 * Return a Link to this field
	 */
	function Link($action = null) {
		return Controller::join_links($this->form->FormAction(), 'field/' . $this->name, $action);
	}
	
	/**
	 * Returns the HTML ID of the field - used in the template by label tags.
	 * The ID is generated as FormName_FieldName.  All Field functions should ensure
	 * that this ID is included in the field.
	 */
	function id() { 
		$name = ereg_replace('(^-)|(-$)','',ereg_replace('[^A-Za-z0-9_-]+','-',$this->name));
		if($this->form) return $this->form->FormName() . '_' . $name;
		else return $name;
	}
	
	/**
	 * Returns the field name - used by templates.
	 * 
	 * @return string
	 */
	function Name() {
		return $this->name;
	}
	
	function attrName() {
		return $this->name;
	}
	
	/** 
	 * Returns the field message, used by form validation.
	 * Use {@link setError()} to set this property.
	 * 
	 * @return string
	 */
	function Message() {
		return $this->message;
	} 
	
	/** 
	 * Returns the field message type, used by form validation.
	 * Arbitrary value which is mostly used for CSS classes
	 * in the rendered HTML, e.g. "required".
	 * Use {@link setError()} to set this property.
	 * 
	 * @return string
	 */
	function MessageType() {
		return $this->messageType;
	} 
	
	/**
	 * Returns the field value - used by templates.
	 */
	function Value() {
		return $this->value;
	}
	
	/**
	 * Method to save this form field into the given data object.
	 * By default, makes use of $this->dataValue()
	 */
	function saveInto(DataObjectInterface $record) {
		if($this->name) {
			$record->setCastedField($this->name, $this->dataValue());
		}
	}
	
	/**
	 * Returns the field value suitable for insertion into the data object
	 */
	function dataValue() { 
		return $this->value;
	}
	
	/**
	 * Returns the field label - used by templates.
	 */
	function Title() { 
		return $this->title;
	}
	
	function setTitle($val) { 
		$this->title = $val;
	}
	
	function RightTitle() {
		return $this->rightTitle;
	}
	
	function setRightTitle($val) { 
		$this->rightTitle = $val;
	}

	function LeftTitle() {
		return $this->leftTitle;
	}
	
	function setLeftTitle($val) { 
		$this->leftTitle = $val;
	}
	
	/**
	 * Set tabindex HTML attribute
	 * (defaults to none).
	 *
	 * @param int $index
	 */
	public function setTabIndex($index) {
		$this->tabIndex = $index;
	}
	
	/**
	 * Get tabindex (if previously set)
	 *
	 * @return int
	 */
	public function getTabIndex() {
		return $this->tabIndex;
	}

	/**
	 * Get tabindex HTML string
	 *
	 * @param int $increment Increase current tabindex by this value
	 * @return string
	 */
	protected function getTabIndexHTML($increment = 0) {
		$tabIndex = (int)$this->getTabIndex() + (int)$increment;
		return (is_numeric($tabIndex)) ? ' tabindex = "' . $tabIndex . '"' : '';
	}
	
	/**
	 * Compiles all CSS-classes. Optionally includes a "nolabel"-class
	 * if no title was set on the formfield.
	 * Uses {@link Message()} and {@link MessageType()} to add validatoin
	 * error classes which can be used to style the contained tags.
	 * 
	 * @return String CSS-classnames
	 */
	function extraClass() {
		$output = "";
		if(is_array($this->extraClasses)) {
			$output = " " . implode($this->extraClasses, " ");
		}
		
		// Allow customization of label and field tag positioning
		if(!$this->Title()) $output .= " nolabel";
		
		// Allow custom styling of any element in the container based
		// on validation errors, e.g. red borders on input tags.
		// CSS-Class needs to be different from the one rendered
		// through {@link FieldHolder()}
		if($this->Message()) $output .= " holder-" . $this->MessageType();
		
		return $output;
	}
	
	/**
	 * Add a CSS-class to the formfield-container.
	 * 
	 * @param $class String
	 */
	function addExtraClass($class) {
		$this->extraClasses[$class] = $class;
	}

	/**
	 * Remove a CSS-class from the formfield-container.
	 * 
	 * @param $class String
	 */
	function removeExtraClass($class) {
		if(isset($this->extraClasses) && array_key_exists($class, $this->extraClasses)) unset($this->extraClasses[$class]);
	}

	/**
	 * Returns a version of a title suitable for insertion into an HTML attribute
	 */
	function attrTitle() {
		return Convert::raw2att($this->title);
	}
	/**
	 * Returns a version of a title suitable for insertion into an HTML attribute
	 */
	function attrValue() {
		return Convert::raw2att($this->value);
	}
	
	/**
	 * Set the field value.
	 * Returns $this.
	 */
	function setValue($value) {
		$this->value = $value; return $this;
	}
	
	/**
	 * Set the field name
	 */
	function setName($name) {
		$this->name = $name;
	}
	
	/**
	 * Set the container form.
	 * This is called whenever you create a new form and put fields inside it, so that you don't
	 * have to worry about linking the two.
	 */
	function setForm($form) {
		$this->form = $form; 
	}
	
	/**
	 * Get the currently used form.
	 *
	 * @return Form
	 */
	function getForm() {
		return $this->form; 
	}
	
	/**
	 * Return TRUE if security token protection is enabled on the parent {@link Form}.
	 *
	 * @return bool
	 */
	public function securityTokenEnabled() {
		return $this->getForm() && $this->getForm()->securityTokenEnabled();
	}
	
	/**
	 * Sets the error message to be displayed on the form field
	 * Set by php validation of the form
	 */
	function setError($message,$messageType){
		$this->message = $message; 
		$this->messageType = $messageType; 
	}
	
	/**
	 * Set the custom error message to show instead of the default
	 * format of Please Fill In XXX. Different from setError() as
	 * that appends it to the standard error messaging
	 * 
	 * @param String Message for the error
	 */
	public function setCustomValidationMessage($msg) {
		$this->customValidationMessage = $msg;
	}
	
	/**
	 * Get the custom error message for this form field. If a custom
	 * message has not been defined then just return blank. The default
	 * error is defined on {@link Validator}.
	 *
	 * @todo Should the default error message be stored here instead
	 * @return String
	 */
	public function getCustomValidationMessage() {
		return $this->customValidationMessage;
	}
	
	/**
	 * Returns the form field - used by templates.
	 * Although FieldHolder is generally what is inserted into templates, all of the field holder
	 * templates make use of $Field.  It's expected that FieldHolder will give you the "complete"
	 * representation of the field on the form, whereas Field will give you the core editing widget,
	 * such as an input tag.
	 * 
	 * Our base FormField class just returns a span containing the value.  This should be overridden!
	 */
	function Field() {
		if($this->value) $value = $this->dontEscape ? ($this->reserveNL ? Convert::raw2xml($this->value) : $this->value) : Convert::raw2xml($this->value);
		else $value = '<i>(' . _t('FormField.NONE', 'none') . ')</i>';
	
		$attributes = array(
			'id' => $this->id(),
			'class' => 'readonly' . ($this->extraClass() ? $this->extraClass() : '')
		);
		
		$hiddenAttributes = array(
			'type' => 'hidden',
			'name' => $this->name,
			'value' => $this->value,
			'tabindex' => $this->getTabIndex()
		);
		
		$containerSpan = $this->createTag('span', $attributes, $value);
		$hiddenInput = $this->createTag('input', $hiddenAttributes);
		
		return $containerSpan . "\n" . $hiddenInput;
	}
	/**
	 * Returns a "Field Holder" for this field - used by templates.
	 * Forms are constructed from by concatenating a number of these field holders.  The default
	 * field holder is a label and form field inside a paragraph tag.
	 * 
	 * Composite fields can override FieldHolder to create whatever visual effects you like.  It's
	 * a good idea to put the actual HTML for field holders into templates.  The default field holder
	 * is the DefaultFieldHolder template.  This lets you override the HTML for specific sites, if it's
	 * necessary.
	 * 
	 * @todo Add "validationError" if needed.
	 */
	function FieldHolder() {
		$Title = $this->XML_val('Title');
		$Message = $this->XML_val('Message');
		$MessageType = $this->XML_val('MessageType');
		$RightTitle = $this->XML_val('RightTitle');
		$Type = $this->XML_val('Type');
		$extraClass = $this->XML_val('extraClass');
		$Name = $this->XML_val('Name');
		$Field = $this->XML_val('Field');
		
		// Only of the the following titles should apply
		$titleBlock = (!empty($Title)) ? "<label class=\"left\" for=\"{$this->id()}\">$Title</label>" : "";
		$rightTitleBlock = (!empty($RightTitle)) ? "<label class=\"right\" for=\"{$this->id()}\">$RightTitle</label>" : "";

		// $MessageType is also used in {@link extraClass()} with a "holder-" prefix
		$messageBlock = (!empty($Message)) ? "<span class=\"message $MessageType\">$Message</span>" : "";

		return <<<HTML
<div id="$Name" class="field $Type $extraClass">$titleBlock<div class="middleColumn">$Field</div>$rightTitleBlock$messageBlock</div>
HTML;
	}

	/**
	 * Returns a restricted field holder used within things like FieldGroups.
	 */
	function SmallFieldHolder() {
		$result = '';
		// set label
		if($title = $this->RightTitle()){
			$result .= "<label class=\"right\" for=\"" . $this->id() . "\">{$title}</label>\n";
		} elseif($title = $this->LeftTitle()) {
			$result .= "<label class=\"left\" for=\"" . $this->id() . "\">{$title}</label>\n";
		} elseif($title = $this->Title()) {
			$result .= "<label for=\"" . $this->id() . "\">{$title}</label>\n";
		}
		
		$result .= $this->Field();
		
		return $result;
	}

	
	/**
	 * Returns true if this field is a composite field.
	 * To create composite field types, you should subclass {@link CompositeField}.
	 */
	function isComposite() { return false; }
	
	/**
	 * Returns true if this field has its own data.
	 * Some fields, such as titles and composite fields, don't actually have any data.  It doesn't
	 * make sense for data-focused methods to look at them.  By overloading hasData() to return false,
	 * you can prevent any data-focused methods from looking at it.
	 *
	 * @see FieldSet::collateDataFields()
	 */
	function hasData() { return true; }

	/**
	 * @return boolean
	 */
	function isReadonly() { 
		return $this->readonly; 
	}

	/**
	 * Sets readonly-flag on form-field. Please use performReadonlyTransformation()
	 * to actually transform this instance.
	 * @param $bool boolean Setting "false" has no effect on the field-state.
	 */
	function setReadonly($bool) { 
		$this->readonly = $bool; 
	}
	
	/**
	 * @return boolean
	 */
	function isDisabled() { 
		return $this->disabled; 
	}

	/**
	 * Sets disabed-flag on form-field. Please use performDisabledTransformation()
	 * to actually transform this instance.
	 * @param $bool boolean Setting "false" has no effect on the field-state.
	 */
	function setDisabled($bool) { 
		$this->disabled = $bool; 
	}
	
	/**
	 * Returns a readonly version of this field
	 */
	function performReadonlyTransformation() {
		$field = new ReadonlyField($this->name, $this->title, $this->value);
		$field->addExtraClass($this->extraClass());
		$field->setForm($this->form);
		return $field;
	}
	
	/**
	 * Return a disabled version of this field
	 */
	function performDisabledTransformation() {
		$clone = clone $this;
		$disabledClassName = $clone->class . '_Disabled';
		if( ClassInfo::exists( $disabledClassName ) )
			return new $disabledClassName( $this->name, $this->title, $this->value );
		elseif($clone->hasMethod('setDisabled')){
			$clone->setDisabled(true);
			return $clone;
		}else{
			return $this->performReadonlyTransformation();
		}
	}
	
	function transform(FormTransformation $trans) {
		return $trans->transform($this);
	}
	
	function hasClass($class){
		$patten = '/'.strtolower($class).'/i';
		$subject = strtolower($this->class." ".$this->extraClass());
		return preg_match($patten, $subject);
	}
	
	/**
	 * Returns the field type - used by templates.
	 * The field type is the class name with the word Field dropped off the end, all lowercase.
	 * It's handy for assigning HTML classes.
	 */
	function Type() {return strtolower(ereg_replace('Field$','',$this->class)); }
	
	/**
	 * Construct and return HTML tag.
	 * 
	 * @todo Transform to static helper method.
	 */
	function createTag($tag, $attributes, $content = null) {
		$preparedAttributes = '';
		foreach($attributes as $k => $v) {
			// Note: as indicated by the $k == value item here; the decisions over what to include in the attributes can sometimes get finicky
			if(!empty($v) || $v === '0' || $k == 'value') $preparedAttributes .= " $k=\"" . Convert::raw2att($v) . "\"";
		}

		if($content || $tag != 'input') return "<$tag$preparedAttributes>$content</$tag>";
		else return "<$tag$preparedAttributes />";
	}
	
	/**
	 * javascript handler Functions for each field type by default
	 * formfield doesnt have a validation function
	 * 
	 * @todo shouldn't this be an abstract method?
	 */
	function jsValidation() {
	}
	
	/**
	 * Validation Functions for each field type by default
	 * formfield doesnt have a validation function
	 * 
	 * @todo shouldn't this be an abstract method?
	 */
	function validate() {
		return true;
	}

	/**
	 * Describe this field, provide help text for it.
	 * The function returns this so it can be used like this:
	 * $action = FormAction::create('submit', 'Submit')->describe("Send your changes to be approved")
	 */
	function describe($description) {
		$this->description = $description;
		return $this;
	}
	
	function debug() {
		return "$this->class ($this->name: $this->title : <font style='color:red;'>$this->message</font>) = $this->value";
	}
	
	/**
	 * This function is used by the template processor.  If you refer to a field as a $ variable, it
	 * will return the $Field value.
	 */
	function forTemplate() {
		return $this->Field();
	}
	
	/**
	 * @uses Validator->fieldIsRequired()
	 * @return boolean
	 */
	function Required() {
		if($this->form && ($validator = $this->form->Validator)) {
			return $validator->fieldIsRequired($this->name);
		}
	}
	
	/**
	 * Takes a fieldname and converts camelcase to spaced
	 * words. Also resolves combined fieldnames with dot syntax
	 * to spaced words.
	 * 
	 * Examples:
	 * - 'TotalAmount' will return 'Total Amount'
	 * - 'Organisation.ZipCode' will return 'Organisation Zip Code'
	 *
	 * @param string $fieldName
	 * @return string
	 */
	public function name_to_label($fieldName) {
		if(strpos($fieldName, '.') !== false) {
			$parts = explode('.', $fieldName);
			$label = $parts[count($parts)-2] . ' ' . $parts[count($parts)-1];
		} else {
			$label = $fieldName;
		}
		$label = preg_replace("/([a-z]+)([A-Z])/","$1 $2", $label);
		
		return $label;
	}
	
	/**
	 * Set the fieldset that contains this field. 
	 *
	 * @param FieldSet $containerFieldSet
	 */ 
	function setContainerFieldSet($containerFieldSet) {
		$this->containerFieldSet = $containerFieldSet;
	}
	
	function rootFieldSet() {
		if(is_object($this->containerFieldSet)) return $this->containerFieldSet->rootFieldSet();
		else user_error("rootFieldSet() called on $this->class object without a containerFieldSet", E_USER_ERROR);
	}
	
}
?>