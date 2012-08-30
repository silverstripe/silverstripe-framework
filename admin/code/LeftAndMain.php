<?php
/**
 * LeftAndMain is the parent class of all the two-pane views in the CMS.
 * If you are wanting to add more areas to the CMS, you can do it by subclassing LeftAndMain.
 * 
 * This is essentially an abstract class which should be subclassed.
 * See {@link CMSMain} for a good example.
 * 
 * @package cms
 * @subpackage core
 */
class LeftAndMain extends Controller implements PermissionProvider {
	
	/**
	 * The 'base' url for CMS administration areas.
	 * Note that if this is changed, many javascript
	 * behaviours need to be updated with the correct url
	 *
	 * @var string $url_base
	 */
	static $url_base = "admin";
	
	/**
	 * The current url segment attached to the LeftAndMain instance
	 *
	 * @var string
	 */
	static $url_segment;
	
	/**
	 * @var string
	 */
	static $url_rule = '/$Action/$ID/$OtherID';
	
	/**
	 * @var string
	 */
	static $menu_title;

	/**
	 * @var string
	 */
	static $menu_icon;
	
	/**
	 * @var int
	 */
	static $menu_priority = 0;
	
	/**
	 * @var int
	 */
	static $url_priority = 50;

	/**
	 * A subclass of {@link DataObject}. 
	 * 
	 * Determines what is managed in this interface, through 
	 * {@link getEditForm()} and other logic.
	 *
	 * @var string 
	 */
	static $tree_class = null;
	
	/**
	 * The url used for the link in the Help tab in the backend
	 * 
	 * @var string
	 */
	static $help_link = 'http://3.0.userhelp.silverstripe.org';

	/**
	 * @var array
	 */
	static $allowed_actions = array(
		'index',
		'save',
		'savetreenode',
		'getsubtree',
		'updatetreenodes',
		'printable',
		'show',
		'ping',
		'EditorToolbar',
		'EditForm',
		'AddForm',
		'batchactions',
		'BatchActionsForm',
		'Member_ProfileForm',
	);

	/**
	 * @var Array Codes which are required from the current user to view this controller.
	 * If multiple codes are provided, all of them are required.
	 * All CMS controllers require "CMS_ACCESS_LeftAndMain" as a baseline check,
	 * and fall back to "CMS_ACCESS_<class>" if no permissions are defined here.
	 * See {@link canView()} for more details on permission checks.
	 */
	static $required_permission_codes;

	/**
	 * @var String Namespace for session info, e.g. current record.
	 * Defaults to the current class name, but can be amended to share a namespace in case
	 * controllers are logically bundled together, and mainly separated
	 * to achieve more flexible templating.
	 */
	static $session_namespace;
	
	/**
	 * Register additional requirements through the {@link Requirements} class.
	 * Used mainly to work around the missing "lazy loading" functionality
	 * for getting css/javascript required after an ajax-call (e.g. loading the editform).
	 *
	 * @var array $extra_requirements
	 */
	protected static $extra_requirements = array(
		'javascript' => array(),
		'css' => array(),
		'themedcss' => array(),
	);

	/**
	 * @var PjaxResponseNegotiator
	 */
	protected $responseNegotiator;
	
	/**
	 * @param Member $member
	 * @return boolean
	 */
	function canView($member = null) {
		if(!$member && $member !== FALSE) $member = Member::currentUser();
		
		// cms menus only for logged-in members
		if(!$member) return false;
		
		// alternative extended checks
		if($this->hasMethod('alternateAccessCheck')) {
			$alternateAllowed = $this->alternateAccessCheck();
			if($alternateAllowed === FALSE) return false;
		}

		// Check for "CMS admin" permission
		if(Permission::checkMember($member, "CMS_ACCESS_LeftAndMain")) return true;

		// Check for LeftAndMain sub-class permissions			
		$codes = array();
		$extraCodes = $this->stat('required_permission_codes');
		if($extraCodes !== false) { // allow explicit FALSE to disable subclass check
			if($extraCodes) $codes = array_merge($codes, (array)$extraCodes);
			else $codes[] = "CMS_ACCESS_$this->class";	
		}
		foreach($codes as $code) if(!Permission::checkMember($member, $code)) return false;
		
		return true;
	}
	
