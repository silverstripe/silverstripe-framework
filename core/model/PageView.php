<?php
/**
 * @package cms
 */

/**
 * Data object that represents any page view in the system.
 * @package cms
 */
class PageView extends DataObject {

	static $db = array(
		"IP" => "Varchar(255)",
		"Browser" => "Varchar(255)",
		"BrowserVersion" => "Decimal",
		"FromExternal" => "Boolean",
		"Referrer" => "Varchar(255)",
		"SearchEngine" => "Boolean",
		"Keywords" => "Varchar(255)",
		"OS" => "Varchar(255)"
	);

	static $has_one = array(
		"Page" => "SiteTree",
		"User" => "Member"
	);

	static $has_many = array();

	static $many_many = array();

	static $belongs_many_many = array();

	static $defaults = array();

	protected $hitdata = null;

	function init() {
		$browscap = new Browscap();
		$this->hitdata = $browscap->getBrowser(null, true);
	}

	function record() {
		$this->init();
		$this->setBrowser();
		$this->setOS();
		$this->setUserID();
		$this->setPageID();
		$this->setIP();
		$this->write(true);
	}

	function sanitize($str) {
		//TODO
		return $str;
	}

	function setBrowser() {
		if(isset($this->hitdata['Browser']))
			$this->setField('Browser', $this->hitdata['Browser']);

		if(isset($this->hitdata['Version']))
			$this->setField('BrowserVersion', $this->hitdata['Version']);
	}

	function setOS() {
		if(isset($this->hitdata['Platform']))
			$this->setField('OS', $this->hitdata['Platform']);
	}

	function setUserID() {
		$isLogged = Session::get('loggedInAs');
		if($isLogged) {
			$id = $isLogged;
		} else {
			$id = -1;
		}
		$this->setField('UserID', $id);
	}

	function setPageID() {
		$currentPage = Director::currentPage();
		if($currentPage)
			$this->setField('PageID', $currentPage->ID);
	}

	function setIP() {
		if(isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
			$ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
		} else {
			$ip = $_SERVER['REMOTE_ADDR'];
		}
		$this->setField('IP', $ip);
	}

}


?>