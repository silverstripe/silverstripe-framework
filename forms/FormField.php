<?php
/**
 * Represents a field in a form. 
 *  
 * A FieldList contains a number of FormField objects which make up the whole of a form.
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

	/**
	 * @var Form
	 */
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
	 * @var $rightTitle string Used in SmallFieldHolder to force a right-aligned label, or in FieldHolder
	 * to create contextual label.
	 */
	protected $rightTitle;
	
	/**
	 * @var $leftTitle string Used in SmallFieldHolder() to force a left-aligned label with correct spacing.
	 * Please use $title for FormFields rendered with FieldHolder().
	 */
	protected $leftTitle;
	
	/**
	 * Stores a reference to the FieldList that contains this object.
	 * @var FieldList
	 */
	protected $containerFieldList;
	
	/**
	 * @var boolean
	 */
	protected $readonly = false;

	/**
	 * @var boolean
	 */
	protected $disabled = false;
	
	/**
	 * @var string custom validation message for the Field
	 */
	protected $customValidationMessage = "";
	
	/**
	 * Name of the template used to render this form field. If not set, then
	 * will look up the class ancestry for the first matching template where 
	 * the template name equals the class name.
	 *
	 * To explicitly use a custom template or one named other than the form 
	 * field see {@link setTemplate()}, {@link setFieldHolderTemplate()}
	 *
	 * @var string
	 */
	protected 
		$template,
		$fieldHolderTemplate,
		$smallFieldHolderTemplate;
		
	/**
	 * @var array All attributes on the form field (not the field holder).
	 * Partially determined based on other instance properties, please use {@link getAttributes()}.
	 */
	protected $attributes = array();

	private static $casting = array(
		'Message' => 'Text'
	);

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
	public static function name_to_label($fieldName) {
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
	 * Construct and return HTML tag.
	 */
	public static function create_tag($tag, $attributes, $content = null) {
		$preparedAttributes = '';
		foreach($attributes as $k => $v) {
			// Note: as indicated by the $k == value item here; the decisions over what to include in the attributes
			// can sometimes get finicky
			if(!empty($v) || $v === '0' || ($k == 'value' && $v !== null) ) {
				$preparedAttributes .= " $k=\"" . Convert::raw2att($v) . "\"";
			}
		}

		if($content || $tag != 'input') return "<$tag$preparedAttributes>$content</$tag>";
		else return "<$tag$preparedAttributes />";
	}

	/**
	 * Creates a new field.
	 *
	 * @param string $name The internal field name, passed to forms.
	 * @param string $title The human-readable field label.
	 * @param mixed $value The value of the field.
	 */
	public function __construct($name, $title = null, $value = null) {
		$this->name = $name;
		$this->title = ($title === null) ? self::name_to_label($name) : $title;

		if($value !== NULL) $this->setValue($value);

		parent::__construct();
	}
	
	/**
	 * Return a Link to this field
	 */
	public function Link($action = null) {
		return Controller::join_links($this->form->FormAction(), 'field/' . $this->name, $action);
	}
	
	/**
	 * Returns the HTML ID of the field - used in the template by label tags.
	 * The ID is generated as FormName_FieldName.  All Field functions should ensure
	 * that this ID is included in the field.
	 */
	public function ID() {
		$name = preg_replace('/(^-)|(-$)/', '', preg_replace('/[^A-Za-z0-9_-]+/', '-', $this->name));
		if($this->form) return $this->form->FormName() . '_' . $name;
		else return $name;
	}
	
	/**
	 * Returns the field name - used by templates.
	 * 
	 * @return string
	 */
	public function getName() {
		return $this->name;
	}

	/**
	 * Returns the field message, used by form validation.
	 * Use {@link setError()} to set this property.
	 * 
	 * @return string
	 */
	public function Message() {
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
	public function MessageType() {
		return $this->messageType;
	} 
	
	/**
	 * Returns the field value - used by templates.
	 */
	public function Value() {
		return $this->value;
	}
	
	/**
	 * Method to save this form field into the given data object.
	 * By default, makes use of $this->dataValue()
	 * 
	 * @param DataObjectInterface $record DataObject to save data into
	 */
	public function saveInto(DataObjectInterface $record) {
		if($this->name) {
			$record->setCastedField($this->name, $this->dataValue());
		}
	}
	
	/**
	 * Returns the field value suitable for insertion into the data object
	 */
	public function dataValue() {
		return $this->value;
	}
	
	/**
	 * Returns the field label - used by templates.
	 */
	public function Title() {
		return $this->title;
	}
	
	public function setTitle($val) {
		$this->title = $val;
		return $this;
	}

	/**
	 * Gets the contextual label than can be used for additional field description.
	 * Can be shown to the right or under the field in question.
	 *
	 * @return string Contextual label text.
	 */
	public function RightTitle() {
		return $this->rightTitle;
	}

	/**
	 * Sets the contextual label.
	 *
	 * @param $val string Text to set on the label.
	 */
	public function setRightTitle($val) {
		$this->rightTitle = $val;
		return $this;
	}

	public function LeftTitle() {
		return $this->leftTitle;
	}

	public function setLeftTitle($val) {
		$this->leftTitle = $val;
		return $this;
	}

	/**
	 * Compiles all CSS-classes. Optionally includes a "nolabel"-class
	 * if no title was set on the formfield.
	 * Uses {@link Message()} and {@link MessageType()} to add validatoin
	 * error classes which can be used to style the contained tags.
	 * 
	 * @return string CSS-classnames
	 */
	public function extraClass() {
		$classes = array();

		$classes[] = $this->Type();

		if($this->extraClasses) $classes = array_merge($classes, array_values($this->extraClasses));
		
		// Allow customization of label and field tag positioning
		if(!$this->Title()) $classes[] = "nolabel";
		
		// Allow custom styling of any element in the container based
		// on validation errors, e.g. red borders on input tags.
		// CSS-Class needs to be different from the one rendered
		// through {@link FieldHolder()}
		if($this->Message()) $classes[] .= "holder-" . $this->MessageType();
		
		return implode(' ', $classes);
	}
	
	/**
	 * Add one or more CSS-classes to the formfield-container. Multiple class 
	 * names should be space delimited.
	 * 
	 * @param string $class
	 */
	public function addExtraClass($class) {
		$classes = preg_split('/\s+/', $class);

		foreach ($classes as $class) {
			$this->extraClasses[$class] = $class;
		}

		return $this;
	}

	/**
	 * Remove one or more CSS-classes from the formfield-container.
	 * 
	 * @param string $class
	 */
	public function removeExtraClass($class) {
		$classes = preg_split('/\s+/', $class);

		foreach ($classes as $class) {
			unset($this->extraClasses[$class]);
		}
		
		return $this;
	}

	/**
	 * Set an HTML attribute on the field element, mostly an <input> tag.
	 * 
	 * Some attributes are best set through more specialized methods, to avoid interfering with built-in behaviour:
	 * - 'class': {@link addExtraClass()}
	 * - 'title': {@link setDescription()}
	 * - 'value': {@link setValue}
	 * - 'name': {@link setName}
	 * 
	 * CAUTION Doesn't work on most fields which are composed of more than one HTML form field:
	 * AjaxUniqueTextField, CheckboxSetField, ComplexTableField, CompositeField, ConfirmedPasswordField,
	 * CountryDropdownField, CreditCardField, CurrencyField, DateField, DatetimeField, FieldGroup, GridField,
	 * HtmlEditorField, ImageField, ImageFormAction, InlineFormAction, ListBoxField, etc.
	 * 
	 * @param string
	 * @param string
	 */
	public function setAttribute($name, $value) {
		$this->attributes[$name] = $value;
		return $this;
	}

	/**
	 * Get an HTML attribute defined by the field, or added through {@link setAttribute()}.
	 * Caution: Doesn't work on all fields, see {@link setAttribute()}.
	 * 
	 * @return string
	 */
	public function getAttribute($name) {
		$attrs = $this->getAttributes();
		return @$attrs[$name];
	}
	
	/**
	 * @return array
	 */
	public function getAttributes() {
		$attrs = array(
			'type' => 'text',
			'name' => $this->getName(),
			'value' => $this->Value(),			
			'class' => $this->extraClass(),
			'id' => $this->ID(),
			'disabled' => $this->isDisabled(),
		);
		
		if ($this->Required()) {
			$attrs['required'] = 'required';
			$attrs['aria-required'] = 'true';
		}

		return array_merge($attrs, $this->attributes);
	}

	/**
	 * @param Array Custom attributes to process. Falls back to {@link getAttributes()}.
	 * If at least one argument is passed as a string, all arguments act as excludes by name.
	 * @return string HTML attributes, ready for insertion into an HTML tag
	 */
	public function getAttributesHTML($attrs = null) {
		$exclude = (is_string($attrs)) ? func_get_args() : null;

		if(!$attrs || is_string($attrs)) $attrs = $this->getAttributes();

		// Remove empty
		$attrs = array_filter((array)$attrs, function($v) {
			return ($v || $v === 0 || $v === '0');
		}); 

		// Remove excluded
		if($exclude) $attrs = array_diff_key($attrs, array_flip($exclude));

		// Create markkup
		$parts = array();
		foreach($attrs as $name => $value) {
			$parts[] = ($value === true) ? "{$name}=\"{$name}\"" : "{$name}=\"" . Convert::raw2att($value) . "\"";
		}

		return implode(' ', $parts);
	}

	/**
	 * Returns a version of a title suitable for insertion into an HTML attribute
	 */
	public function attrTitle() {
		return Convert::raw2att($this->title);
	}
	/**
	 * Returns a version of a title suitable for insertion into an HTML attribute
	 */
	public function attrValue() {
		return Convert::raw2att($this->value);
	}
	
	/**
	 * Set the field value.
	 * 
	 * @param mixed $value
	 * @return FormField Self reference
	 */
	public function setValue($value) {
		$this->value = $value;
		return $this;
	}
	
	/**
	 * Set the field name
	 */
	public function setName($name) {
		$this->name = $name;
		return $this;
	}
	
	/**
	 * Set the container form.
	 * This is called whenever you create a new form and put fields inside it, so that you don't
	 * have to worry about linking the two.
	 */
	public function setForm($form) {
		$this->form = $form; 
		return $this;
	}
	
	/**
	 * Get the currently used form.
	 *
	 * @return Form
	 */
	public function getForm() {
		return $this->form; 
	}
	
	/**
	 * Return TRUE if security token protection is enabled on the parent {@link Form}.
	 *
	 * @return bool
	 */
	public function securityTokenEnabled() {
		$form = $this->getForm();
		if(!$form) return false;
		
		return $form->getSecurityToken()->isEnabled();
	}
	
	/**
	 * Sets the error message to be displayed on the form field
	 * Set by php validation of the form
	 */
	public function setError($message, $messageType) {
		$this->message = $message; 
		$this->messageType = $messageType; 
		
		return $this;
	}
	
	/**
	 * Set the custom error message to show instead of the default
	 * format of Please Fill In XXX. Different from setError() as
	 * that appends it to the standard error messaging
	 * 
	 * @param string Message for the error
	 */
	public function setCustomValidationMessage($msg) {
		$this->customValidationMessage = $msg;
		
		return $this;
	}
	
	/**
	 * Get the custom error message for this form field. If a custom
	 * message has not been defined then just return blank. The default
	 * error is defined on {@link Validator}.
	 *
	 * @todo Should the default error message be stored here instead
	 * @return string
	 */
	public function getCustomValidationMessage() {
		return $this->customValidationMessage;
	}

	/**
	 * Set name of template (without path or extension).
	 * Caution: Not consistently implemented in all subclasses,
	 * please check the {@link Field()} method on the subclass for support.
	 * 
	 * @param string
	 */
	public function setTemplate($template) {
		$this->template = $template;
		
		return $this;
	}
	
	/**
	 * @return string
	 */
	public function getTemplate() {
		return $this->template;
	}
	
	/**
	 * @return string
	 */
	public function getFieldHolderTemplate() {
		return $this->fieldHolderTemplate;
	}
	
	/**
	 * Set name of template (without path or extension) for the holder,
	 * which in turn is responsible for rendering {@link Field()}.
	 * 
	 * Caution: Not consistently implemented in all subclasses,
	 * please check the {@link Field()} method on the subclass for support.
	 * 
	 * @param string
	 */
	public function setFieldHolderTemplate($template) {
		$this->fieldHolderTemplate = $template;
		
		return $this;
	}
	
	/**
	 * @return string
	 */
	public function getSmallFieldHolderTemplate() {
		return $this->smallFieldHolderTemplate;
	}
	
	/**
	 * Set name of template (without path or extension) for the small holder,
	 * which in turn is responsible for rendering {@link Field()}.
	 * 
	 * Caution: Not consistently implemented in all subclasses,
	 * please check the {@link Field()} method on the subclass for support.
	 * 
	 * @param string
	 */
	public function setSmallFieldHolderTemplate($template) {
		$this->smallFieldHolderTemplate = $template;
		
		return $this;
	}
	
	/**
	 * Returns the form field - used by templates.
	 * Although FieldHolder is generally what is inserted into templates, all of the field holder
	 * templates make use of $Field.  It's expected that FieldHolder will give you the "complete"
	 * representation of the field on the form, whereas Field will give you the core editing widget,
	 * such as an input tag.
	 * 
	 * @param array $properties key value pairs of template variables
	 * @return string
	 */
	public function Field($properties = array()) {
		$obj = ($properties) ? $this->customise($properties) : $this;
		$this->extend('onBeforeRender', $this);
		return $obj->renderWith($this->getTemplates());
	}

	/**
	 * Returns a "field holder" for this field - used by templates.
	 * 
	 * Forms are constructed by concatenating a number of these field holders.
	 * The default field holder is a label and a form field inside a div.
	 * @see FieldHolder.ss
	 * 
	 * @param array $properties key value pairs of template variables
	 * @return string
	 */
	public function FieldHolder($properties = array()) {
		$obj = ($properties) ? $this->customise($properties) : $this;

		return $obj->renderWith($this->getFieldHolderTemplates());
	}

	/**
	 * Returns a restricted field holder used within things like FieldGroups.
	 *
	 * @param array $properties
	 *
	 * @return string
	 */
	public function SmallFieldHolder($properties = array()) {
		$obj = ($properties) ? $this->customise($properties) : $this;

		return $obj->renderWith($this->getSmallFieldHolderTemplates());
	}
	
	/**
	* Returns an array of templates to use for rendering {@link FieldH}
	 *
	 * @return array
	 */
	public function getTemplates() {
		return $this->_templates($this->getTemplate());
	}
	
	/**
	 * Returns an array of templates to use for rendering {@link FieldHolder}
	 *
	 * @return array
	 */
	public function getFieldHolderTemplates() {
		return $this->_templates(
			$this->getFieldHolderTemplate(), 
			'_holder'
		);
	}

	/**
	 * Returns an array of templates to use for rendering {@link SmallFieldHolder}
	 *
	 * @return array
	 */	
	public function getSmallFieldHolderTemplates() {
		return $this->_templates(
			$this->getSmallFieldHolderTemplate(), 
			'_holder_small'
		);
	}


	/**
	 * Generate an array of classname strings to use for rendering this form 
	 * field into HTML
	 *
	 * @param string $custom custom template (if set)
	 * @param string $suffix template suffix
	 *
	 * @return array $stack a stack of 
	 */
	private function _templates($custom = null, $suffix = null) {
		$matches = array();
		
		foreach(array_reverse(ClassInfo::ancestry($this)) as $className) {
			$matches[] = $className . $suffix;
			
			if($className == "FormField") break;
		}
		
		if($custom) array_unshift($matches, $custom);
		
		return $matches;
	}
	
	/**
	 * Returns true if this field is a composite field.
	 * To create composite field types, you should subclass {@link CompositeField}.
	 */
	public function isComposite() {
		return false;
	}

	/**
	 * Returns true if this field has its own data.
	 * Some fields, such as titles and composite fields, don't actually have any data.  It doesn't
	 * make sense for data-focused methods to look at them.  By overloading hasData() to return false,
	 * you can prevent any data-focused methods from looking at it.
	 *
	 * @see FieldList::collateDataFields()
	 */
	public function hasData() {
		return true;
	}

	/**
	 * @return boolean
	 */
	public function isReadonly() {
		return $this->readonly; 
	}

	/**
	 * Sets readonly-flag on form-field. Please use performReadonlyTransformation()
	 * to actually transform this instance.
	 * @param $bool boolean Setting "false" has no effect on the field-state.
	 */
	public function setReadonly($bool) {
		$this->readonly = $bool; 
		return $this;
	}
	
	/**
	 * @return boolean
	 */
	public function isDisabled() {
		return $this->disabled;
	}

	/**
	 * Sets disabed-flag on form-field. Please use performDisabledTransformation()
	 * to actually transform this instance.
	 * @param $bool boolean Setting "false" has no effect on the field-state.
	 */
	public function setDisabled($bool) {
		$this->disabled = $bool;
		return $this;
	}
	
	/**
	 * Returns a readonly version of this field
	 */
	public function performReadonlyTransformation() {
		$copy = $this->castedCopy('ReadonlyField');
		$copy->setReadonly(true);
		return $copy;
	}
	
	/**
	 * Return a disabled version of this field.
	 * Tries to find a class of the class name of this field suffixed with "_Disabled",
	 * failing that, finds a method {@link setDisabled()}.
	 *
	 * @return FormField
	 */
	public function performDisabledTransformation() {
		$disabledClassName = $this->class . '_Disabled';
		if(ClassInfo::exists($disabledClassName)) {
			$clone = $this->castedCopy($disabledClassName);
		} else {
			$clone = clone $this;
			$clone->setDisabled(true);
		}

			return $clone;
		}

	public function transform(FormTransformation $trans) {
		return $trans->transform($this);
	}
	
	public function hasClass($class){
		$patten = '/'.strtolower($class).'/i';
		$subject = strtolower($this->class." ".$this->extraClass());
		return preg_match($patten, $subject);
	}
	
	/**
	 * Returns the field type - used by templates.
	 * The field type is the class name with the word Field dropped off the end, all lowercase.
	 * It's handy for assigning HTML classes. Doesn't signify the <input type> attribute,
	 * see {link getAttributes()}.
	 * 
	 * @return string
	 */
	public function Type() {
		return strtolower(preg_replace('/Field$/', '', $this->class));	
	}

	/**
	 * @deprecated 3.2 Use FormField::create_tag()
	 */
	public function createTag($tag, $attributes, $content = null) {
		Deprecation::notice('3.2', 'Use FormField::create_tag()');
		return self::create_tag($tag, $attributes, $content);
			}

	/**
	 * Abstract method each {@link FormField} subclass must implement,
	 * determines whether the field is valid or not based on the value.
	 * @todo Make this abstract.
	 *
	 * @param Validator
	 * @return boolean
	 */
	public function validate($validator) {
		return true;
	}

	/**
	 * Describe this field, provide help text for it.
	 * By default, renders as a <span class="description"> 
	 * underneath the form field.
	 * 
	 * @return string Description
	 */
	public function setDescription($description) {
		$this->description = $description;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getDescription() {
		return $this->description;
	}
	
	public function debug() {
		return "$this->class ($this->name: $this->title : <font style='color:red;'>$this->message</font>)"
			. " = $this->value";
	}
	
	/**
	 * This function is used by the template processor.  If you refer to a field as a $ variable, it
	 * will return the $Field value.
	 */
	public function forTemplate() {
		return $this->Field();
	}
	
	/**
	 * @uses Validator->fieldIsRequired()
	 * @return boolean
	 */
	public function Required() {
		if($this->form && ($validator = $this->form->Validator)) {
			return $validator->fieldIsRequired($this->name);
		}
	}

	/**
	 * Set the FieldList that contains this field.
	 *
	 * @param FieldList $list
	 * @return FieldList
	 */
	public function setContainerFieldList($list) {
		$this->containerFieldList = $list;
		return $this;
	}

	public function rootFieldList() {
		if(is_object($this->containerFieldList)) return $this->containerFieldList->rootFieldList();
		else user_error("rootFieldList() called on $this->class object without a containerFieldList", E_USER_ERROR);
	}
	
	/**
	 * Returns another instance of this field, but "cast" to a different class.
	 * The logic tries to retain all of the instance properties,
	 * and may be overloaded by subclasses to set additional ones.
	 *
	 * Assumes the standard FormField parameter signature with
	 * its name as the only mandatory argument. Mainly geared towards
	 * creating *_Readonly or *_Disabled subclasses of the same type,
	 * or casting to a {@link ReadonlyField}.
	 *
	 * Does not copy custom field templates, since they probably won't apply to
	 * the new instance. 
	 * 
	 * @param  String $classOrCopy Class name for copy, or existing copy instance to update
	 * @return FormField
	 */
	public function castedCopy($classOrCopy) {
		$field = (is_object($classOrCopy)) ? $classOrCopy : new $classOrCopy($this->name);
		$field
			->setValue($this->value) // get value directly from property, avoid any conversions
			->setForm($this->form)
			->setTitle($this->Title())
			->setLeftTitle($this->LeftTitle())
			->setRightTitle($this->RightTitle())
			->addExtraClass($this->extraClass())
			->setDescription($this->getDescription());
			
		// Only include built-in attributes, ignore anything
		// set through getAttributes(), since those might change important characteristics
		// of the field, e.g. its "type" attribute.
		foreach($this->attributes as $k => $v) {
			$field->setAttribute($k, $v);
}
		$field->dontEscape = $this->dontEscape;

		return $field;
	}
	
}
