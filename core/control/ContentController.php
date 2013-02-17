<?php
/**
 * The most common kind of controller; effectively a controller linked to a {@link DataObject}.
 *
 * ContentControllers are most useful in the content-focused areas of a site.  This is generally
 * the bulk of a site; however, they may be less appropriate in, for example, the user management
 * section of an application.
 *
 * On its own, content controller does very little.  Its constructor is passed a {@link DataObject}
 * which is stored in $this->dataRecord.  Any unrecognised method calls, for example, Title()
 * and Content(), will be passed along to the data record,
 *
 * Subclasses of ContentController are generally instantiated by ModelAsController; this will create
 * a controller based on the URLSegment action variable, by looking in the SiteTree table.
 * 
 * @todo Can this be used for anything other than SiteTree controllers?
 *
 * @package sapphire
 * @subpackage control
 */
class ContentController extends Controller {

	protected $dataRecord;
	
	static $url_handlers = array(
		'widget/$ID/$Action' => 'handleWidget'
	);
	
	public static $allowed_actions = array (
		'PageComments',
		'successfullyinstalled',
		'handleWidget',
		'deleteinstallfiles' // secured through custom code
	);
	
	/**
	 * The ContentController will take the URLSegment parameter from the URL and use that to look
	 * up a SiteTree record.
	 */
	public function __construct($dataRecord = null) {
		if(!$dataRecord) {
			$dataRecord = new Page();
			if($this->hasMethod("Title")) $dataRecord->Title = $this->Title();
			$dataRecord->URLSegment = get_class($this);
			$dataRecord->ID = -1;
		}
		
		$this->dataRecord = $dataRecord;
		$this->failover = $this->dataRecord;
		parent::__construct();
	}
	
	/**
	 * Return the link to this controller, but force the expanded link to be returned so that form methods and
	 * similar will function properly.
	 *
	 * @return string
	 */
	public function Link($action = null) {
		return $this->data()->Link(($action ? $action : true));
	}
	
	//----------------------------------------------------------------------------------//
	// These flexible data methods remove the need for custom code to do simple stuff
	
	/**
	 * Return the children of a given page. The parent reference can either be a page link or an ID.
	 *
	 * @param string|int $parentRef
	 * @return DataObjectSet
	 */
	public function ChildrenOf($parentRef) {
		$parent = SiteTree::get_by_link($parentRef);
		
		if(!$parent && is_numeric($parentRef)) {
			$parent = DataObject::get_by_id('SiteTree', Convert::raw2sql($parentRef));
		}
		
		if($parent) return $parent->Children();
	}
	
	/**
	 * @return DataObjectSet
	 */
	public function Page($link) {
		return SiteTree::get_by_link($link);
	}

	public function init() {
		parent::init();
		
		// If we've accessed the homepage as /home/, then we should redirect to /.
		if($this->dataRecord && $this->dataRecord instanceof SiteTree
			 	&& RootURLController::should_be_on_root($this->dataRecord) && (!isset($this->urlParams['Action']) || !$this->urlParams['Action'] ) 
				&& !$_POST && !$_FILES && !Director::redirected_to() ) {
			$getVars = $_GET;
			unset($getVars['url']);
			if($getVars) $url = "?" . http_build_query($getVars);
			else $url = "";
			Director::redirect($url, 301);
			return;
		}
		
		if($this->dataRecord) $this->dataRecord->extend('contentcontrollerInit', $this);
		else singleton('SiteTree')->extend('contentcontrollerInit', $this);

		if(Director::redirected_to()) return;

		// Check page permissions
		if($this->dataRecord && $this->URLSegment != 'Security' && !$this->dataRecord->canView()) {
			return Security::permissionFailure($this);
		}

		// Draft/Archive security check - only CMS users should be able to look at stage/archived content
		if($this->URLSegment != 'Security' && !Session::get('unsecuredDraftSite') && (Versioned::current_archived_date() || (Versioned::current_stage() && Versioned::current_stage() != 'Live'))) {
			if(!Permission::check('CMS_ACCESS_CMSMain') && !Permission::check('VIEW_DRAFT_CONTENT')) {
				$link = $this->Link();
				$message = _t("ContentController.DRAFT_SITE_ACCESS_RESTRICTION", 'You must log in with your CMS password in order to view the draft or archived content.  <a href="%s">Click here to go back to the published site.</a>');
				Session::clear('currentStage');
				Session::clear('archiveDate');
				
				return Security::permissionFailure($this, sprintf($message, Controller::join_links($link, "?stage=Live")));
			}
		}

		// Use theme from the site config
		if(($config = SiteConfig::current_site_config()) && $config->Theme) {
			SSViewer::set_theme($config->Theme);
		}
	}
	
