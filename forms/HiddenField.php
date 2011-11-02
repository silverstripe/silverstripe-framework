<?php
/**
 * Hidden field.
 * @package forms
 * @subpackage fields-dataless
 */
class HiddenField extends FormField {
	/**
	 * Returns an hidden input field, class="hidden" and type="hidden"
	 */
	function Field() {
		$extraClass = $this->extraClass();
		//if($this->name=="ShowChooseOwn")Debug::show($this->value);
		return "<input class=\"hidden$extraClass\" type=\"hidden\" id=\"" . $this->id() . "\" name=\"{$this->name}\" value=\"" . $this->attrValue() . "\" />";
	}
	function FieldHolder() {
		return $this->Field();
	}
	function performReadonlyTransformation() {
		$clone = clone $this;
		$clone->setReadonly(true);
		return $clone;
	}
	
	function IsHidden() {
		return true;
	}

	static function create($name) { return new HiddenField($name); }
}

?>