<?php

/**
 * Field that generates a heading tag.
 *
 * This can be used to add extra text in your forms.
 *
 * @package forms
 * @subpackage fields-dataless
 */
class HeaderField extends DatalessField {

	/**
	 * The level of the <h1> to <h6> HTML tag.
	 *
	 * @var int
	 */
	protected $headingLevel = 2;

	/**
	 * @param string $name
	 * @param null|string $title
	 * @param int $headingLevel
	 */
	public function __construct($name, $title = null, $headingLevel = 2) {
		// legacy handling:
		// $title, $headingLevel...
		$args = func_get_args();

		if(!isset($args[1]) || is_numeric($args[1])) {
			if(isset($args[0])) {
				$title = $args[0];
			}

			// Prefix name to avoid collisions.
			$name = 'HeaderField' . $title;

			if(isset($args[1])) {
				$headingLevel = $args[1];
			}
		}

		$this->setHeadingLevel($headingLevel);

		parent::__construct($name, $title);
	}

	/**
	 * @return int
	 */
	public function getHeadingLevel() {
		return $this->headingLevel;
	}

	/**
	 * @param int $headingLevel
	 *
	 * @return $this
	 */
	public function setHeadingLevel($headingLevel) {
		$this->headingLevel = $headingLevel;

		return $this;
	}

	/**
	 * {@inheritdoc}
	 */
	public function getAttributes() {
		return array_merge(
			parent::getAttributes(),
			array(
				'id' => $this->ID(),
				'class' => $this->extraClass(),
			)
		);
	}

	/**
	 * @return null
	 */
	public function Type() {
		return null;
	}
}