	/**
	 * @uses LeftAndMainExtension->init()
	 * @uses LeftAndMainExtension->accessedCMS()
	 * @uses CMSMenu
	 */
	function init() {
		parent::init();

		SSViewer::setOption('rewriteHashlinks', false);
		
		// set language
		$member = Member::currentUser();
		if(!empty($member->Locale)) i18n::set_locale($member->Locale);
		if(!empty($member->DateFormat)) i18n::set_date_format($member->DateFormat);
		if(!empty($member->TimeFormat)) i18n::set_time_format($member->TimeFormat);
		
		// can't be done in cms/_config.php as locale is not set yet
		CMSMenu::add_link(
			'Help', 
			_t('LeftAndMain.HELP', 'Help', 'Menu title'), 
			self::$help_link
		);

		// Allow customisation of the access check by a extension
		// Also all the canView() check to execute Controller::redirect()
		if(!$this->canView() && !$this->response->isFinished()) {
			// When access /admin/, we should try a redirect to another part of the admin rather than be locked out
			$menu = $this->MainMenu();
			foreach($menu as $candidate) {
				if(
					$candidate->Link && 
					$candidate->Link != $this->Link() 
					&& $candidate->MenuItem->controller 
					&& singleton($candidate->MenuItem->controller)->canView()
				) {
					return $this->redirect($candidate->Link);
				}
			}
			
			if(Member::currentUser()) {
				Session::set("BackURL", null);
			}
			
			// if no alternate menu items have matched, return a permission error
			$messageSet = array(
				'default' => _t('LeftAndMain.PERMDEFAULT',"Please choose an authentication method and enter your credentials to access the CMS."),
				'alreadyLoggedIn' => _t('LeftAndMain.PERMALREADY',"I'm sorry, but you can't access that part of the CMS.  If you want to log in as someone else, do so below"),
				'logInAgain' => _t('LeftAndMain.PERMAGAIN',"You have been logged out of the CMS.  If you would like to log in again, enter a username and password below."),
			);

			return Security::permissionFailure($this, $messageSet);
		}
		
		// Don't continue if there's already been a redirection request.
		if($this->redirectedTo()) return;

		// Audit logging hook
		if(empty($_REQUEST['executeForm']) && !$this->request->isAjax()) $this->extend('accessedCMS');
		
		// Set the members html editor config
		HtmlEditorConfig::set_active(Member::currentUser()->getHtmlEditorConfigForCMS());
		
		// Set default values in the config if missing.  These things can't be defined in the config
		// file because insufficient information exists when that is being processed
		$htmlEditorConfig = HtmlEditorConfig::get_active();
		$htmlEditorConfig->setOption('language', i18n::get_tinymce_lang());
		if(!$htmlEditorConfig->getOption('content_css')) {
			$cssFiles = array();
			$cssFiles[] = FRAMEWORK_ADMIN_DIR . '/css/editor.css';
			
			// Use theme from the site config
			if(class_exists('SiteConfig') && ($config = SiteConfig::current_site_config()) && $config->Theme) {
				$theme = $config->Theme;
			} elseif(SSViewer::current_theme()) {
				$theme = SSViewer::current_theme();
			} else {
				$theme = false;
			}
			
			if($theme) $cssFiles[] = THEMES_DIR . "/{$theme}/css/editor.css";
			else if(project()) $cssFiles[] = project() . '/css/editor.css';
			
			// Remove files that don't exist
			foreach($cssFiles as $k => $cssFile) {
				if(!file_exists(BASE_PATH . '/' . $cssFile)) unset($cssFiles[$k]);
			}

			$htmlEditorConfig->setOption('content_css', implode(',', $cssFiles));
		}
		
		// Using uncompressed files as they'll be processed by JSMin in the Requirements class.
		// Not as effective as other compressors or pre-compressed+finetuned files, 
		// but overall the unified minification into a single file brings more performance benefits
		// than a couple of saved bytes (after gzip) in individual files.
		// We also re-compress already compressed files through JSMin as this causes weird runtime bugs.
		Requirements::combine_files(
			'lib.js',
			array(
				THIRDPARTY_DIR . '/jquery/jquery.js',
				FRAMEWORK_DIR . '/javascript/jquery-ondemand/jquery.ondemand.js',
				FRAMEWORK_ADMIN_DIR . '/javascript/lib.js',
				THIRDPARTY_DIR . '/jquery-ui/jquery-ui.js',
				THIRDPARTY_DIR . '/json-js/json2.js',
				THIRDPARTY_DIR . '/jquery-entwine/dist/jquery.entwine-dist.js',
				THIRDPARTY_DIR . '/jquery-cookie/jquery.cookie.js',
				THIRDPARTY_DIR . '/jquery-query/jquery.query.js',
				THIRDPARTY_DIR . '/jquery-form/jquery.form.js',
				FRAMEWORK_ADMIN_DIR . '/thirdparty/jquery-notice/jquery.notice.js',
				FRAMEWORK_ADMIN_DIR . '/thirdparty/jsizes/lib/jquery.sizes.js',
				FRAMEWORK_ADMIN_DIR . '/thirdparty/jlayout/lib/jlayout.border.js',
				FRAMEWORK_ADMIN_DIR . '/thirdparty/jlayout/lib/jquery.jlayout.js',
				FRAMEWORK_ADMIN_DIR . '/thirdparty/history-js/scripts/uncompressed/history.js',
				FRAMEWORK_ADMIN_DIR . '/thirdparty/history-js/scripts/uncompressed/history.adapter.jquery.js',
				FRAMEWORK_ADMIN_DIR . '/thirdparty/history-js/scripts/uncompressed/history.html4.js',
				THIRDPARTY_DIR . '/jstree/jquery.jstree.js',
				FRAMEWORK_ADMIN_DIR . '/thirdparty/chosen/chosen/chosen.jquery.js',
				FRAMEWORK_ADMIN_DIR . '/thirdparty/jquery-hoverIntent/jquery.hoverIntent.js',
				FRAMEWORK_ADMIN_DIR . '/javascript/jquery-changetracker/lib/jquery.changetracker.js',
				FRAMEWORK_DIR . '/javascript/TreeDropdownField.js',
				FRAMEWORK_DIR . '/javascript/DateField.js',
				FRAMEWORK_DIR . '/javascript/HtmlEditorField.js',
				FRAMEWORK_DIR . '/javascript/TabSet.js',
				FRAMEWORK_DIR . '/javascript/i18n.js',
				FRAMEWORK_ADMIN_DIR . '/javascript/ssui.core.js',
				FRAMEWORK_DIR . '/javascript/GridField.js',
			)
		);

		if (Director::isDev()) Requirements::javascript(FRAMEWORK_ADMIN_DIR . '/javascript/leaktools.js');

		HTMLEditorField::include_js();

		Requirements::combine_files(
			'leftandmain.js',
			array_unique(array_merge(
				array(
					FRAMEWORK_ADMIN_DIR . '/javascript/LeftAndMain.js',
					FRAMEWORK_ADMIN_DIR . '/javascript/LeftAndMain.Panel.js',
					FRAMEWORK_ADMIN_DIR . '/javascript/LeftAndMain.Tree.js',
					FRAMEWORK_ADMIN_DIR . '/javascript/LeftAndMain.Ping.js',
					FRAMEWORK_ADMIN_DIR . '/javascript/LeftAndMain.Content.js',
					FRAMEWORK_ADMIN_DIR . '/javascript/LeftAndMain.EditForm.js',
					FRAMEWORK_ADMIN_DIR . '/javascript/LeftAndMain.Menu.js',
					FRAMEWORK_ADMIN_DIR . '/javascript/LeftAndMain.AddForm.js',
					FRAMEWORK_ADMIN_DIR . '/javascript/LeftAndMain.Preview.js',
					FRAMEWORK_ADMIN_DIR . '/javascript/LeftAndMain.BatchActions.js',
					FRAMEWORK_ADMIN_DIR . '/javascript/LeftAndMain.FieldHelp.js',
					FRAMEWORK_ADMIN_DIR . '/javascript/LeftAndMain.TreeDropdownField.js',
				),
				Requirements::add_i18n_javascript(FRAMEWORK_DIR . '/javascript/lang', true, true),
				Requirements::add_i18n_javascript(FRAMEWORK_ADMIN_DIR . '/javascript/lang', true, true)
			))
		);

		// TODO Confuses jQuery.ondemand through document.write()
		if (Director::isDev()) {
			Requirements::javascript(THIRDPARTY_DIR . '/jquery-entwine/src/jquery.entwine.inspector.js');
		}

		Requirements::css(FRAMEWORK_ADMIN_DIR . '/thirdparty/jquery-notice/jquery.notice.css');
		Requirements::css(THIRDPARTY_DIR . '/jquery-ui-themes/smoothness/jquery-ui.css');
		Requirements::css(FRAMEWORK_ADMIN_DIR .'/thirdparty/chosen/chosen/chosen.css');
		Requirements::css(THIRDPARTY_DIR . '/jstree/themes/apple/style.css');
		Requirements::css(FRAMEWORK_DIR . '/css/TreeDropdownField.css');
		Requirements::css(FRAMEWORK_ADMIN_DIR . '/css/screen.css');
		Requirements::css(FRAMEWORK_DIR . '/css/GridField.css');

		// Browser-specific requirements
		$ie = isset($_SERVER['HTTP_USER_AGENT']) ? strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE') : false;
		if($ie) {
			$version = substr($_SERVER['HTTP_USER_AGENT'], $ie + 5, 3);
			if($version == 7) Requirements::css(FRAMEWORK_ADMIN_DIR . '/css/ie7.css');
			else if($version == 8) Requirements::css(FRAMEWORK_ADMIN_DIR . '/css/ie8.css');
		}

		// Custom requirements
		foreach (self::$extra_requirements['javascript'] as $file) {
			Requirements::javascript($file[0]);
		}
		foreach (self::$extra_requirements['css'] as $file) {
			Requirements::css($file[0], $file[1]);
		}
		foreach (self::$extra_requirements['themedcss'] as $file) {
			Requirements::themedCSS($file[0], $file[1]);
		}

		$dummy = null;
		$this->extend('init', $dummy);

		// The user's theme shouldn't affect the CMS, if, for example, they have replaced
		// TableListField.ss or Form.ss.
		SSViewer::set_theme(null);
	}
	
