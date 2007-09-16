<?php
class GroupedDropdownField extends DropdownField {
  /**
  * @desc Returns a <select> tag containing all the appropriate <option> tags, with <optgroup> tags around the <option> tags as required
  */
  function Field() {
		// Initialisations
		$options = '';
		$classAttr = '';
    if($extraClass = trim($this->extraClass())) {
			$classAttr = "class=\"$extraClass\"";
		}
		foreach($this->source as $value => $title) {
			if(is_array($title)) {
        $options .= "<optgroup label=\"$value\">";
        foreach($title as $value2 => $title2) {
          $selected = $value2 == $this->value ? " selected=\"selected\"" : ""; 
			    $options .= "<option$selected value=\"$value2\">$title2</option>";
        }
        $options .= "</optgroup>";
      } else { // Fall back to the standard dropdown field
        $selected = $value == $this->value ? " selected=\"selected\"" : ""; 
			  $options .= "<option$selected value=\"$value\">$title</option>";
      }
		}

		$id = $this->id();

		return "<select $classAttr name=\"$this->name\" id=\"$id\">$options</select>";
  }
}
?>
