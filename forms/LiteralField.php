<?php

/**
 * This field lets you put an arbitrary piece of HTML into your forms.
 *
 * <code>
 * new LiteralField (
 *    $name = "literalfield",
 *    $content = '<b>some bold text</b> and <a href="http://silverstripe.com">a link</a>'
 * )
 * </code>
 *
 * @package forms
 * @subpackage fields-dataless
 */
class LiteralField extends DatalessField {
	/**
	 * @var string|FormField
	 */
	protected $content;

	/**
	 * @param string $name
	 * @param string|FormField $content
	 */
	public function __construct($name, $content) {
		$this->setContent($content);

		parent::__construct($name);
	}

	/**
	 * @param array $properties
	 *
	 * @return string
	 */
	public function FieldHolder($properties = array()) {
		if($this->content instanceof ViewableData) {
			$context = $this->content;

			if($properties) {
				$context = $context->customise($properties);
			}

			return $context->forTemplate();
		}

		return $this->content;
	}

	/**
	 * @param array $properties
	 *
	 * @return string
	 */
	public function Field($properties = array()) {
		return $this->FieldHolder($properties);
	}

	/**
	 * Sets the content of this field to a new value.
	 *
	 * @param string|FormField $content
	 *
	 * @return $this
	 */
	public function setContent($content) {
		$this->content = $content;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getContent() {
		return $this->content;
	}

	/**
	 * Synonym of {@link setContent()} so that LiteralField is more compatible with other field types.
	 *
	 * @param string|FormField $content
	 *
	 * @return $this
	 */
	public function setValue($content) {
		$this->setContent($content);

		return $this;
	}

	/**
	 * @return static
	 */
	public function performReadonlyTransformation() {
		$clone = clone $this;

		$clone->setReadonly(true);

		return $clone;
	}
}
