<?php

/**
 * This controller handles what happens when you visit the root URL
 */
class RootURLController extends Controller {
	protected static $is_at_root = false;
	
	/**
	 * Marks at that we are actually at the root URL before handing control over to another controller
	 */
	function index() {
		self::$is_at_root = true;
		Director::direct(self::get_homepage_urlsegment() . '/');
	}
	
	/**
	 * Return the URL segment for the current HTTP_HOST value
	 */
	static function get_homepage_urlsegment() {
		$host = $_SERVER['HTTP_HOST'];
		$host = str_replace('www.','',$host);
		$SQL_host = Convert::raw2sql($host);
		$homePageOBJ = DataObject::get_one("SiteTree", "HomepageForDomain = '$SQL_host'");

		if($homePageOBJ) {
			return $homePageOBJ->URLSegment;
		} else {
			return 'home';
		}
	}
	
	/**
	 * Returns true if we're currently on the root page and should be redirecting to the root
	 * Doesn't take into account actions, post vars, or get vars
	 */
	static function should_be_on_root(SiteTree $currentPage) {
		if(!self::$is_at_root) return self::get_homepage_urlsegment() == $currentPage->URLSegment;
		else return false;
	}
}

?>