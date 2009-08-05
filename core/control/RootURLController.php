<?php
/**
 * This controller handles what happens when you visit the root URL.
 *
 * @package sapphire
 * @subpackage control
 */
class RootURLController extends Controller {
	
	/**
	 * @var boolean $is_at_root
	 */
	protected static $is_at_root = false;
	
	/**
	 * @var string $default_homepage_urlsegment Defines which URLSegment value on a {@link SiteTree} object
	 * is regarded as the correct "homepage" if the requested URI doesn't contain
	 * an explicit segment. E.g. http://mysite.com should show http://mysite.com/home.
	 */
	protected static $default_homepage_urlsegment = 'home';
	
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
	 * 
	 * @param string $locale
	 * @return string
	 */
	static function get_homepage_urlsegment() {
		$urlSegment = '';
		
		$homePageOBJ = null;

		$host = $_SERVER['HTTP_HOST'];
		$host = str_replace('www.','',$host);
		$SQL_host = Convert::raw2sql($host);
		
        $homePageOBJs = DataObject::get("SiteTree", "HomepageForDomain LIKE '%$SQL_host%'");
		if($homePageOBJs) foreach($homePageOBJs as $candidateObj) {
			if(preg_match('/(,|^) *' . preg_quote($host) . ' *(,|$)/', $candidateObj->HomepageForDomain)) {
				$homePageOBJ = $candidateObj;
				break;
			}
		}
		
		if(singleton('SiteTree')->hasExtension('Translatable') && !$homePageOBJ) {
			$urlSegment = Translatable::get_homepage_urlsegment_by_locale(Translatable::get_current_locale());
		} elseif($homePageOBJ) {
			$urlSegment = $homePageOBJ->URLSegment;
		}

		return ($urlSegment) ? $urlSegment : self::get_default_homepage_urlsegment();
	}
	
	/**
	 * Returns true if we're currently on the root page and should be redirecting to the root
	 * Doesn't take into account actions, post vars, or get vars.
	 *
	 * @param SiteTree $currentPage
	 * @return boolean
	 */
	static function should_be_on_root(SiteTree $currentPage) {
		if(self::$is_at_root) return false;
		
		if(!$currentPage->HomepageForDomain 
			&& $currentPage->URLSegment != self::get_default_homepage_urlsegment()) return false;
		
		$matchesHomepageSegment = (self::get_homepage_urlsegment() == $currentPage->URLSegment);
		// Don't redirect translated homepage segments,
		// as the redirection target '/' will show the default locale
		// instead of the translation.
		$isTranslatedHomepage = (
			singleton('SiteTree')->hasExtension('Translatable')
			&& $currentPage->Locale 
			&& $currentPage->Locale != Translatable::default_locale()
		);
		if($matchesHomepageSegment && !$isTranslatedHomepage) return true;
		
		return false;
	}
	
	/**
	 * Returns the (untranslated) hardcoded URL segment that will
	 * show when the website is accessed without a URL segment (http://mysite.com/).
	 * It is also the base for any redirections to '/' for the homepage,
	 * see {@link should_be_on_root()}.
	 * 
	 * @return string
	 */
	static function get_default_homepage_urlsegment() {
		return self::$default_homepage_urlsegment;
	}
}

?>