	/**
	 * This acts the same as {@link Controller::handleRequest()}, but if an action cannot be found this will attempt to
	 * fall over to a child controller in order to provide functionality for nested URLs.
	 *
	 * @return SS_HTTPResponse
	 */
	public function handleRequest(SS_HTTPRequest $request) {		
		$child  = null;
		$action = $request->param('Action');
		
		// If nested URLs are enabled, and there is no action handler for the current request then attempt to pass
		// control to a child controller. This allows for the creation of chains of controllers which correspond to a
		// nested URL.
		if($action && SiteTree::nested_urls() && !$this->hasAction($action)) {
			// See ModelAdController->getNestedController() for similar logic
			Translatable::disable_locale_filter();
			// look for a page with this URLSegment
			$child = DataObject::get_one('SiteTree', sprintf (
				"\"ParentID\" = %s AND \"URLSegment\" = '%s'", $this->ID, Convert::raw2sql($action)
			));
			Translatable::enable_locale_filter();
			
			// if we can't find a page with this URLSegment try to find one that used to have 
			// that URLSegment but changed. See ModelAsController->getNestedController() for similiar logic.
			if(!$child){
				$child = ModelAsController::find_old_page($action,$this->ID);
				if($child){
					$response = new SS_HTTPResponse();
					$params = $request->getVars();
					if(isset($params['url'])) unset($params['url']);
					$response->redirect(
						Controller::join_links(
							$child->Link(
								Controller::join_links(
									$request->param('ID'), // 'ID' is the new 'URLSegment', everything shifts up one position
									$request->param('OtherID')
								)
							),
							// Needs to be in separate join links to avoid urlencoding
							($params) ? '?' . http_build_query($params) : null
						),
						301
					);
					return $response;
				}
			}
		}
		
		// we found a page with this URLSegment.
		if($child) {
			$request->shiftAllParams();
			$request->shift();
			
			$response = ModelAsController::controller_for($child)->handleRequest($request);
		} else {
			// If a specific locale is requested, and it doesn't match the page found by URLSegment,
			// look for a translation and redirect (see #5001). Only happens on the last child in
			// a potentially nested URL chain.
			if($request->getVar('locale') && $this->dataRecord && $this->dataRecord->Locale != $request->getVar('locale')) {
				$translation = $this->dataRecord->getTranslation($request->getVar('locale'));
				if($translation) {
					$response = new SS_HTTPResponse();
					$response->redirect($translation->Link(), 301);
					throw new SS_HTTPResponse_Exception($response);
				}
			}
			
			Director::set_current_page($this->data());
			$response = parent::handleRequest($request);
			Director::set_current_page(null);
		}
		
		return $response;
	}
	
	/**
	 * @uses ErrorPage::response_for()
	 */
	public function httpError($code, $message = null) {
		if($this->request->isMedia() || !$response = ErrorPage::response_for($code)) {
			parent::httpError($code, $message);
		} else {
			throw new SS_HTTPResponse_Exception($response);
		}
	}
	