	function handleRequest(SS_HTTPRequest $request, DataModel $model = null) {
		$response = parent::handleRequest($request, $model);
		$title = $this->Title();
		if(!$response->getHeader('X-Controller')) $response->addHeader('X-Controller', $this->class);
		if(!$response->getHeader('X-Title')) $response->addHeader('X-Title', $title);
		
		return $response;
	}

	/**
	 * Overloaded redirection logic to trigger a fake redirect on ajax requests.
	 * While this violates HTTP principles, its the only way to work around the
	 * fact that browsers handle HTTP redirects opaquely, no intervention via JS is possible.
	 * In isolation, that's not a problem - but combined with history.pushState()
	 * it means we would request the same redirection URL twice if we want to update the URL as well.
	 * See LeftAndMain.js for the required jQuery ajaxComplete handlers.
	 */
	function redirect($url, $code=302) {
		if($this->request->isAjax()) {
			$this->response->addHeader('X-ControllerURL', $url);
			if($this->request->getHeader('X-Pjax') && !$this->response->getHeader('X-Pjax')) {
				$this->response->addHeader('X-Pjax', $this->request->getHeader('X-Pjax'));
			}
			$oldResponse = $this->response;
			$newResponse = new LeftAndMain_HTTPResponse(
				$oldResponse->getBody(), 
				$oldResponse->getStatusCode(),
				$oldResponse->getStatusDescription()
			);
			foreach($oldResponse->getHeaders() as $k => $v) {
				$newResponse->addHeader($k, $v);
			}
			$newResponse->setIsFinished(true);
			$this->response = $newResponse;
			return ''; // Actual response will be re-requested by client
		} else {
			parent::redirect($url, $code);
		}
	}

	function index($request) {
		return $this->getResponseNegotiator()->respond($request);
	}

	/**
	 * admin/ping can be visited with ajax to keep a session alive.
	 * This is used in the CMS.
	 */
	function ping() {
		return 1;
	}
	
	/**
	 * If this is set to true, the "switchView" context in the
	 * template is shown, with links to the staging and publish site.
	 *
	 * @return boolean
	 */
	function ShowSwitchView() {
		return false;
	}

	
	//------------------------------------------------------------------------------------------//
	// Main controllers

	/**
	 * You should implement a Link() function in your subclass of LeftAndMain,
	 * to point to the URL of that particular controller.
	 * 
	 * @return string
	 */
	public function Link($action = null) {
		// Handle missing url_segments
		if(!$this->stat('url_segment', true)) self::$url_segment = $this->class;
		
		$link = Controller::join_links(
			$this->stat('url_base', true),
			$this->stat('url_segment', true),
			'/', // trailing slash needed if $action is null!
			"$action"
		);
		$this->extend('updateLink', $link);
		return $link;
	}

	/**
	 * Returns the menu title for the given LeftAndMain subclass.
	 * Implemented static so that we can get this value without instantiating an object.
	 * Menu title is *not* internationalised.
	 */
	static function menu_title_for_class($class) {
		$title = Config::inst()->get($class, 'menu_title', Config::FIRST_SET);
		if(!$title) $title = preg_replace('/Admin$/', '', $class);
		return $title;
	}

	/**
	 * Return styling for the menu icon, if a custom icon is set for this class
	 *
	 * Example: static $menu-icon = '/path/to/image/';
	 * @param type $class
	 * @return string
	 */
	static function menu_icon_for_class($class) {
		$icon = Config::inst()->get($class, 'menu_icon', Config::FIRST_SET);
		if (!empty($icon)) {
			$class = strtolower($class);
			return ".icon.icon-16.icon-{$class} { background: url('{$icon}'); } ";
		}
		return '';
	}
	
	public function show($request) {
		// TODO Necessary for TableListField URLs to work properly
		if($request->param('ID')) $this->setCurrentPageID($request->param('ID'));
		return $this->getResponseNegotiator()->respond($request);
	}

	/**
	 * Caution: Volatile API.
	 *  
	 * @return PjaxResponseNegotiator
	 */
	public function getResponseNegotiator() {
		if(!$this->responseNegotiator) {
			$controller = $this;
			$this->responseNegotiator = new PjaxResponseNegotiator(
				array(
					'CurrentForm' => function() use(&$controller) {
						return $controller->getEditForm()->forTemplate();
					},
					'Content' => function() use(&$controller) {
						return $controller->renderWith($controller->getTemplatesWithSuffix('_Content'));
					},
					'Breadcrumbs' => function() use (&$controller) {
						return $controller->renderWith('CMSBreadcrumbs');
					},
					'default' => function() use(&$controller) {
						return $controller->renderWith($controller->getViewer('show'));
					}
				),
				$this->response
			);
		}
		return $this->responseNegotiator;
	}

	//------------------------------------------------------------------------------------------//
	// Main UI components

	/**
	 * Returns the main menu of the CMS.  This is also used by init() 
	 * to work out which sections the user has access to.
	 * 
	 * @param Boolean
	 * @return SS_List
	 */
	public function MainMenu($cached = true) {
		if(!isset($this->_cache_MainMenu) || !$cached) {
			// Don't accidentally return a menu if you're not logged in - it's used to determine access.
			if(!Member::currentUser()) return new ArrayList();

			// Encode into DO set
			$menu = new ArrayList();
			$menuItems = CMSMenu::get_viewable_menu_items();

			// extra styling for custom menu-icons
			$menuIconStyling = '';

			if($menuItems) {
				foreach($menuItems as $code => $menuItem) {
					// alternate permission checks (in addition to LeftAndMain->canView())
					if(
						isset($menuItem->controller) 
						&& $this->hasMethod('alternateMenuDisplayCheck')
						&& !$this->alternateMenuDisplayCheck($menuItem->controller)
					) {
						continue;
					}

					$linkingmode = "link";
					
					if($menuItem->controller && get_class($this) == $menuItem->controller) {
						$linkingmode = "current";
					} else if(strpos($this->Link(), $menuItem->url) !== false) {
						if($this->Link() == $menuItem->url) {
							$linkingmode = "current";
					
						// default menu is the one with a blank {@link url_segment}
						} else if(singleton($menuItem->controller)->stat('url_segment') == '') {
							if($this->Link() == $this->stat('url_base').'/') {
								$linkingmode = "current";
							}

						} else {
							$linkingmode = "current";
						}
					}
			
					// already set in CMSMenu::populate_menu(), but from a static pre-controller
					// context, so doesn't respect the current user locale in _t() calls - as a workaround,
					// we simply call LeftAndMain::menu_title_for_class() again 
					// if we're dealing with a controller
					if($menuItem->controller) {
						$defaultTitle = LeftAndMain::menu_title_for_class($menuItem->controller);
						$title = _t("{$menuItem->controller}.MENUTITLE", $defaultTitle);
					} else {
						$title = $menuItem->title;
					}

					// Provide styling for custom $menu-icon. Done here instead of in
					// CMSMenu::populate_menu(), because the icon is part of
					// the CMS right pane for the specified class as well...
					if($menuItem->controller) {
						$menuIcon = LeftAndMain::menu_icon_for_class($menuItem->controller);
						if (!empty($menuIcon)) $menuIconStyling .= $menuIcon;
					}
					
					$menu->push(new ArrayData(array(
						"MenuItem" => $menuItem,
						"Title" => Convert::raw2xml($title),
						"Code" => DBField::create_field('Text', $code),
						"Link" => $menuItem->url,
						"LinkingMode" => $linkingmode
					)));
				}
			}
			if ($menuIconStyling) Requirements::customCSS($menuIconStyling);

			$this->_cache_MainMenu = $menu;
		}

		return $this->_cache_MainMenu;
	}

