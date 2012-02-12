<?php
/**
 * A simple CMS menu item
 * 
 * @package cms
 * @subpackage content
 */
class CMSMenuItem extends Object
{
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
	 * Create a new CMS Menu Item
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
	
}
