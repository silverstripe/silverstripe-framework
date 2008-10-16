<?php
/**
 * Lets you include a nested group of fields inside a template.
 * This control gives you more flexibility over form layout.
 * 
 * Note: the child fields within a field group aren't rendered using DefaultFieldHolder.  Instead,
 * SmallFieldHolder() is called, which just prefixes $Field with a <label> tag, if the Title is set.
 * @deprecated 2.3
 * @package forms
 * @subpackage fields-structural
 */
class HiddenFieldGroup extends FieldGroup {
	
	/**
	 * Returns a set of <span class="subfield"> tags, each containing a sub-field.
	 * You can also use <% control FieldSet %>, if you'd like more control over the generated HTML
	 */
	function Field() {
		$fs = $this->FieldSet();
		$content = "<span class=\"fieldgroup\">";
		foreach($fs as $subfield) {
			$content .= $subfield->SmallFieldHolder() . " ";
		}
		$content .= "</span>";
		
		return $content;
	}
	function FieldHolder() {
		return $this->renderWith("HiddenFieldHolder");
	}
	
}

?>