	public function Menu() {
		return $this->renderWith($this->getTemplatesWithSuffix('_Menu'));
	}

	/**
	 * @todo Wrap in CMSMenu instance accessor
	 * @return ArrayData A single menu entry (see {@link MainMenu})
	 */
	public function MenuCurrentItem() {
		$items = $this->MainMenu();
		return $items->find('LinkingMode', 'current');
	}

	/**
	 * Return a list of appropriate templates for this class, with the given suffix
	 */
	public function getTemplatesWithSuffix($suffix) {
		$templates = array();
		$classes = array_reverse(ClassInfo::ancestry($this->class));
		foreach($classes as $class) {
			$template = $class . $suffix;
			if(SSViewer::hasTemplate($template)) $templates[] = $template;
			if($class == 'LeftAndMain') break;
		}
		return $templates;
	}

	public function Content() {
		return $this->renderWith($this->getTemplatesWithSuffix('_Content'));
	}

	public function getRecord($id) {
		$className = $this->stat('tree_class');
		if($className && $id instanceof $className) {
			return $id;
		} else if($id == 'root') {
			return singleton($className);
		} else if(is_numeric($id)) {
			return DataObject::get_by_id($className, $id);
		} else {
			return false;
		}
	}

	/**
	 * @return ArrayList
	 */
	public function Breadcrumbs($unlinked = false) {
		$defaultTitle = LeftAndMain::menu_title_for_class($this->class);
		$title = _t("{$this->class}.MENUTITLE", $defaultTitle);
		$items = new ArrayList(array(
			new ArrayData(array(
				'Title' => $title,
				'Link' => ($unlinked) ? false : $this->Link()
			))
		));
		$record = $this->currentPage();
		if($record && $record->exists()) {
			if($record->hasExtension('Hierarchy')) {
				$ancestors = $record->getAncestors();
				$ancestors = new ArrayList(array_reverse($ancestors->toArray()));
				$ancestors->push($record);
				foreach($ancestors as $ancestor) {
					$items->push(new ArrayData(array(
						'Title' => $ancestor->Title,
						'Link' => ($unlinked) ? false : Controller::join_links($this->Link('show'), $ancestor->ID)
					)));		
				}
			} else {
				$items->push(new ArrayData(array(
					'Title' => $record->Title,
					'Link' => ($unlinked) ? false : Controller::join_links($this->Link('show'), $record->ID)
				)));	
			}
		}

		return $items;
	}
	
	/**
	 * @return String HTML
	 */
	public function SiteTreeAsUL() {
		$html = $this->getSiteTreeFor($this->stat('tree_class'));
		$this->extend('updateSiteTreeAsUL', $html);
		return $html;
	}

	/**
	 * Get a site tree HTML listing which displays the nodes under the given criteria.
	 * 
	 * @param $className The class of the root object
	 * @param $rootID The ID of the root object.  If this is null then a complete tree will be
	 *  shown
	 * @param $childrenMethod The method to call to get the children of the tree. For example,
	 *  Children, AllChildrenIncludingDeleted, or AllHistoricalChildren
	 * @return String Nested unordered list with links to each page
	 */
	function getSiteTreeFor($className, $rootID = null, $childrenMethod = null, $numChildrenMethod = null, $filterFunction = null, $minNodeCount = 30) {
		// Filter criteria
		$params = $this->request->getVar('q');
		if(isset($params['FilterClass']) && $filterClass = $params['FilterClass']){
			if(!is_subclass_of($filterClass, 'CMSSiteTreeFilter')) {
				throw new Exception(sprintf('Invalid filter class passed: %s', $filterClass));
			}
			$filter = new $filterClass($params);
		} else {
			$filter = null;
		}

		// Default childrenMethod and numChildrenMethod
		if(!$childrenMethod) $childrenMethod = ($filter && $filter->getChildrenMethod()) ? $filter->getChildrenMethod() : 'AllChildrenIncludingDeleted';
		if(!$numChildrenMethod) $numChildrenMethod = 'numChildren';
		if(!$filterFunction) $filterFunction = ($filter) ? array($filter, 'isPageIncluded') : null;

		// Get the tree root
		$record = ($rootID) ? $this->getRecord($rootID) : null;
		$obj = $record ? $record : singleton($className);
		
		// Mark the nodes of the tree to return
		if ($filterFunction) $obj->setMarkingFilterFunction($filterFunction);

		$obj->markPartialTree($minNodeCount, $this, $childrenMethod, $numChildrenMethod);
		
		// Ensure current page is exposed
		if($p = $this->currentPage()) $obj->markToExpose($p);
		
		// NOTE: SiteTree/CMSMain coupling :-(
		if(class_exists('SiteTree')) {
			SiteTree::prepopulate_permission_cache('CanEditType', $obj->markedNodeIDs(), 'SiteTree::can_edit_multiple');
		}

		// getChildrenAsUL is a flexible and complex way of traversing the tree
		$controller = $this;
		$recordController = ($this->stat('tree_class') == 'SiteTree') ?  singleton('CMSPageEditController') : $this;
		$titleFn = function(&$child) use(&$controller, &$recordController) {
			$link = Controller::join_links($recordController->Link("show"), $child->ID);
			return LeftAndMain_TreeNode::create($child, $link, $controller->isCurrentPage($child))->forTemplate();
		};
		$html = $obj->getChildrenAsUL(
			"",
			$titleFn,
			singleton('CMSPagesController'),
			true, 
			$childrenMethod,
			$numChildrenMethod,
			$minNodeCount
		);

		// Wrap the root if needs be.
		if(!$rootID) {
			$rootLink = $this->Link('show') . '/root';
			
			// This lets us override the tree title with an extension
			if($this->hasMethod('getCMSTreeTitle') && $customTreeTitle = $this->getCMSTreeTitle()) {
				$treeTitle = $customTreeTitle;
			} elseif(class_exists('SiteConfig')) {
				$siteConfig = SiteConfig::current_site_config();
				$treeTitle =  $siteConfig->Title;
			} else {
				$treeTitle = '...';
			}
			
			$html = "<ul><li id=\"record-0\" data-id=\"0\" class=\"Root nodelete\"><strong>$treeTitle</strong>"
				. $html . "</li></ul>";
		}

		return $html;
	}

	/**
	 * Get a subtree underneath the request param 'ID'.
	 * If ID = 0, then get the whole tree.
	 */
	public function getsubtree($request) {
		$html = $this->getSiteTreeFor(
			$this->stat('tree_class'), 
			$request->getVar('ID'), 
			null, 
			null,
			null, 
			$request->getVar('minNodeCount')
		);

		// Trim off the outer tag
		$html = preg_replace('/^[\s\t\r\n]*<ul[^>]*>/','', $html);
		$html = preg_replace('/<\/ul[^>]*>[\s\t\r\n]*$/','', $html);
		
		return $html;
	}

