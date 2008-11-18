<?php
/**
 * Initial implementation of Sitemap support.
 * GoogleSitemap should handle requests to 'sitemap.xml'
 * the other two classes are used to render the sitemap.
 * 
 * You can notify ("ping") Google about a changed sitemap
 * automatically whenever a new page is published or unpublished.
 * By default, Google is not notified, and will pick up your new
 * sitemap whenever the GoogleBot visits your website.
 * 
 * Enabling notification of Google after every publish (in your _config.php):
 * <example
 * GoogleSitemap::enable_google_notificaton();
 * </example>
 * 
 * @see http://www.google.com/support/webmasters/bin/answer.py?hl=en&answer=34609
 * 
 * @package sapphire
 * @subpackage misc
 */
class GoogleSitemap extends Controller {
	
	/**
	 * @var boolean
	 */
	protected static $enabled = true;
	
	/**
	 * @var DataObjectSet
	 */
	protected $Pages;
	
	/**
	 * @var boolean
	 */
	protected static $google_notification_enabled = false;
	
	public function Items() {
		$this->Pages = Versioned::get_by_stage('SiteTree', 'Live');

		$newPages = new DataObjectSet();
		
		foreach($this->Pages as $page) {
			// Only include pages from this host and pages which are not an instance of ErrorPage 
			if(parse_url($page->AbsoluteLink(), PHP_URL_HOST) == $_SERVER['HTTP_HOST'] && !($page instanceof ErrorPage)) {
			
				// If the page has been set to 0 priority, we set a flag so it won't be included
				if(!isset($page->Priority) || $page->Priority > 0) {
					// The one field that isn't easy to deal with in the template is
					// Change frequency, so we set that here.
					$properties = $page->toMap();
					$created = new SSDatetime($properties['Created']);
					$now = new SSDatetime();
					$versions = $properties['Version'];
					$timediff = $now->format('U') - $created->format('U');
			
					// Check how many revisions have been made over the lifetime of the
					// Page for a rough estimate of it's changing frequency.
			
					$period = $timediff / ($versions + 1);
			
					if($period > 60*60*24*365) { // > 1 year
						$page->ChangeFreq='yearly';
					} else if($period > 60*60*24*30) { // > ~1 month
						$page->ChangeFreq='monthly';
					} else if($period > 60*60*24*7) { // > 1 week
						$page->ChangeFreq='weekly';
					} else if($period > 60*60*24) { // > 1 day
						$page->ChangeFreq='daily';
					} else if($period > 60*60) { // > 1 hour
						$page->ChangeFreq='hourly';
					} else { // < 1 hour
						$page->ChangeFreq='always';
					}
				
					$newPages->push($page);
				}
			}
		}
		return $newPages;
	}
	
	/**
	 * Notifies Google about changes to your sitemap.
	 * Triggered automatically on every publish/unpublish of a page.
	 * This behaviour is disabled by default, enable with:
	 * GoogleSitemap::enable_google_notificaton();
	 * 
	 * If the site is in "dev-mode", no ping will be sent regardless wether
	 * the Google notification is enabled.
	 * 
	 * @return string Response text
	 */
	static function ping() {
		if(!self::$enabled) return false;
		
		//Don't ping if the site has disabled it, or if the site is in dev mode
		if(!GoogleSitemap::$google_notification_enabled || Director::isDev())
			return;
			
		$location = urlencode(Director::absoluteBaseURL() . '/sitemap.xml');
		
		$response = HTTP::sendRequest("www.google.com", "/webmasters/sitemaps/ping",
			"sitemap=" . $location);
			
		return $response;
	}
	
	/**
	 * Enable pings to google.com whenever sitemap changes.
	 */
	public static function enable_google_notification() {
		self::$google_notification_enabled = true;
	}
	
	/**
	 * Disables pings to google when the sitemap changes.
	 */
	public static function disable_google_notification() {
		self::$google_notification_enabled = false;
	}
	
	function index($url) {
		if(self::$enabled) {
			// We need to override the default content-type
			ContentNegotiator::disable();
			header('Content-type: application/xml; charset="utf-8"');

			// But we want to still render.
			return array();
		} else {
			return new HTTPResponse('Not allowed', 405);
		}

	}
	
	public static function enable() {
		self::$enabled = true;
	}
	
	public static function disable() {
		self::$enabled = false;
	}
}

/**
 * @package sapphire
 * @subpackage misc
 */
class GoogleSitemapDecorator extends SiteTreeDecorator {
	
	function extraStatics() {
		return array(
			'db' => array(
				"Priority" => "Float",
			)
		);
	}
	
	function updateCMSFields(&$fields) {
		$pagePriorities = array(
			'' => _t('SiteTree.PRIORITYAUTOSET','Auto-set based on page depth'),
			'-1' => _t('SiteTree.PRIORITYNOTINDEXED', "Not indexed"), // We set this to -ve one because a blank value implies auto-generation of Priority
			'1.0' => '1 - ' . _t('SiteTree.PRIORITYMOSTIMPORTANT', "Most important"),
			'0.9' => '2',
			'0.8' => '3',
			'0.7' => '4',
			'0.6' => '5',
			'0.5' => '6',
			'0.4' => '7',
			'0.3' => '8',
			'0.2' => '9',
			'0.1' => '10 - ' . _t('SiteTree.PRIORITYLEASTIMPORTANT', "Least important")
		);
		
		$tabset = $fields->findOrMakeTab('Root.Content');
		$tabset->push(
			$addTab = new Tab(
				'GoogleSitemap',
				_t('SiteTree.TABGOOGLESITEMAP', 'Google Sitemap'),
				new LiteralField(
					"GoogleSitemapIntro", 
					"<p>" .
					sprintf(
						_t(
							'SiteTree.METANOTEPRIORITY', 
							"Manually specify a Google Sitemaps priority for this page (%s)"
						),
						'<a href="https://www.google.com/webmasters/tools/docs/en/protocol.html#prioritydef">?</a>'
					) .
					"</p>"
				), 
				new DropdownField("Priority", $this->owner->fieldLabel('Priority'), $pagePriorities)
			)
		);
	}
	
	function updateFieldLabels(&$labels) {
		parent::updateFieldLabels($labels);
		
		$labels['Priority'] = _t('SiteTree.METAPAGEPRIO', "Page Priority");
	}
	
	function onAfterPublish() {
		GoogleSiteMap::ping();
	}
	
	function onAfterUnpublish() {
		GoogleSiteMap::ping();
	}
	
	/**
	 * The default value of the priority field depends on the depth of the page in
	 * the site tree, so it must be calculated dynamically.
	 */
	function getPriority() {		
		if(!$this->owner->getField('Priority')) {
			$parentStack = $this->owner->parentStack();
			$numParents = is_array($parentStack) ? count($parentStack) - 1: 0;
			return max(0.1, 1.0 - ($numParents / 10));
		} else if($this->owner->getField('Priority') == -1) {
			return 0;
		} else {
			return $this->owner->getField('Priority');
		}
	}
}

Object::add_extension('SiteTree', 'GoogleSitemapDecorator');
?>