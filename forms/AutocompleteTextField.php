<?php

/**
 * @package forms
 * @subpackage fields-formattedinput
 */

/**
 * Autocompleting text field, using script.aculo.us
 * @package forms
 * @subpackage fields-formattedinput
 */
class AutocompleteTextField extends TextField {
	
	protected $optionsURL;
	
	function __construct($name, $title = null, $optionsURL, $value = "", $maxLength = null){
		$this->optionsURL = $optionsURL;		
	
		parent::__construct($name, $title, $value, $maxLength);
	}
	
	function extraClass() {
		return parent::extraClass() . " autocomplete";
	}
	
	function Field() {
		// Requirements::javascript('sapphire/javascript/AutocompleteTextField.js');
		$extraClass = $this->extraClass();
		
		$fieldSize = $this->maxLength ? min( $this->maxLength, 30 ) : 30;
		
		if($this->maxLength) {
			return "<input class=\"text maxlength$extraClass\" type=\"text\" id=\"" . $this->id() . "\" name=\"{$this->name}\" value=\"" . $this->attrValue() . "\" maxlength=\"$this->maxLength\" size=\"$fieldSize\" /><div id=\"" . $this->id() . "_Options\" class=\"autocompleteoptions\"></div>";
		} else {
			return "<input class=\"text$extraClass\" type=\"text\" id=\"" . $this->id() . "\" name=\"{$this->name}\" value=\"" . $this->attrValue() . "\" /><div id=\"" . $this->id() . "_Options\" class=\"autocompleteoptions\"></div>"; 
		}
	}
	
	function FieldHolder() {
		$holder = parent::FieldHolder();
		
		$id = $this->id();
		
		$holder .= <<<JS
			<script type="text/javascript">
				new Ajax.Autocompleter( '$id', '{$id}_Options', '{$this->optionsURL}', { afterUpdateElement : function(el) { if(el.onajaxupdate) { el.onajaxupdate(); } } } );
			</script>
JS;

		return $holder;
	}
}
?>