	/**
	 * Allows requesting a view update on specific tree nodes.
	 * Similar to {@link getsubtree()}, but doesn't enforce loading
	 * all children with the node. Useful to refresh views after
	 * state modifications, e.g. saving a form.
	 * 
	 * @return String JSON
	 */
	public function updatetreenodes($request) {
		$data = array();
		$ids = explode(',', $request->getVar('ids'));
		foreach($ids as $id) {
			$record = $this->getRecord($id);
			$recordController = ($this->stat('tree_class') == 'SiteTree') ?  singleton('CMSPageEditController') : $this;

			// Find the next & previous nodes, for proper positioning (Sort isn't good enough - it's not a raw offset)
			// TODO: These methods should really be in hierarchy - for a start it assumes Sort exists
			$next = $prev = null;

			$className = $this->stat('tree_class');
			$next = DataObject::get($className)->filter('ParentID', $record->ParentID)->filter('Sort:GreaterThan', $record->Sort)->first();
			if (!$next) {
				$prev = DataObject::get($className)->filter('ParentID', $record->ParentID)->filter('Sort:LessThan', $record->Sort)->reverse()->first();
			}

			$link = Controller::join_links($recordController->Link("show"), $record->ID);
			$html = LeftAndMain_TreeNode::create($record, $link, $this->isCurrentPage($record))->forTemplate() . '</li>';

			$data[$id] = array(
				'html' => $html, 
				'ParentID' => $record->ParentID,
				'NextID' => $next ? $next->ID : null,
				'PrevID' => $prev ? $prev->ID : null
			);
		}
		$this->response->addHeader('Content-Type', 'text/json');
		return Convert::raw2json($data);
	}
	
	/**
	 * Save  handler
	 */
	public function save($data, $form) {
		$className = $this->stat('tree_class');

		// Existing or new record?
		$SQL_id = Convert::raw2sql($data['ID']);
		if(substr($SQL_id,0,3) != 'new') {
			$record = DataObject::get_by_id($className, $SQL_id);
			if($record && !$record->canEdit()) return Security::permissionFailure($this);
			if(!$record || !$record->ID) throw new HTTPResponse_Exception("Bad record ID #" . (int)$data['ID'], 404);
		} else {
			if(!singleton($this->stat('tree_class'))->canCreate()) return Security::permissionFailure($this);
			$record = $this->getNewItem($SQL_id, false);
		}
		
		// save form data into record
		$form->saveInto($record, true);
		$record->write();
		$this->extend('onAfterSave', $record);
		$this->setCurrentPageID($record->ID);
		
		$this->response->addHeader('X-Status', rawurlencode(_t('LeftAndMain.SAVEDUP', 'Saved.')));
		return $this->getResponseNegotiator()->respond($this->request);
	}
	
	public function delete($data, $form) {
		$className = $this->stat('tree_class');
		
		$record = DataObject::get_by_id($className, Convert::raw2sql($data['ID']));
		if($record && !$record->canDelete()) return Security::permissionFailure();
		if(!$record || !$record->ID) throw new HTTPResponse_Exception("Bad record ID #" . (int)$data['ID'], 404);
		
		$record->delete();

		$this->response->addHeader('X-Status', rawurlencode(_t('LeftAndMain.DELETED', 'Deleted.')));
		return $this->getResponseNegotiator()->respond(
			$this->request, 
			array('currentform' => array($this, 'EmptyForm'))
		);
	}

	/**
	 * Update the position and parent of a tree node.
	 * Only saves the node if changes were made.
	 * 
	 * Required data: 
	 * - 'ID': The moved node
	 * - 'ParentID': New parent relation of the moved node (0 for root)
	 * - 'SiblingIDs': Array of all sibling nodes to the moved node (incl. the node itself).
	 *   In case of a 'ParentID' change, relates to the new siblings under the new parent.
	 * 
	 * @return SS_HTTPResponse JSON string with a 
	 */
	public function savetreenode($request) {
		if (!Permission::check('SITETREE_REORGANISE') && !Permission::check('ADMIN')) {
			$this->response->setStatusCode(
				403,
				_t('LeftAndMain.CANT_REORGANISE',"You do not have permission to rearange the site tree. Your change was not saved.")
			);
			return;
		}

		$className = $this->stat('tree_class');		
		$statusUpdates = array('modified'=>array());
		$id = $request->requestVar('ID');
		$parentID = $request->requestVar('ParentID');

		if($className == 'SiteTree' && $page = DataObject::get_by_id('Page', $id)){
			$root = $page->getParentType();
			if(($parentID == '0' || $root == 'root') && !SiteConfig::current_site_config()->canCreateTopLevel()){
				$this->response->setStatusCode(
					403,
						_t('LeftAndMain.CANT_REORGANISE',"You do not have permission to alter Top level pages. Your change was not saved.")
					);
				return;
			}
		}

		$siblingIDs = $request->requestVar('SiblingIDs');
		$statusUpdates = array('modified'=>array());
		if(!is_numeric($id) || !is_numeric($parentID)) throw new InvalidArgumentException();
		
		$node = DataObject::get_by_id($className, $id);
		if($node && !$node->canEdit()) return Security::permissionFailure($this);
		
		if(!$node) {
			$this->response->setStatusCode(
				500,
				_t(
					'LeftAndMain.PLEASESAVE',
					"Please Save Page: This page could not be upated because it hasn't been saved yet."
				)
			);
			return;
		}

		// Update hierarchy (only if ParentID changed)
		if($node->ParentID != $parentID) {
			$node->ParentID = (int)$parentID;
			$node->write();
			
			$statusUpdates['modified'][$node->ID] = array(
				'TreeTitle'=>$node->TreeTitle
			);
			
			// Update all dependent pages
			if(class_exists('VirtualPage')) {
				if($virtualPages = DataObject::get("VirtualPage", "\"CopyContentFromID\" = $node->ID")) {
					foreach($virtualPages as $virtualPage) {
						$statusUpdates['modified'][$virtualPage->ID] = array(
							'TreeTitle' => $virtualPage->TreeTitle()
						);
					}
				}
			}

			$this->response->addHeader('X-Status', rawurlencode(_t('LeftAndMain.REORGANISATIONSUCCESSFUL', 'Reorganised the site tree successfully.')));
		}
		
		// Update sorting
		if(is_array($siblingIDs)) {
			$counter = 0;
			foreach($siblingIDs as $id) {
				if($id == $node->ID) {
					$node->Sort = ++$counter;
					$node->write();
					$statusUpdates['modified'][$node->ID] = array(
						'TreeTitle' => $node->TreeTitle
					);
				} else if(is_numeric($id)) {
					// Nodes that weren't "actually moved" shouldn't be registered as 
					// having been edited; do a direct SQL update instead
					++$counter;
					DB::query(sprintf("UPDATE \"%s\" SET \"Sort\" = %d WHERE \"ID\" = '%d'", $className, $counter, $id));
				}
			}
			
			$this->response->addHeader('X-Status', rawurlencode(_t('LeftAndMain.REORGANISATIONSUCCESSFUL', 'Reorganised the site tree successfully.')));
		}

		return Convert::raw2json($statusUpdates);
	}

