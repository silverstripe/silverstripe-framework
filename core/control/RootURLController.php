<?php
/**
 * @package sapphire
 * @subpackage control
 */
class RootURLController extends Controller {
	
	/**
	 * @var bool
	 */
	protected static $is_at_root = false;
	
	/**
	 * @var string
	 */
	protected static $default_homepage_link = 'home';
	
	/**
	 * Get the full form (e.g. /home/) relative link to the home page for the current HTTP_HOST value. Note that the
	 * link is trimmed of leading and trailing slashes before returning to ensure consistency.
	 *
	 * @return string
	 */
	public static function get_homepage_link() {
		$host     = str_replace('www.', null, $_SERVER['HTTP_HOST']);
		$SQL_host = Convert::raw2sql($host);
		
		$candidates = DataObject::get('SiteTree', "\"HomepageForDomain\" LIKE '%$SQL_host%'");
		
		if($candidates) foreach($candidates as $candidate) {
			if(preg_match('/(,|^) *' . preg_quote($host) . ' *(,|$)/', $candidate->HomepageForDomain)) {
				return trim($candidate->RelativeLink(true), '/');
			}
		}
		
		if(Object::has_extension('SiteTree', 'Translatable')) {
			if($link = Translatable::get_homepage_link_by_locale(Translatable::get_current_locale())) return $link;
		}
		
		return self::get_default_homepage_link();
	}
	
	/**
	 * Gets the link that denotes the homepage if there is not one explicitly defined for this HTTP_HOST value.
	 *
	 * @return string
	 */
	public static function get_default_homepage_link() {
		return self::$default_homepage_link;
	}
	
	/**
	 * Returns TRUE if a request to a certain page should be redirected to the site root (i.e. if the page acts as the
	 * home page).
	 *
	 * @param SiteTree $page
	 * @return bool
	 */
	public static function should_be_on_root(SiteTree $page) {
		if(!self::$is_at_root && self::get_homepage_link() == trim($page->RelativeLink(true), '/')) {
			return !(
				$page->hasExtension('Translatable') && $page->Locale && $page->Locale != Translatable::default_locale()
			);
		}
		
		return false;
	}
	
	/**
	 * @deprecated 2.4 Use {@link RootURLController::get_homepage_link()}
	 */
	public static function get_homepage_urlsegment() {
		user_error (
			'RootURLController::get_homepage_urlsegment() is deprecated, please use get_homepage_link()', E_USER_NOTICE
		);
		
		return self::get_homepage_link();
	}
	
	/**
	 * @param HTTPRequest $request
	 * @return HTTPResponse
	 */
	public function handleRequest(HTTPRequest $request) {
		self::$is_at_root = true;
		
		$this->pushCurrent();
		$this->init();
		
		if(!DB::isActive() || !ClassInfo::hasTable('SiteTree')) {
			$this->response = new HTTPResponse();
			$this->response->redirect('dev/build/?returnURL=');
			
			return $this->response;
		}
			
		$request = new HTTPRequest (
			$request->httpMethod(), self::get_homepage_link() . '/', $request->getVars(), $request->postVars()
		);
		$request->match('$URLSegment//$Action', true);
		
		$controller = new ModelAsController();
		$result     = $controller->handleRequest($request);
		
		$this->popCurrent();
		return $result;
	}
	
}
