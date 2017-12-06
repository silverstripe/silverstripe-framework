<?php

/**
 * A simple CMS menu item.
 *
 * Items can be added to the menu through custom {@link LeftAndMainExtension}
 * classes and {@link CMSMenu}.
 *
 * @see CMSMenu
 *
 * @package framework
 * @subpackage admin
 */
class CMSMenuItem extends Object {

	/**
	 * The (translated) menu title
	 * @var string $title
	 */
	public $title;

	/**
	 * Relative URL
	 * @var string $url
	 */
	public $url;

	/**
	 * Parent controller class name
	 * @var string $controller
	 */
	public $controller;

	/**
	 * Menu priority (sort order)
	 * @var integer $priority
	 */
	public $priority;

	/**
	 * Attributes for the link. For instance, custom data attributes or standard
	 * HTML anchor properties.
	 *
	 * @var array
	 */
	protected $attributes = array();

	/**
	 * Create a new CMS Menu Item
	 *
	 * @param string $title
	 * @param string $url
	 * @param string $controller Controller class name
	 * @param integer $priority The sort priority of the item
	 */
	public function __construct($title, $url, $controller = null, $priority = -1) {
		$this->title = $title;
		$this->url = $url;
		$this->controller = $controller;
		$this->priority = $priority;

		parent::__construct();
	}

	/**
	 * @param array $attributes
	 */
	public function setAttributes($attributes) {
		$this->attributes = $attributes;
	}

	/**
	 * @param array
	 *
	 * @return HTML
	 */
	public function getAttributesHTML($attrs = null) {
		$exclude = (is_string($attrs)) ? func_get_args() : null;

		if(!$attrs || is_string($attrs)) {
			$attrs = $this->attributes;
		}

		// Remove empty
		$attrs = array_filter((array)$attrs, function($v) {
			return ($v || $v === 0 || $v === '0');
		});

		// Create markkup
		$parts = array();

		foreach($attrs as $name => $value) {
			$parts[] = ($value === true) ? "{$name}=\"{$name}\"" : "{$name}=\"" . Convert::raw2att($value) . "\"";
		}

		return implode(' ', $parts);
	}
}