	public function CanOrganiseSitetree() {
		return !Permission::check('SITETREE_REORGANISE') && !Permission::check('ADMIN') ? false : true;
	}
	
	/**
	 * Retrieves an edit form, either for display, or to process submitted data.
	 * Also used in the template rendered through {@link Right()} in the $EditForm placeholder.
	 * 
	 * This is a "pseudo-abstract" methoed, usually connected to a {@link getEditForm()}
	 * method in an entwine subclass. This method can accept a record identifier,
	 * selected either in custom logic, or through {@link currentPageID()}. 
	 * The form usually construct itself from {@link DataObject->getCMSFields()} 
	 * for the specific managed subclass defined in {@link LeftAndMain::$tree_class}.
	 * 
	 * @param HTTPRequest $request Optionally contains an identifier for the
	 *  record to load into the form.
	 * @return Form Should return a form regardless wether a record has been found.
	 *  Form might be readonly if the current user doesn't have the permission to edit
	 *  the record.
	 */
	/**
	 * @return Form
	 */
	function EditForm($request = null) {
		return $this->getEditForm();
	}

	/**
	 * Calls {@link SiteTree->getCMSFields()}
	 * 
	 * @param Int $id
	 * @param FieldList $fields
	 * @return Form
	 */
	public function getEditForm($id = null, $fields = null) {
		if(!$id) $id = $this->currentPageID();
		
		if(is_object($id)) {
			$record = $id;
		} else {
			$record = $this->getRecord($id);
			if($record && !$record->canView()) return Security::permissionFailure($this);
		}

		if($record) {
			$fields = ($fields) ? $fields : $record->getCMSFields();
			if ($fields == null) {
				user_error(
					"getCMSFields() returned null  - it should return a FieldList object. 
					Perhaps you forgot to put a return statement at the end of your method?", 
					E_USER_ERROR
				);
			}
			
			// Add hidden fields which are required for saving the record
			// and loading the UI state
			if(!$fields->dataFieldByName('ClassName')) {
				$fields->push(new HiddenField('ClassName'));
			}
			if(
				Object::has_extension($this->stat('tree_class'), 'Hierarchy') 
				&& !$fields->dataFieldByName('ParentID')
			) {
				$fields->push(new HiddenField('ParentID'));
			}

			// Added in-line to the form, but plucked into different view by LeftAndMain.Preview.js upon load
			if(in_array('CMSPreviewable', class_implements($record))) {
				$navField = new LiteralField('SilverStripeNavigator', $this->getSilverStripeNavigator());
				$navField->setAllowHTML(true);
				$fields->push($navField);
			}
			
			if($record->hasMethod('getAllCMSActions')) {
				$actions = $record->getAllCMSActions();
			} else {
				$actions = $record->getCMSActions();
				// add default actions if none are defined
				if(!$actions || !$actions->Count()) {
					if($record->hasMethod('canEdit') && $record->canEdit()) {
						$actions->push(
							FormAction::create('save',_t('CMSMain.SAVE','Save'))
								->addExtraClass('ss-ui-action-constructive')->setAttribute('data-icon', 'accept')
						);
					}
					if($record->hasMethod('canDelete') && $record->canDelete()) {
						$actions->push(
							FormAction::create('delete',_t('ModelAdmin.DELETE','Delete'))
								->addExtraClass('ss-ui-action-destructive')
						);
					}
				}
			}

			// Use <button> to allow full jQuery UI styling
			$actionsFlattened = $actions->dataFields();
			if($actionsFlattened) foreach($actionsFlattened as $action) $action->setUseButtonTag(true);
			
			$form = new Form($this, "EditForm", $fields, $actions);
			$form->addExtraClass('cms-edit-form');
			$form->loadDataFrom($record);
			$form->setTemplate($this->getTemplatesWithSuffix('_EditForm'));
			$form->setAttribute('data-pjax-fragment', 'CurrentForm');
			
			// Set this if you want to split up tabs into a separate header row
			// if($form->Fields()->hasTabset()) $form->Fields()->findOrMakeTab('Root')->setTemplate('CMSTabSet');
			
			// Add a default or custom validator.
			// @todo Currently the default Validator.js implementation
			//  adds javascript to the document body, meaning it won't
			//  be included properly if the associated fields are loaded
			//  through ajax. This means only serverside validation
			//  will kick in for pages+validation loaded through ajax.
			//  This will be solved by using less obtrusive javascript validation
			//  in the future, see http://open.silverstripe.com/ticket/2915 and
			//  http://open.silverstripe.com/ticket/3386
			if($record->hasMethod('getCMSValidator')) {
				$validator = $record->getCMSValidator();
				// The clientside (mainly LeftAndMain*.js) rely on ajax responses
				// which can be evaluated as javascript, hence we need
				// to override any global changes to the validation handler.
				$form->setValidator($validator);
			} else {
				$form->unsetValidator();
			}
		
			if($record->hasMethod('canEdit') && !$record->canEdit()) {
				$readonlyFields = $form->Fields()->makeReadonly();
				$form->setFields($readonlyFields);
			}
		} else {
			$form = $this->EmptyForm();
		}
		
		return $form;
	}	
	
	/**
	 * Returns a placeholder form, used by {@link getEditForm()} if no record is selected.
	 * Our javascript logic always requires a form to be present in the CMS interface.
	 * 
	 * @return Form
	 */
	function EmptyForm() {
		$form = new Form(
			$this, 
			"EditForm", 
			new FieldList(
				// new HeaderField(
				// 	'WelcomeHeader',
				// 	$this->getApplicationName()
				// ),
				// new LiteralField(
				// 	'WelcomeText',
				// 	sprintf('<p id="WelcomeMessage">%s %s. %s</p>',
				// 		_t('LeftAndMain_right.ss.WELCOMETO','Welcome to'),
				// 		$this->getApplicationName(),
				// 		_t('CHOOSEPAGE','Please choose an item from the left.')
				// 	)
				// )
			), 
			new FieldList()
		);
		$form->unsetValidator();
		$form->addExtraClass('cms-edit-form');
		$form->addExtraClass('root-form');
		$form->setTemplate($this->getTemplatesWithSuffix('_EditForm'));
		$form->setAttribute('data-pjax-fragment', 'CurrentForm');
		
		return $form;
	}
	
	/**
	 * Return the CMS's HTML-editor toolbar
	 */
	public function EditorToolbar() {
		return HtmlEditorField_Toolbar::create($this, "EditorToolbar");
	}

	/**
	 * Renders a panel containing tools which apply to all displayed
	 * "content" (mostly through {@link EditForm()}), for example a tree navigation or a filter panel.
	 * Auto-detects applicable templates by naming convention: "<controller classname>_Tools.ss",
	 * and takes the most specific template (see {@link getTemplatesWithSuffix()}).
	 * To explicitly disable the panel in the subclass, simply create a more specific, empty template.
	 * 
	 * @return String HTML
	 */
	public function Tools() {
		$templates = $this->getTemplatesWithSuffix('_Tools');
		if($templates) {
			$viewer = new SSViewer($templates);
			return $viewer->process($this);	
		} else {
			return false;
		}
	}