	/**
	 * Handles widgets attached to a page through one or more {@link WidgetArea} elements.
	 * Iterated through each $has_one relation with a {@link WidgetArea}
	 * and looks for connected widgets by their database identifier.
	 * Assumes URLs in the following format: <URLSegment>/widget/<Widget-ID>.
	 * 
	 * @return RequestHandler
	 */
	function handleWidget() {
		$SQL_id = $this->request->param('ID');
		if(!$SQL_id) return false;
		
		// find WidgetArea relations
		$widgetAreaRelations = array();
		$hasOnes = $this->dataRecord->has_one();
		if(!$hasOnes) return false;
		foreach($hasOnes as $hasOneName => $hasOneClass) {
			if($hasOneClass == 'WidgetArea' || ClassInfo::is_subclass_of($hasOneClass, 'WidgetArea')) {
				$widgetAreaRelations[] = $hasOneName;
			}
		}

		// find widget
		$widget = null;
		foreach($widgetAreaRelations as $widgetAreaRelation) {
			if($widget) break;
			$widget = $this->dataRecord->$widgetAreaRelation()->Widgets(
				sprintf('"Widget"."ID" = %d', $SQL_id)
			)->First();
		}
		if(!$widget) user_error('No widget found', E_USER_ERROR);
		
		// find controller
		$controllerClass = '';
		foreach(array_reverse(ClassInfo::ancestry($widget->class)) as $widgetClass) {
			$controllerClass = "{$widgetClass}_Controller";
			if(class_exists($controllerClass)) break;
		}
		if(!$controllerClass) user_error(
			sprintf('No controller available for %s', $widget->class),
			E_USER_ERROR
		);

		return new $controllerClass($widget);
	}

	/**
	 * Get the project name
	 *
	 * @return string
	 */
	function project() {
		global $project;
		return $project;
	}
	
	/**
	 * Returns the associated database record
	 */
	public function data() {
		return $this->dataRecord;
	}

	/*--------------------------------------------------------------------------------*/

	/**
	 * Returns a fixed navigation menu of the given level.
	 * @return DataObjectSet
	 */
	public function getMenu($level = 1) {
		if($level == 1) {
			$result = DataObject::get("SiteTree", "\"ShowInMenus\" = 1 AND \"ParentID\" = 0");

		} else {
			$parent = $this->data();
			$stack = array($parent);
			
			if($parent) {
				while($parent = $parent->Parent) {
					array_unshift($stack, $parent);
				}
			}
			
			if(isset($stack[$level-2])) $result = $stack[$level-2]->Children();
		}

		$visible = array();

		// Remove all entries the can not be viewed by the current user
		// We might need to create a show in menu permission
 		if(isset($result)) {
			foreach($result as $page) {
				if($page->canView()) {
					$visible[] = $page;
				}
			}
		}

		return new DataObjectSet($visible);
	}

	public function Menu($level) {
		return $this->getMenu($level);
	}

	/**
	 * Returns the default log-in form.
	 *
	 * @todo Check if here should be returned just the default log-in form or
	 *       all available log-in forms (also OpenID...)
	 */
	public function LoginForm() {
		return MemberAuthenticator::get_login_form($this);
	}

