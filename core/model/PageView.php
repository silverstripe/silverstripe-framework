<?php
/**
 * @package cms
 */

/**
 * Data object that logs statistics for any page view in the system.
 * 
 * Inbound links from external websites are distinquished by a 'true'
 * value in the FromExternal field.
 * 
 * The referring urls are recorded in the Referrer field.
 * 
 * Information about the users browser version and operating system is also recorded.
 * 
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
		if(Statistics::$browscap_enabled) {
			$browscap = new Browscap();
			$this->hitdata = $browscap->getBrowser(null, true);
		}
	}
	
	/**
	 * gathers data for this page view and writes
	 * it to the data source.
	 */
	function record() {
		$this->init();
		$this->recordBrowser();
		$this->recordOS();
		$this->recordUserID();
		$this->recordPageID();
		$this->recordIP();
		$this->recordFromExternal();
		$this->recordReferrer();
		$this->write(true);
	}

	private function recordFromExternal() {
		$http_host = "http://".$_SERVER['HTTP_HOST'];
		if (isset($_SERVER['HTTP_REFERER']) && !strstr($_SERVER['HTTP_REFERER'], $http_host) && $_SERVER['HTTP_REFERER'] != null)
			$this->FromExternal = 1;
	}

	private function recordBrowser() {
		if (isset($this->hitdata['Browser']))
			$this->Browser = $this->hitdata['Browser'];

		if (isset($this->hitdata['Version']))
			$this->BrowserVersion = $this->hitdata['Version'];
	}
	
	private function recordReferrer() {
		if(isset($_SERVER['HTTP_REFERER'])) $this->Referrer = $_SERVER['HTTP_REFERER'];
	}

	private function recordOS() {
		if(isset($this->hitdata['Platform']))
			$this->OS = $this->hitdata['Platform'];
	}

	private function recordUserID() {
		$isLogged = Session::get('loggedInAs');
		$id = ($isLogged) ? $isLogged : -1;
		$this->UserID = $id;
	}

	private function recordPageID() {
		$currentPage = Director::currentPage();
		if ($currentPage) $this->PageID = $currentPage->ID;
	}

	private function recordIP() {
		$this->IP = (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) 
					 ? $_SERVER['HTTP_X_FORWARDED_FOR'] : $_SERVER['REMOTE_ADDR'];
	}
	
}

?>