	/**
	 * Renders a panel containing tools which apply to the currently displayed edit form.
	 * The main difference to {@link Tools()} is that the panel is displayed within
	 * the element structure of the form panel (rendered through {@link EditForm}).
	 * This means the panel will be loaded alongside new forms, and refreshed upon save,
	 * which can mean a performance hit, depending on how complex your panel logic gets.
	 * Any form fields contained in the returned markup will also be submitted with the main form,
	 * which might be desired depending on the implementation details.
	 * 
	 * @return String HTML
	 */
	public function EditFormTools() {
		$templates = $this->getTemplatesWithSuffix('_EditFormTools');
		if($templates) {
			$viewer = new SSViewer($templates);
			return $viewer->process($this);	
		} else {
			return false;
		}
	}
	
	/**
	 * Batch Actions Handler
	 */
	function batchactions() {
		return new CMSBatchActionHandler($this, 'batchactions', $this->stat('tree_class'));
	}
	
	/**
	 * @return Form
	 */
	function BatchActionsForm() {
		$actions = $this->batchactions()->batchActionList();
		$actionsMap = array('-1' => _t('LeftAndMain.DropdownBatchActionsDefault', 'Actions'));
		foreach($actions as $action) $actionsMap[$action->Link] = $action->Title;
		
		$form = new Form(
			$this,
			'BatchActionsForm',
			new FieldList(
				new HiddenField('csvIDs'),
				DropdownField::create(
					'Action',
					false,
					$actionsMap
				)->setAttribute('autocomplete', 'off')
			),
			new FieldList(
				// TODO i18n
				new FormAction('submit', _t('Form.SubmitBtnLabel', "Go"))
			)
		);
		$form->addExtraClass('cms-batch-actions nostyle');
		$form->unsetValidator();
		
		return $form;
	}
	
	public function printable() {
		$form = $this->getEditForm($this->currentPageID());
		if(!$form) return false;
		
		$form->transform(new PrintableTransformation());
		$form->setActions(null);

		Requirements::clear();
		Requirements::css(FRAMEWORK_ADMIN_DIR . '/css/LeftAndMain_printable.css');
		return array(
			"PrintForm" => $form
		);
	}

	/**
	 * Used for preview controls, mainly links which switch between different states of the page.
	 * 
	 * @return ArrayData
	 */
	function getSilverStripeNavigator() {
		$page = $this->currentPage();
		if($page) {
			$navigator = new SilverStripeNavigator($page);
			return $navigator->renderWith($this->getTemplatesWithSuffix('_SilverStripeNavigator'));
		} else {
			return false;
		}
	}

	/**
	 * Identifier for the currently shown record,
	 * in most cases a database ID. Inspects the following
	 * sources (in this order):
	 * - GET/POST parameter named 'ID'
	 * - URL parameter named 'ID'
	 * - Session value namespaced by classname, e.g. "CMSMain.currentPage"
	 * 
	 * @return int 
	 */
	public function currentPageID() {
		if($this->request->requestVar('ID') && is_numeric($this->request->requestVar('ID')))	{
			return $this->request->requestVar('ID');
		} elseif (isset($this->urlParams['ID']) && is_numeric($this->urlParams['ID'])) {
			return $this->urlParams['ID'];
		} elseif(Session::get($this->sessionNamespace() . ".currentPage")) {
			return Session::get($this->sessionNamespace() . ".currentPage");
		} else {
			return null;
		}
	}

	/**
	 * Forces the current page to be set in session,
	 * which can be retrieved later through {@link currentPageID()}.
	 * Keep in mind that setting an ID through GET/POST or
	 * as a URL parameter will overrule this value.
	 * 
	 * @param int $id
	 */
	public function setCurrentPageID($id) {
		Session::set($this->sessionNamespace() . ".currentPage", $id);
	}

	/**
	 * Uses {@link getRecord()} and {@link currentPageID()}
	 * to get the currently selected record.
	 * 
	 * @return DataObject
	 */
	public function currentPage() {
		return $this->getRecord($this->currentPageID());
	}

	/**
	 * Compares a given record to the currently selected one (if any).
	 * Used for marking the current tree node.
	 * 
	 * @return boolean
	 */
	public function isCurrentPage(DataObject $record) {
		return ($record->ID == $this->currentPageID());
	}
	
	/**
	 * @return String
	 */
	protected function sessionNamespace() {
		$override = $this->stat('session_namespace');
		return $override ? $override : $this->class;
	}

	/**
	 * URL to a previewable record which is shown through this controller.
	 * The controller might not have any previewable content, in which case 
	 * this method returns FALSE.
	 * 
	 * @return String|boolean
	 */
	public function LinkPreview() {
		return false;
	}

	/**
	 * Return the version number of this application.
	 * Uses the subversion path information in <mymodule>/silverstripe_version
	 * (automacially replaced by build scripts).
	 * 
	 * @return string
	 */
	public function CMSVersion() {
		$frameworkVersion = file_get_contents(FRAMEWORK_PATH . '/silverstripe_version');
		if(!$frameworkVersion) $frameworkVersion = _t('LeftAndMain.VersionUnknown', 'Unknown');
		
		return sprintf(
			"Framework: %s",
			$frameworkVersion
		);
	}
	
	/**
	 * @return array
	 */
	function SwitchView() { 
		if($page = $this->currentPage()) { 
			$nav = SilverStripeNavigator::get_for_record($page); 
			return $nav['items']; 
		} 
	}
	
	/**
	 * @return SiteConfig
	 */
	function SiteConfig() {
		return (class_exists('SiteConfig')) ? SiteConfig::current_site_config() : null;
	}

	/**
	 * The application name. Customisable by calling
	 * LeftAndMain::setApplicationName() - the first parameter.
	 * 
	 * @var String
	 */
	static $application_name = 'SilverStripe';
	
	/**
	 * @param String $name
	 */
	static function setApplicationName($name) {
		self::$application_name = $name;
	}

	/**
	 * Get the application name.
	 *
	 * @return string
	 */
	function getApplicationName() {
		return self::$application_name;
	}
	
	/**
	 * @return string
	 */
	function Title() {
		$app = $this->getApplicationName();
		
		return ($section = $this->SectionTitle()) ? sprintf('%s - %s', $app, $section) : $app;
	}

	/**
	 * Return the title of the current section. Either this is pulled from
	 * the current panel's menu_title or from the first active menu
	 *
	 * @return string
	 */
	function SectionTitle() {
		$class = get_class($this);
		$defaultTitle = LeftAndMain::menu_title_for_class($class);
		if($title = _t("{$class}.MENUTITLE", $defaultTitle)) return $title;

		foreach($this->MainMenu() as $menuItem) {
			if($menuItem->LinkingMode != 'link') return $menuItem->Title;
		}
	}

	/**
	 * Return the base directory of the tiny_mce codebase
	 */
	function MceRoot() {
		return MCE_ROOT;
	}
	
	/**
	 * Same as {@link ViewableData->CSSClasses()}, but with a changed name
	 * to avoid problems when using {@link ViewableData->customise()}
	 * (which always returns "ArrayData" from the $original object).
	 * 
	 * @return String
	 */
	function BaseCSSClasses() {
		return $this->CSSClasses('Controller');
	}
	
	function IsPreviewExpanded() {
		return ($this->request->getVar('cms-preview-expanded'));
	}

	/**
	 * @return String
	 */
	function Locale() {
		return DBField::create_field('DBLocale', i18n::get_locale());
	}