	public function SilverStripeNavigator() {
		$member = Member::currentUser();
		$items = '';
		$message = '';

		if(Director::isDev() || Permission::check('CMS_ACCESS_CMSMain') || Permission::check('VIEW_DRAFT_CONTENT')) {			
			if($this->dataRecord) {
				Requirements::css(SAPPHIRE_DIR . '/css/SilverStripeNavigator.css');
				// TODO Using jQuery for this is absolute overkill, and might cause conflicts
				// with other libraries.
				Requirements::javascript(SAPPHIRE_DIR . '/thirdparty/jquery/jquery.js');
				Requirements::javascript(SAPPHIRE_DIR . '/javascript/SilverStripeNavigator.js');
				
				$return = $nav = SilverStripeNavigator::get_for_record($this->dataRecord);
				$items = $return['items'];
				$message = $return['message'];
			}

			if($member) {
				$firstname = Convert::raw2xml($member->FirstName);
				$surname = Convert::raw2xml($member->Surname);
				$logInMessage = _t('ContentController.LOGGEDINAS', 'Logged in as') ." {$firstname} {$surname} - <a href=\"Security/logout\">". _t('ContentController.LOGOUT', 'Log out'). "</a>";
			} else {
				$logInMessage = _t('ContentController.NOTLOGGEDIN', 'Not logged in') ." - <a href=\"Security/login\">". _t('ContentController.LOGIN', 'Login') ."</a>";
			}
			$viewPageIn = _t('ContentController.VIEWPAGEIN', 'View Page in:');
			
			Requirements::customScript("window.name = windowName('site');");
			
			return <<<HTML
				<div id="SilverStripeNavigator">
					<div class="holder">
					<div id="logInStatus">
						$logInMessage
					</div>

					<div id="switchView" class="bottomTabs">
						<div class="blank">$viewPageIn </div>
						$items
					</div>
					</div>
				</div>
					$message
HTML;

		// On live sites we should still see the archived message
		} else {
			if($date = Versioned::current_archived_date()) {
				Requirements::css(SAPPHIRE_DIR . '/css/SilverStripeNavigator.css');
				$dateObj = Object::create('Datetime', $date, null);
				// $dateObj->setVal($date);
				return "<div id=\"SilverStripeNavigatorMessage\">". _t('ContentController.ARCHIVEDSITEFROM') ."<br>" . $dateObj->Nice() . "</div>";
			}
		}
	}

	/**
	 * Returns a page comment system
	 */
	function PageComments() {
		$hasComments = DB::query("SELECT COUNT(*) FROM \"PageComment\" WHERE \"PageComment\".\"ParentID\" = '". Convert::raw2sql($this->ID) . "'")->value();
		if(($this->data() && $this->data()->ProvideComments) || ($hasComments > 0 && PageCommentInterface::$show_comments_when_disabled)) {
			return new PageCommentInterface($this, 'PageComments', $this->data());
		} else {
			if(isset($_REQUEST['executeForm']) && $_REQUEST['executeForm'] == 'PageComments.PostCommentForm') {
				echo "Comments have been disabled for this page";
				die();
			}
		}
	}
	
	function SiteConfig() {
		if(method_exists($this->dataRecord, 'getSiteConfig')) {
			return $this->dataRecord->getSiteConfig();
		} else {
			return SiteConfig::current_site_config();
		}
	}

	/**
	 * Returns the xml:lang and lang attributes.
	 * 
	 * @deprecated 2.5 Use ContentLocale() instead and write attribute names suitable to XHTML/HTML
	 * templates directly in the template.
	 */
	function LangAttributes() {
		$locale = $this->ContentLocale();
		return "xml:lang=\"$locale\" lang=\"$locale\"";	
	}
	
	/**
	 * Returns an RFC1766 compliant locale string, e.g. 'fr-CA'.
	 * Inspects the associated {@link dataRecord} for a {@link SiteTree->Locale} value if present,
	 * and falls back to {@link Translatable::get_current_locale()} or {@link i18n::default_locale()},
	 * depending if Translatable is enabled.
	 * 
	 * Suitable for insertion into lang= and xml:lang=
	 * attributes in HTML or XHTML output.
	 * 
	 * @return string
	 */
	function ContentLocale() {
		if($this->dataRecord && $this->dataRecord->hasExtension('Translatable')) {
			$locale = $this->dataRecord->Locale;
		} elseif(Object::has_extension('SiteTree', 'Translatable')) {
			$locale = Translatable::get_current_locale();
		} else {
			$locale = i18n::get_locale();
		}
		
		return i18n::convert_rfc1766($locale);
	}

