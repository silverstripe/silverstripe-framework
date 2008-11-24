<?php
/**
 * Single checkbox field.
 * @package forms
 * @subpackage fields-basic
 */
class CheckboxField extends FormField {
	/**
	 * Returns a single checkbox field - used by templates.
	 *
	 * Shouldn't this have a value?
	 */
	 
	protected $disabled;
	
	function Field() {
		$attributes = array(
			'type' => 'checkbox',
			'class' => ($this->extraClass() ? $this->extraClass() : ''),
			'id' => $this->id(),
			'name' => $this->Name(),
			'checked' => $this->value ? 'checked' : '',
			'tabindex' => $this->getTabIndex()
		);
		
		if($this->disabled) $attributes['disabled'] = 'disabled';
		
		return $this->createTag('input', $attributes);
	}
	
	
	function dataValue() {
		return $this->value ? 1 : 0;
	}
	
	/**
	 * Checkboxes use the RightLabelledFieldHolder template, to put the field on the left
	 * and the label on the right.  See {@link FormField::FieldHolder} for more information about
	 * how FieldHolder works. 
	 */
	function FieldHolder() {
		if($this->labelLeft) {
			return parent::FieldHolder();
		} else {
			extract($this->getXMLValues(array( 'Name', 'Field', 'Title', 'Message', 'MessageType' )));
			$messageBlock = isset($Message) ? "<span class=\"message $MessageType\">$Message</span>" : '';
			$Type = $this->XML_val('Type');
			return <<<HTML
<p id="$Name" class="field $Type">
	$Field
	<label class="right" for="{$this->id()}">$Title</label>
	$messageBlock
</p>
HTML;
			
		}
	}

	function useLabelLeft( $labelLeft = true ) {
		$this->labelLeft = $labelLeft;
	}

	/**
	 * Returns a restricted field holder used within things like FieldGroups
	 */
	function SmallFieldHolder() {
		$result = $this->Field();
		if($t = $this->Title()) {
			$result .= "<label for=\"" . $this->id() ."\">$t</label> ";
		}
		return $result;
	}

	/**
	 * Returns a readonly version of this field
	 */
	 
	function performReadonlyTransformation() {
		$field = new CheckboxField_Readonly($this->name, $this->title, $this->value ? 'Yes' : 'No');
		$field->setForm($this->form);
		return $field;	
	}
	
	function performDisabledTransformation() {
		$clone = clone $this;
		$clone->setDisabled(true);
		return $clone;
	}
}

/**
 * Readonly version of a checkbox field - "Yes" or "No".
 * @package forms
 * @subpackage fields-basic
 */
class CheckboxField_Readonly extends ReadonlyField {
	function performReadonlyTransformation() {
		return clone $this;
	}
	
	function setValue($val) {
		$this->value = ($val) ? 'Yes' : 'No';
	}
}

/**
 * Single checkbox field, disabled
 * @package forms
 * @subpackage fields-basic
 */
class CheckboxField_Disabled extends CheckboxField {
	
	protected $disabled = true;
	
	/**
	 * Returns a single checkbox field - used by templates.
	 */
	function Field() {
		$attributes = array(
			'type' => 'checkbox',
			'class' => 'text' . ($this->extraClass() ? $this->extraClass() : ''),
			'id' => $this->id(),
			'name' => $this->Name(),
			'tabindex' => $this->getTabIndex(),
			'checked' => ($this->value) ? 'checked' : false,
			'disabled' => 'disabled' 
		);
		
		return $this->createTag('input', $attributes);
	}
}
?>