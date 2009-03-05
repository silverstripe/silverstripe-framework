<?php
/**
 * This controller handles what happens when you visit the root URL.
 *
 * @package sapphire
 * @subpackage control
 */
class RootURLController extends Controller {
	protected static $is_at_root = false;
	
	public function init() {
		Director::set_site_mode('site');
		parent::init();
	}
	
	public function handleRequest($request) {
		self::$is_at_root = true;
		$this->pushCurrent();
		
		$this->init();

		// If the basic database hasn't been created, then build it.
		if(!DB::isActive() || !ClassInfo::hasTable('SiteTree')) {
			$this->response = new HTTPResponse();
			$this->redirect("dev/build?returnURL=");
			return $this->response;
		}

		$controller = new ModelAsController();
		
		$request = new HTTPRequest("GET", self::get_homepage_urlsegment().'/', $request->getVars(), $request->postVars());
		$request->match('$URLSegment//$Action', true);
			
		$result = $controller->handleRequest($request);

		$this->popCurrent();
		return $result;
	}

	/**
	 * Return the URL segment for the current HTTP_HOST value
	 */
	static function get_homepage_urlsegment() {
		$host = $_SERVER['HTTP_HOST'];
		$host = str_replace('www.','',$host);
		$SQL_host = str_replace('.','\\.',Convert::raw2sql($host));
        $homePageOBJ = DataObject::get_one("SiteTree", "HomepageForDomain REGEXP '(,|^) *$SQL_host *(,|\$)'");

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