	/**
	 * This action is called by the installation system
	 */
	function successfullyinstalled() {
		// The manifest should be built by now, so it's safe to publish the 404 page
		$fourohfour = Versioned::get_one_by_stage('ErrorPage', 'Stage', '"ErrorCode" = 404');
		if($fourohfour) {
			$fourohfour->Status = "Published";
			$fourohfour->write();
			$fourohfour->publish("Stage", "Live");
		}
		
		// TODO Allow this to work when allow_url_fopen=0
		if(isset($_SESSION['StatsID']) && $_SESSION['StatsID']) {
			$url = 'http://ss2stat.silverstripe.com/Installation/installed?ID=' . $_SESSION['StatsID'];
			@file_get_contents($url);
		}
		
		$title = new Varchar("Title");
		$content = new HTMLText("Content");
		$username = Session::get('username');
		$password = Session::get('password');
		$title->setValue("Installation Successful");
		global $project;
		$tutorialOnly = ($project == 'tutorial') ? "<p>This website is a simplistic version of a SilverStripe 2 site. To extend this, please take a look at <a href=\"http://doc.silverstripe.org/doku.php?id=tutorials\">our new tutorials</a>.</p>" : '';
		$content->setValue(<<<HTML
			<p style="margin: 1em 0"><b>Congratulations, SilverStripe has been successfully installed.</b></p>
			
			$tutorialOnly
			<p>You can start editing your site's content by opening <a href="admin/">the CMS</a>. <br />
				&nbsp; &nbsp; Email: $username<br />
				&nbsp; &nbsp; Password: $password<br />
			</p>
			<div style="background:#ddd; border:1px solid #ccc; padding:5px; margin:5px;"><img src="cms/images/dialogs/alert.gif" style="border: none; margin-right: 10px; float: left;" /><p style="color:red;">For security reasons you should now delete the install files, unless you are planning to reinstall later (<em>requires admin login, see above</em>). The web server also now only needs write access to the "assets" folder, you can remove write access from all other folders. <a href="home/deleteinstallfiles" style="text-align: center;">Click here to delete the install files.</a></p></div>
HTML
);

		return array(
			"Title" => $title,
			"Content" => $content,
		);
	}

	function deleteinstallfiles() {
		if(!Permission::check("ADMIN")) return Security::permissionFailure($this);
		
		$title = new Varchar("Title");
		$content = new HTMLText("Content");
		$tempcontent = '';
		$username = Session::get('username');
		$password = Session::get('password');

		// We can't delete index.php as it might be necessary for URL routing without mod_rewrite.
		// There's no safe way to detect usage of mod_rewrite across webservers,
		// so we have to assume the file is required.
		$installfiles = array(
			'install.php',
			'config-form.css',
			'config-form.html',
			'index.html'
		);

		foreach($installfiles as $installfile) {
			if(file_exists(BASE_PATH . '/' . $installfile)) {
				@unlink(BASE_PATH . '/' . $installfile);
			}

			if(file_exists(BASE_PATH . '/' . $installfile)) {
				$unsuccessful[] = $installfile;
			}
		}

		if(isset($unsuccessful)) {
			$title->setValue("Unable to delete installation files");
			$tempcontent = "<p style=\"margin: 1em 0\">Unable to delete installation files. Please delete the files below manually:</p><ul>";
			foreach($unsuccessful as $unsuccessfulFile) {
				$tempcontent .= "<li>$unsuccessfulFile</li>";
			}
			$tempcontent .= "</ul>";
		} else {
			$title->setValue("Deleted installation files");
			$tempcontent = <<<HTML
<p style="margin: 1em 0">Installation files have been successfully deleted.</p>
HTML
			;
		}

		$tempcontent .= <<<HTML
			<p style="margin: 1em 0">You can start editing your site's content by opening <a href="admin/">the CMS</a>. <br />
				&nbsp; &nbsp; Email: $username<br />
				&nbsp; &nbsp; Password: $password<br />
			</p>
HTML
		;
		$content->setValue($tempcontent);

		return array(
			"Title" => $title,
			"Content" => $content,
		);
	}
}

?>
