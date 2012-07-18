<?php
/**
 * Allows visibility of a group of fields to be toggled.
 *
 * @package forms
 * @subpackage fields-structural
 */
class ToggleCompositeField extends CompositeField {

	/**
	 * @var bool
	 */
	protected $startClosed = true;

	/**
	 * @var $int
	 */
	protected $headingLevel = 3;

	public function __construct($name, $title, $children) {
		$this->name = $name;
		$this->title = $title;

		parent::__construct($children);
	}

	public function FieldHolder($properties = array()) {
		Requirements::javascript(FRAMEWORK_DIR . '/thirdparty/jquery/jquery.js');
		Requirements::javascript(FRAMEWORK_DIR . '/thirdparty/jquery-ui/jquery-ui.js');
		Requirements::javascript(FRAMEWORK_DIR . '/thirdparty/jquery-entwine/dist/jquery.entwine-dist.js');
		Requirements::javascript(FRAMEWORK_DIR . '/javascript/ToggleCompositeField.js');
		Requirements::css(FRAMEWORK_DIR . '/thirdparty/jquery-ui-themes/smoothness/jquery.ui.css');

		$obj = $properties ? $this->customise($properties) : $this;
		return $obj->renderWith($this->getTemplates());
	}

	public function getAttributes() {
		if($this->getStartClosed()) {
			$class = 'ss-toggle ss-toggle-start-closed';
		} else {
			$class = 'ss-toggle';
		}

		return array_merge(
			$this->attributes,
			array(
				'id'    => $this->id(),
				'class' => $class . ' ' . $this->extraClass()
			)
		);
	}

	/**
	 * @return bool
	 */
	public function getStartClosed() {
		return $this->startClosed;
	}

	/**
	 * Controls whether the field is open or closed by default. By default the
	 * field is closed.
	 *
	 * @param bool $bool
	 */
	public function setStartClosed($bool) {
		$this->startClosed = (bool) $bool;
		return $this;
	}

	/**
	 * @return int
	 */
	public function getHeadingLevel() {
		return $this->headingLevel;
	}

	/**
	 * @param int $level
	 */
	public function setHeadingLevel($level) {
		$this->headingLevel = $level;
		return $this;
	}

	/**
	 * @deprecated 3.0
	 */
	public function startClosed($bool) {
		Deprecation::notice('3.0', 'Please use ToggleCompositeField->setStartClosed()');
		$this->setStartClosed($bool);
	}

}