	function providePermissions() {
		$perms = array(
			"CMS_ACCESS_LeftAndMain" => array(
				'name' => _t('CMSMain.ACCESSALLINTERFACES', 'Access to all CMS sections'),
				'category' => _t('Permission.CMS_ACCESS_CATEGORY', 'CMS Access'),
				'help' => _t('CMSMain.ACCESSALLINTERFACESHELP', 'Overrules more specific access settings.'),
				'sort' => -100
			)
		);

		// Add any custom ModelAdmin subclasses. Can't put this on ModelAdmin itself
		// since its marked abstract, and needs to be singleton instanciated.
		foreach(ClassInfo::subclassesFor('ModelAdmin') as $i => $class) {
			if($class == 'ModelAdmin') continue;
			if(ClassInfo::classImplements($class, 'TestOnly')) continue;

			$title = _t("{$class}.MENUTITLE", LeftAndMain::menu_title_for_class($class));
			$perms["CMS_ACCESS_" . $class] = array(
				'name' => _t(
					'CMSMain.ACCESS', 
					"Access to '{title}' section",
					"Item in permission selection identifying the admin section. Example: Access to 'Files & Images'",
					array('title' => $title)
				),
				'category' => _t('Permission.CMS_ACCESS_CATEGORY', 'CMS Access')
			);
		}

		return $perms;
	}
	
	/**
	 * Register the given javascript file as required in the CMS.
	 * Filenames should be relative to the base, eg, FRAMEWORK_DIR . '/javascript/loader.js'
	 */
	public static function require_javascript($file) {
		self::$extra_requirements['javascript'][] = array($file);
	}
	
	/**
	 * Register the given stylesheet file as required.
	 * 
	 * @param $file String Filenames should be relative to the base, eg, THIRDPARTY_DIR . '/tree/tree.css'
	 * @param $media String Comma-separated list of media-types (e.g. "screen,projector") 
	 * @see http://www.w3.org/TR/REC-CSS2/media.html
	 */
	public static function require_css($file, $media = null) {
		self::$extra_requirements['css'][] = array($file, $media);
	}
	
	/**
	 * Register the given "themeable stylesheet" as required.
	 * Themeable stylesheets have globally unique names, just like templates and PHP files.
	 * Because of this, they can be replaced by similarly named CSS files in the theme directory.
	 * 
	 * @param $name String The identifier of the file.  For example, css/MyFile.css would have the identifier "MyFile"
	 * @param $media String Comma-separated list of media-types (e.g. "screen,projector") 
	 */
	static function require_themed_css($name, $media = null) {
		self::$extra_requirements['themedcss'][] = array($name, $media);
	}
	
}

/**
 * @package cms
 * @subpackage core
 */
class LeftAndMainMarkingFilter {
	
	/**
	 * @var array Request params (unsanitized)
	 */
	protected $params = array();
	
	/**
	 * @param array $params Request params (unsanitized)
	 */
	function __construct($params = null) {
		$this->ids = array();
		$this->expanded = array();
		$parents = array();
		
		$q = $this->getQuery($params);
		$res = $q->execute();
		if (!$res) return;
		
		// And keep a record of parents we don't need to get parents 
		// of themselves, as well as IDs to mark
		foreach($res as $row) {
			if ($row['ParentID']) $parents[$row['ParentID']] = true;
			$this->ids[$row['ID']] = true;
		}
		
		// We need to recurse up the tree, 
		// finding ParentIDs for each ID until we run out of parents
		while (!empty($parents)) {
			$res = DB::query('SELECT "ParentID", "ID" FROM "SiteTree" WHERE "ID" in ('.implode(',',array_keys($parents)).')');
			$parents = array();

			foreach($res as $row) {
				if ($row['ParentID']) $parents[$row['ParentID']] = true;
				$this->ids[$row['ID']] = true;
				$this->expanded[$row['ID']] = true;
			}
		}
	}
	
	protected function getQuery($params) {
		$where = array();
		
		$SQL_params = Convert::raw2sql($params);
		if(isset($SQL_params['ID'])) unset($SQL_params['ID']);
		foreach($SQL_params as $name => $val) {
			switch($name) {
				default:
					// Partial string match against a variety of fields 
					if(!empty($val) && singleton("SiteTree")->hasDatabaseField($name)) {
						$where[] = "\"$name\" LIKE '%$val%'";
					}
			}
		}
		
		return new SQLQuery(
			array("ParentID", "ID"),
			'SiteTree',
			$where
		);
	}
	
	function mark($node) {
		$id = $node->ID;
		if(array_key_exists((int) $id, $this->expanded)) $node->markOpened();
		return array_key_exists((int) $id, $this->ids) ? $this->ids[$id] : false;
	}
}

/**
 * Allow overriding finished state for faux redirects.
 */
class LeftAndMain_HTTPResponse extends SS_HTTPResponse {

	protected $isFinished = false;

	function isFinished() {
		return (parent::isFinished() || $this->isFinished);
	}

	function setIsFinished($bool) {
		$this->isFinished = $bool;
	}

}

/**
 * Wrapper around objects being displayed in a tree.
 * Caution: Volatile API.
 *
 * @todo Implement recursive tree node rendering
 */
class LeftAndMain_TreeNode extends ViewableData {
	
	/**
	 * @var obj
	 */
	protected $obj;

	/**
	 * @var String Edit link to the current record in the CMS
	 */
	protected $link;

	/**
	 * @var Bool
	 */
	protected $isCurrent;

	function __construct($obj, $link = null, $isCurrent = false) {
		$this->obj = $obj;
		$this->link = $link;
		$this->isCurrent = $isCurrent;
	}

	/**
	 * Returns template, for further processing by {@link Hierarchy->getChildrenAsUL()}.
	 * Does not include closing tag to allow this method to inject its own children.
	 *
	 * @todo Remove hardcoded assumptions around returning an <li>, by implementing recursive tree node rendering
	 * 
	 * @return String
	 */
	function forTemplate() {
		$obj = $this->obj;
		return "<li id=\"record-$obj->ID\" data-id=\"$obj->ID\" data-pagetype=\"$obj->ClassName\" class=\"" . $this->getClasses() . "\">" .
			"<ins class=\"jstree-icon\">&nbsp;</ins>" .
			"<a href=\"" . $this->getLink() . "\" title=\"" .
			_t('LeftAndMain.PAGETYPE','Page type: ') .
			"$obj->class\" ><ins class=\"jstree-icon\">&nbsp;</ins><span class=\"text\">" . ($obj->TreeTitle). 
			"</span></a>";
	}

	function getClasses() {
		$classes = $this->obj->CMSTreeClasses();
		if($this->isCurrent) $classes .= " current";
		$flags = $this->obj->hasMethod('getStatusFlags') ? $this->obj->getStatusFlags() : false;
		if($flags) $classes .= ' ' . implode(' ', array_keys($flags));
		return $classes;
	}

	function getObj() {
		return $this->obj;
	}

	function setObj($obj) {
		$this->obj = $obj;
		return $this;
	}

	function getLink() {
		return $this->link;
	}

	function setLink($link) {
		$this->link = $link;
		return $this;
	}

	function getIsCurrent() {
		return $this->isCurrent;
	}

	function setIsCurrent($bool) {
		$this->isCurrent = $bool;
		return $this;
	}

}
