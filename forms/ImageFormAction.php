<?php
/**
 * Action that uses an image instead of a button.
 *
 * @deprecated 3.0 Use FormAction with setAttribute('src', 'myimage.png') and custom JavaScript to achieve hover effect
 * @package forms
 * @subpackage actions
 */
class ImageFormAction extends FormAction {
	protected $image, $hoverImage, $className;
	
	/**
	 * Create a new action button.
	 * @param action The method to call when the button is clicked
	 * @param title The label on the button
	 * @param image The default image to display
	 * @param hoverImage The image to display on hover
	 * @param form The parent form, auto-set when the field is placed inside a form 
	 */
	function __construct($action, $title = "", $image = "", $hoverImage = null, $className = null, $form = null) {
		Deprecation::notice('3.0', "Use FormAction with setAttribute('src', 'myimage.png') and custom JavaScript to achieve hover effect", Deprecation::SCOPE_CLASS);

		$this->image = $image;
		$this->hoverImage = $hoverImage;
		$this->className = $className;
		parent::__construct($action, $title, $form);
	}

	function Field($properties = array()) {
		Requirements::javascript(THIRDPARTY_DIR . '/jquery/jquery.js');
		Requirements::javascript(FRAMEWORK_DIR . '/javascript/ImageFormAction.js');
		
		$classClause = '';
		if($this->className) $classClause = $this->className . ' ';
		if($this->hoverImage) $classClause .= 'rollover ';
		return "<input class=\"{$classClause}action\" id=\"" . $this->id() . "\" type=\"image\" name=\"{$this->name}\" src=\"{$this->image}\" title=\"{$this->title}\" alt=\"{$this->title}\" />";
	}
}
