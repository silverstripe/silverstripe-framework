<?php

/**
 * @package forms
 * @subpackage fields-formattedinput
 */

/**
 * A WYSIWYG editor field, powered by tinymce.
 * tinymce editor fields are created from <textarea> tags which are then converted with javascript.
 * The {@link Requirements} system is used to ensure that all necessary javascript is included.
 * @package forms
 * Caution: Only works within the CMS with a global tinymce-menubar, see {@link CMSMain}
 * 
 * @subpackage fields-formattedinput
 * @deprecated It's not clear that this field works properly.  Just use {@link HtmlEditorField}.
 */
class HtmlOneLineField extends TextField {
	/**
	 * Returns the a <textarea> field with tinymce="true" set on it
	 */
	function Field() {
		Requirements::javascript(MCE_ROOT . "tiny_mce_src.js");
		Requirements::javascript("jsparty/tiny_mce_improvements.js");

		// Don't allow unclosed tags - they will break the whole application ;-)		
		$cleanVal = $this->value;
		$lPos = strrpos($cleanVal,'<');
		$rPos = strrpos($cleanVal,'>');
		if(($lPos > $rPos) || ($rPos === false && $lPos !== false)) $cleanVal .= '>';
		
		return "<textarea class=\"htmleditor\" tinymce_oneline=\"true\" id=\"" . $this->id() . "\" name=\"{$this->name}\" rows=\"1\">$cleanVal</textarea>";
	}
}

?>