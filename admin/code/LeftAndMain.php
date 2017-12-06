<?php

/**
 * @package framework
 * @subpackage admin
 */

/**
 * LeftAndMain is the parent class of all the two-pane views in the CMS.
 * If you are wanting to add more areas to the CMS, you can do it by subclassing LeftAndMain.
 *
 * This is essentially an abstract class which should be subclassed.
 * See {@link CMSMain} for a good example.
 */
class LeftAndMain extends Controller implements PermissionProvider {

	/**
	 * The 'base' url for CMS administration areas.
	 * Note that if this is changed, many javascript
	 * behaviours need to be updated with the correct url
	 *
	 * @config
	 * @var string $url_base
	 */
	private static $url_base = "admin";

	/**
	 * The current url segment attached to the LeftAndMain instance
	 *
	 * @config
	 * @var string
	 */
	private static $url_segment;

	/**
	 * @config
	 * @var string
	 */
	private static $url_rule = '/$Action/$ID/$OtherID';

	/**
	 * @config
	 * @var string
	 */
	private static $menu_title;

	/**
	 * @config
	 * @var string
	 */
	private static $menu_icon;

	/**
	 * @config
	 * @var int
	 */
	private static $menu_priority = 0;

	/**
	 * @config
	 * @var int
	 */
	private static $url_priority = 50;

	/**
	 * A subclass of {@link DataObject}.
	 *
	 * Determines what is managed in this interface, through
	 * {@link getEditForm()} and other logic.
	 *
	 * @config
	 * @var string
	 */
	private static $tree_class = null;

	/**
	 * The url used for the link in the Help tab in the backend
	 *
	 * @config
	 * @var string
	 */
	private static $help_link = '//userhelp.silverstripe.org/framework/en/3.6';

	/**
	 * @var array
	 */
	private static $allowed_actions = array(
		'index',
		'save',
		'savetreenode',
		'getsubtree',
		'updatetreenodes',
		'printable',
		'show',
		'EditorToolbar',
		'EditForm',
		'AddForm',
		'batchactions',
		'BatchActionsForm',
	);

	/**
	 * @config
	 * @var Array Codes which are required from the current user to view this controller.
	 * If multiple codes are provided, all of them are required.
	 * All CMS controllers require "CMS_ACCESS_LeftAndMain" as a baseline check,
	 * and fall back to "CMS_ACCESS_<class>" if no permissions are defined here.
	 * See {@link canView()} for more details on permission checks.
	 */
	private static $required_permission_codes;

	/**
	 * @config
	 * @var String Namespace for session info, e.g. current record.
	 * Defaults to the current class name, but can be amended to share a namespace in case
	 * controllers are logically bundled together, and mainly separated
	 * to achieve more flexible templating.
	 */
	private static $session_namespace;

	/**
	 * Register additional requirements through the {@link Requirements} class.
	 * Used mainly to work around the missing "lazy loading" functionality
	 * for getting css/javascript required after an ajax-call (e.g. loading the editform).
	 *
	 * YAML configuration example:
	 * <code>
	 * LeftAndMain:
	 *   extra_requirements_javascript:
	 *     mysite/javascript/myscript.js:
	 * </code>
	 *
	 * @config
	 * @var array
	 */
	private static $extra_requirements_javascript = array();

	/**
	 * YAML configuration example:
	 * <code>
	 * LeftAndMain:
	 *   extra_requirements_css:
	 *     mysite/css/mystyle.css:
	 *       media: screen
	 * </code>
	 *
	 * @config
	 * @var array See {@link extra_requirements_javascript}
	 */
	private static $extra_requirements_css = array();

	/**
	 * @config
	 * @var array See {@link extra_requirements_javascript}
	 */
	private static $extra_requirements_themedCss = array();

	/**
	 * If true, call a keepalive ping every 5 minutes from the CMS interface,
	 * to ensure that the session never dies.
	 *
	 * @config
	 * @var boolean
	 */
	private static $session_keepalive_ping = true;

	/**
	 * Value of X-Frame-Options header
	 *
	 * @config
	 * @var string
	 */
	private static $frame_options = 'SAMEORIGIN';

	/**
	 * @var PjaxResponseNegotiator
	 */
	protected $responseNegotiator;

	/**
	 * @param Member $member
	 * @return boolean
	 */
	public function canView($member = null) {
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
	public function init() {
		parent::init();

		Config::inst()->update('SSViewer', 'rewrite_hash_links', false);
		Config::inst()->update('ContentNegotiator', 'enabled', false);

		// set language
		$member = Member::currentUser();
		if(!empty($member->Locale)) i18n::set_locale($member->Locale);
		if(!empty($member->DateFormat)) i18n::config()->date_format = $member->DateFormat;
		if(!empty($member->TimeFormat)) i18n::config()->time_format = $member->TimeFormat;

		// can't be done in cms/_config.php as locale is not set yet
		CMSMenu::add_link(
			'Help',
			_t('LeftAndMain.HELP', 'Help', 'Menu title'),
			$this->config()->help_link,
			-2,
			array(
				'target' => '_blank'
			)
		);

		// Allow customisation of the access check by a extension
		// Also all the canView() check to execute Controller::redirect()
		if(!$this->canView() && !$this->getResponse()->isFinished()) {
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
				'default' => _t(
					'LeftAndMain.PERMDEFAULT',
					"You must be logged in to access the administration area; please enter your credentials below."
				),
				'alreadyLoggedIn' => _t(
					'LeftAndMain.PERMALREADY',
					"I'm sorry, but you can't access that part of the CMS.  If you want to log in as someone else, do"
					. " so below."
				),
				'logInAgain' => _t(
					'LeftAndMain.PERMAGAIN',
					"You have been logged out of the CMS.  If you would like to log in again, enter a username and"
					. " password below."
				),
			);

			return Security::permissionFailure($this, $messageSet);
		}

		// Don't continue if there's already been a redirection request.
		if($this->redirectedTo()) return;

		// Audit logging hook
		if(empty($_REQUEST['executeForm']) && !$this->getRequest()->isAjax()) $this->extend('accessedCMS');

		// Set the members html editor config
		if(Member::currentUser()) {
			HtmlEditorConfig::set_active(Member::currentUser()->getHtmlEditorConfigForCMS());
		}

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
			} elseif(Config::inst()->get('SSViewer', 'theme_enabled') && Config::inst()->get('SSViewer', 'theme')) {
				$theme = Config::inst()->get('SSViewer', 'theme');
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
				FRAMEWORK_DIR . '/javascript/i18n.js',
				FRAMEWORK_DIR . '/javascript/TreeDropdownField.js',
				FRAMEWORK_DIR . '/javascript/DateField.js',
				FRAMEWORK_DIR . '/javascript/HtmlEditorField.js',
				FRAMEWORK_DIR . '/javascript/TabSet.js',
				FRAMEWORK_ADMIN_DIR . '/javascript/ssui.core.js',
				FRAMEWORK_DIR . '/javascript/GridField.js',
			)
		);

		if (Director::isDev()) Requirements::javascript(FRAMEWORK_ADMIN_DIR . '/javascript/leaktools.js');

		$leftAndMainIncludes = array_unique(array_merge(
			array(
				FRAMEWORK_ADMIN_DIR . '/javascript/LeftAndMain.Layout.js',
				FRAMEWORK_ADMIN_DIR . '/javascript/LeftAndMain.js',
				FRAMEWORK_ADMIN_DIR . '/javascript/LeftAndMain.ActionTabSet.js',
				FRAMEWORK_ADMIN_DIR . '/javascript/LeftAndMain.Panel.js',
				FRAMEWORK_ADMIN_DIR . '/javascript/LeftAndMain.Tree.js',
				FRAMEWORK_ADMIN_DIR . '/javascript/LeftAndMain.Content.js',
				FRAMEWORK_ADMIN_DIR . '/javascript/LeftAndMain.EditForm.js',
				FRAMEWORK_ADMIN_DIR . '/javascript/LeftAndMain.Menu.js',
				FRAMEWORK_ADMIN_DIR . '/javascript/LeftAndMain.Preview.js',
				FRAMEWORK_ADMIN_DIR . '/javascript/LeftAndMain.BatchActions.js',
				FRAMEWORK_ADMIN_DIR . '/javascript/LeftAndMain.FieldHelp.js',
				FRAMEWORK_ADMIN_DIR . '/javascript/LeftAndMain.FieldDescriptionToggle.js',
				FRAMEWORK_ADMIN_DIR . '/javascript/LeftAndMain.TreeDropdownField.js',
			),
			Requirements::add_i18n_javascript(FRAMEWORK_DIR . '/javascript/lang', true, true),
			Requirements::add_i18n_javascript(FRAMEWORK_ADMIN_DIR . '/javascript/lang', true, true)
		));

		if($this->config()->session_keepalive_ping) {
			$leftAndMainIncludes[] = FRAMEWORK_ADMIN_DIR . '/javascript/LeftAndMain.Ping.js';
		}

		Requirements::combine_files('leftandmain.js', $leftAndMainIncludes);

		// TODO Confuses jQuery.ondemand through document.write()
		if (Director::isDev()) {
			Requirements::javascript(THIRDPARTY_DIR . '/jquery-entwine/src/jquery.entwine.inspector.js');
		}

		HtmlEditorConfig::require_js();

		Requirements::css(FRAMEWORK_ADMIN_DIR . '/thirdparty/jquery-notice/jquery.notice.css');
		Requirements::css(THIRDPARTY_DIR . '/jquery-ui-themes/smoothness/jquery-ui.css');
		Requirements::css(FRAMEWORK_ADMIN_DIR .'/thirdparty/chosen/chosen/chosen.css');
		Requirements::css(THIRDPARTY_DIR . '/jstree/themes/apple/style.css');
		Requirements::css(FRAMEWORK_DIR . '/css/TreeDropdownField.css');
		Requirements::css(FRAMEWORK_ADMIN_DIR . '/css/screen.css');
		Requirements::css(FRAMEWORK_DIR . '/css/GridField.css');

		// Browser-specific requirements
		$ie = isset($_SERVER['HTTP_USER_AGENT']) ? strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE') !== false : false;
		if($ie) {
			$version = substr($_SERVER['HTTP_USER_AGENT'], $ie + 5, 3);

			if($version == 7) {
				Requirements::css(FRAMEWORK_ADMIN_DIR . '/css/ie7.css');
			} else if($version == 8) {
				Requirements::css(FRAMEWORK_ADMIN_DIR . '/css/ie8.css');
			}
		}

		// Custom requirements
		$extraJs = $this->stat('extra_requirements_javascript');

		if($extraJs) {
			foreach($extraJs as $file => $config) {
				if(is_numeric($file)) {
					$file = $config;
				}

				Requirements::javascript($file);
			}
		}

		$extraCss = $this->stat('extra_requirements_css');

		if($extraCss) {
			foreach($extraCss as $file => $config) {
				if(is_numeric($file)) {
					$file = $config;
					$config = array();
				}

				Requirements::css($file, isset($config['media']) ? $config['media'] : null);
			}
		}

		$extraThemedCss = $this->stat('extra_requirements_themedCss');

		if($extraThemedCss) {
			foreach ($extraThemedCss as $file => $config) {
				if(is_numeric($file)) {
					$file = $config;
					$config = array();
				}

				Requirements::themedCSS($file, isset($config['media']) ? $config['media'] : null);
			}
		}

		$dummy = null;
		$this->extend('init', $dummy);

		// The user's theme shouldn't affect the CMS, if, for example, they have
		// replaced TableListField.ss or Form.ss.
		Config::inst()->update('SSViewer', 'theme_enabled', false);

		//set the reading mode for the admin to stage
		Versioned::reading_stage('Stage');
	}

	public function handleRequest(SS_HTTPRequest $request, DataModel $model = null) {
		try {
			$response = parent::handleRequest($request, $model);
		} catch(ValidationException $e) {
			// Nicer presentation of model-level validation errors
			$msgs = _t('LeftAndMain.ValidationError', 'Validation error') . ': '
				. $e->getMessage();
			$e = new SS_HTTPResponse_Exception($msgs, 403);
			$errorResponse = $e->getResponse();
			$errorResponse->addHeader('Content-Type', 'text/plain');
			$errorResponse->addHeader('X-Status', rawurlencode($msgs));
			$e->setResponse($errorResponse);
			throw $e;
		}

		$title = $this->Title();
		if(!$response->getHeader('X-Controller')) $response->addHeader('X-Controller', $this->class);
		if(!$response->getHeader('X-Title')) $response->addHeader('X-Title', urlencode($title));

		// Prevent clickjacking, see https://developer.mozilla.org/en-US/docs/HTTP/X-Frame-Options
		$originalResponse = $this->getResponse();
		$originalResponse->addHeader('X-Frame-Options', $this->config()->frame_options);
		$originalResponse->addHeader('Vary', 'X-Requested-With');

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
	public function redirect($url, $code=302) {
		if($this->getRequest()->isAjax()) {
			$response = $this->getResponse();
			$response->addHeader('X-ControllerURL', $url);
			if($this->getRequest()->getHeader('X-Pjax') && !$response->getHeader('X-Pjax')) {
				$response->addHeader('X-Pjax', $this->getRequest()->getHeader('X-Pjax'));
			}
			$newResponse = new LeftAndMain_HTTPResponse(
				$response->getBody(),
				$response->getStatusCode(),
				$response->getStatusDescription()
			);
			foreach($response->getHeaders() as $k => $v) {
				$newResponse->addHeader($k, $v);
			}
			$newResponse->setIsFinished(true);
			$this->setResponse($newResponse);
			return ''; // Actual response will be re-requested by client
		} else {
			parent::redirect($url, $code);
		}
	}

	public function index($request) {
		return $this->getResponseNegotiator()->respond($request);
	}

	/**
	 * If this is set to true, the "switchView" context in the
	 * template is shown, with links to the staging and publish site.
	 *
	 * @return boolean
	 */
	public function ShowSwitchView() {
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
		if($this->config()->url_segment) {
			$segment = $this->config()->get('url_segment', Config::FIRST_SET);
		} else {
			$segment = $this->class;
		};

		$link = Controller::join_links(
			$this->stat('url_base', true),
			$segment,
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
	public static function menu_title_for_class($class) {
		$title = Config::inst()->get($class, 'menu_title', Config::FIRST_SET);
		if(!$title) $title = preg_replace('/Admin$/', '', $class);
		return $title;
	}

	/**
	 * Return styling for the menu icon, if a custom icon is set for this class
	 *
	 * Example: static $menu-icon = '/path/to/image/';
	 * @param string $class
	 * @return string
	 */
	public static function menu_icon_for_class($class) {
		$icon = Config::inst()->get($class, 'menu_icon', Config::FIRST_SET);
		if (!empty($icon)) {
			$class = strtolower(Convert::raw2htmlname(str_replace('\\', '-', $class)));
			return ".icon.icon-16.icon-{$class} { background-image: url('{$icon}'); } ";
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
				$this->getResponse()
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
						"AttributesHTML" => $menuItem->getAttributesHTML(),
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
	 * Return a list of appropriate templates for this class, with the given suffix using
	 * {@link SSViewer::get_templates_by_class()}
	 *
	 * @return array
	 */
	public function getTemplatesWithSuffix($suffix) {
		return SSViewer::get_templates_by_class(get_class($this), $suffix, 'LeftAndMain');
	}

	public function Content() {
		return $this->renderWith($this->getTemplatesWithSuffix('_Content'));
	}

	public function getRecord($id) {
		$className = $this->stat('tree_class');
		if($className && $id instanceof $className) {
			return $id;
		} else if($className && $id == 'root') {
			return singleton($className);
		} else if($className && is_numeric($id)) {
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
						'Title' => ($ancestor->MenuTitle) ? $ancestor->MenuTitle : $ancestor->Title,
						'Link' => ($unlinked) ? false : Controller::join_links($this->Link('show'), $ancestor->ID)
					)));
				}
			} else {
				$items->push(new ArrayData(array(
					'Title' => ($record->MenuTitle) ? $record->MenuTitle : $record->Title,
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
	 * Gets the current search filter for this request, if available
	 *
	 * @throws InvalidArgumentException
	 * @return LeftAndMain_SearchFilter
	 */
	protected function getSearchFilter() {
		// Check for given FilterClass
		$params = $this->getRequest()->getVar('q');
		if(empty($params['FilterClass'])) {
			return null;
		}

		// Validate classname
		$filterClass = $params['FilterClass'];
		$filterInfo = new ReflectionClass($filterClass);
		if(!$filterInfo->implementsInterface('LeftAndMain_SearchFilter')) {
			throw new InvalidArgumentException(sprintf('Invalid filter class passed: %s', $filterClass));
		}

		return Injector::inst()->createWithArgs($filterClass, array($params));
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
	public function getSiteTreeFor($className, $rootID = null, $childrenMethod = null, $numChildrenMethod = null,
			$filterFunction = null, $nodeCountThreshold = 30) {

		// Filter criteria
		$filter = $this->getSearchFilter();

		// Default childrenMethod and numChildrenMethod
		if(!$childrenMethod) $childrenMethod = ($filter && $filter->getChildrenMethod())
			? $filter->getChildrenMethod()
			: 'AllChildrenIncludingDeleted';

		if(!$numChildrenMethod) {
			$numChildrenMethod = 'numChildren';
			if($filter && $filter->getNumChildrenMethod()) {
				$numChildrenMethod = $filter->getNumChildrenMethod();
			}
		}
		if(!$filterFunction && $filter) {
			$filterFunction = function($node) use($filter) {
				return $filter->isPageIncluded($node);
			};
		}

		// Get the tree root
		$record = ($rootID) ? $this->getRecord($rootID) : null;
		$obj = $record ? $record : singleton($className);

		// Get the current page
		// NOTE: This *must* be fetched before markPartialTree() is called, as this
		// causes the Hierarchy::$marked cache to be flushed (@see CMSMain::getRecord)
		// which means that deleted pages stored in the marked tree would be removed
		$currentPage = $this->currentPage();

		// Mark the nodes of the tree to return
		if ($filterFunction) $obj->setMarkingFilterFunction($filterFunction);

		$obj->markPartialTree($nodeCountThreshold, $this, $childrenMethod, $numChildrenMethod);

		// Ensure current page is exposed
		if($currentPage) $obj->markToExpose($currentPage);

		// NOTE: SiteTree/CMSMain coupling :-(
		if(class_exists('SiteTree')) {
			SiteTree::prepopulate_permission_cache('CanEditType', $obj->markedNodeIDs(),
				'SiteTree::can_edit_multiple');
		}

		// getChildrenAsUL is a flexible and complex way of traversing the tree
		$controller = $this;
		$recordController = ($this->stat('tree_class') == 'SiteTree') ?  singleton('CMSPageEditController') : $this;
		$titleFn = function(&$child, $numChildrenMethod) use(&$controller, &$recordController, $filter) {
			$link = Controller::join_links($recordController->Link("show"), $child->ID);
			$node = LeftAndMain_TreeNode::create($child, $link, $controller->isCurrentPage($child), $numChildrenMethod, $filter);
			return $node->forTemplate();
		};

		// Limit the amount of nodes shown for performance reasons.
		// Skip the check if we're filtering the tree, since its not clear how many children will
		// match the filter criteria until they're queried (and matched up with previously marked nodes).
		$nodeThresholdLeaf = Config::inst()->get('Hierarchy', 'node_threshold_leaf');
		if($nodeThresholdLeaf && !$filterFunction) {
			$nodeCountCallback = function($parent, $numChildren) use(&$controller, $className, $nodeThresholdLeaf) {
				if($className == 'SiteTree' && $parent->ID && $numChildren > $nodeThresholdLeaf) {
					return sprintf(
						'<ul><li class="readonly"><span class="item">'
							. '%s (<a href="%s" class="cms-panel-link" data-pjax-target="Content">%s</a>)'
							. '</span></li></ul>',
						_t('LeftAndMain.TooManyPages', 'Too many pages'),
						Controller::join_links(
							$controller->LinkWithSearch($controller->Link()), '
							?view=list&ParentID=' . $parent->ID
						),
						_t(
							'LeftAndMain.ShowAsList',
							'show as list',
							'Show large amount of pages in list instead of tree view'
						)
					);
				}
			};
		} else {
			$nodeCountCallback = null;
		}

		// If the amount of pages exceeds the node thresholds set, use the callback
		$html = null;
		if($obj->ParentID && $nodeCountCallback) {
			$html = $nodeCountCallback($obj, $obj->$numChildrenMethod());
		}

		// Otherwise return the actual tree (which might still filter leaf thresholds on children)
		if(!$html) {
			$html = $obj->getChildrenAsUL(
				"",
				$titleFn,
				singleton('CMSPagesController'),
				true,
				$childrenMethod,
				$numChildrenMethod,
				true,
				$nodeCountThreshold,
				$nodeCountCallback
			);
		}

		// Wrap the root if needs be.
		if(!$rootID) {
			$rootLink = $this->Link('show') . '/root';

			// This lets us override the tree title with an extension
			if($this->hasMethod('getCMSTreeTitle') && $customTreeTitle = $this->getCMSTreeTitle()) {
				$treeTitle = $customTreeTitle;
			} elseif(class_exists('SiteConfig')) {
				$siteConfig = SiteConfig::current_site_config();
				$treeTitle =  Convert::raw2xml($siteConfig->Title);
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
			if($id === "") continue; // $id may be a blank string, which is invalid and should be skipped over

			$record = $this->getRecord($id);
			if(!$record) continue; // In case a page is no longer available
			$recordController = ($this->stat('tree_class') == 'SiteTree')
				?  singleton('CMSPageEditController')
				: $this;

			// Find the next & previous nodes, for proper positioning (Sort isn't good enough - it's not a raw offset)
			// TODO: These methods should really be in hierarchy - for a start it assumes Sort exists
			$next = $prev = null;

			$className = $this->stat('tree_class');
			$next = DataObject::get($className)
				->filter('ParentID', $record->ParentID)
				->filter('Sort:GreaterThan', $record->Sort)
				->first();

			if (!$next) {
				$prev = DataObject::get($className)
					->filter('ParentID', $record->ParentID)
					->filter('Sort:LessThan', $record->Sort)
					->reverse()
					->first();
			}

			$link = Controller::join_links($recordController->Link("show"), $record->ID);
			$html = LeftAndMain_TreeNode::create($record, $link, $this->isCurrentPage($record))
				->forTemplate() . '</li>';

			$data[$id] = array(
				'html' => $html,
				'ParentID' => $record->ParentID,
				'NextID' => $next ? $next->ID : null,
				'PrevID' => $prev ? $prev->ID : null
			);
		}
		$this->getResponse()->addHeader('Content-Type', 'text/json');
		return Convert::raw2json($data);
	}

	/**
	 * Save  handler
	 */
	public function save($data, $form) {
		$className = $this->stat('tree_class');

		// Existing or new record?
		$id = $data['ID'];
		if(substr($id,0,3) != 'new') {
			$record = DataObject::get_by_id($className, $id);
			if($record && !$record->canEdit()) return Security::permissionFailure($this);
			if(!$record || !$record->ID) $this->httpError(404, "Bad record ID #" . (int)$id);
		} else {
			if(!singleton($this->stat('tree_class'))->canCreate()) return Security::permissionFailure($this);
			$record = $this->getNewItem($id, false);
		}

		// save form data into record
		$form->saveInto($record, true);
		$record->write();
		$this->extend('onAfterSave', $record);
		$this->setCurrentPageID($record->ID);

		$this->getResponse()->addHeader('X-Status', rawurlencode(_t('LeftAndMain.SAVEDUP', 'Saved.')));
		return $this->getResponseNegotiator()->respond($this->getRequest());
	}

	public function delete($data, $form) {
		$className = $this->stat('tree_class');

		$id = $data['ID'];
		$record = DataObject::get_by_id($className, $id);
		if($record && !$record->canDelete()) return Security::permissionFailure();
		if(!$record || !$record->ID) $this->httpError(404, "Bad record ID #" . (int)$id);

		$record->delete();

		$this->getResponse()->addHeader('X-Status', rawurlencode(_t('LeftAndMain.DELETED', 'Deleted.')));
		return $this->getResponseNegotiator()->respond(
			$this->getRequest(),
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
		if (!SecurityToken::inst()->checkRequest($request)) {
			return $this->httpError(400);
		}
		if (!Permission::check('SITETREE_REORGANISE') && !Permission::check('ADMIN')) {
			$this->getResponse()->setStatusCode(
				403,
				_t('LeftAndMain.CANT_REORGANISE',
					"You do not have permission to rearange the site tree. Your change was not saved.")
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
				$this->getResponse()->setStatusCode(
					403,
					_t('LeftAndMain.CANT_REORGANISE',
						"You do not have permission to alter Top level pages. Your change was not saved.")
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
			$this->getResponse()->setStatusCode(
				500,
				_t('LeftAndMain.PLEASESAVE',
					"Please Save Page: This page could not be updated because it hasn't been saved yet."
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
				$virtualPages = VirtualPage::get()->filter("CopyContentFromID", $node->ID);
				foreach($virtualPages as $virtualPage) {
					$statusUpdates['modified'][$virtualPage->ID] = array(
						'TreeTitle' => $virtualPage->TreeTitle()
					);
				}
			}

			$this->getResponse()->addHeader('X-Status',
				rawurlencode(_t('LeftAndMain.REORGANISATIONSUCCESSFUL', 'Reorganised the site tree successfully.')));
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
					DB::prepared_query(
						"UPDATE \"$className\" SET \"Sort\" = ? WHERE \"ID\" = ?",
						array($counter, $id)
					);
				}
			}

			$this->getResponse()->addHeader('X-Status',
				rawurlencode(_t('LeftAndMain.REORGANISATIONSUCCESSFUL', 'Reorganised the site tree successfully.')));
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
	public function EditForm($request = null) {
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

			$tree_class = $this->stat('tree_class');
			if(
				$tree_class::has_extension('Hierarchy')
				&& !$fields->dataFieldByName('ParentID')
			) {
				$fields->push(new HiddenField('ParentID'));
			}

			// Added in-line to the form, but plucked into different view by frontend scripts.
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

			$form = CMSForm::create(
				$this, "EditForm", $fields, $actions
			)->setHTMLID('Form_EditForm');
			$form->setResponseNegotiator($this->getResponseNegotiator());
			$form->addExtraClass('cms-edit-form');
			$form->loadDataFrom($record);
			$form->setTemplate($this->getTemplatesWithSuffix('_EditForm'));
			$form->setAttribute('data-pjax-fragment', 'CurrentForm');

			// Announce the capability so the frontend can decide whether to allow preview or not.
			if(in_array('CMSPreviewable', class_implements($record))) {
				$form->addExtraClass('cms-previewable');
			}

			// Set this if you want to split up tabs into a separate header row
			// if($form->Fields()->hasTabset()) {
			// 	$form->Fields()->findOrMakeTab('Root')->setTemplate('CMSTabSet');
			// }

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
				if($validator != NULL){
					$form->setValidator($validator);
				}
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
	public function EmptyForm() {
		$form = CMSForm::create(
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
				// 		_t('LeftAndMain_right_ss.WELCOMETO','Welcome to'),
				// 		$this->getApplicationName(),
				// 		_t('CHOOSEPAGE','Please choose an item from the left.')
				// 	)
				// )
			),
			new FieldList()
		)->setHTMLID('Form_EditForm');
		$form->setResponseNegotiator($this->getResponseNegotiator());
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
	public function batchactions() {
		return new CMSBatchActionHandler($this, 'batchactions', $this->stat('tree_class'));
	}

	/**
	 * @return Form
	 */
	public function BatchActionsForm() {
		$actions = $this->batchactions()->batchActionList();
		$actionsMap = array('-1' => _t('LeftAndMain.DropdownBatchActionsDefault', 'Choose an action...')); // Placeholder action
		foreach($actions as $action) {
			$actionsMap[$action->Link] = $action->Title;
		}

		$form = new Form(
			$this,
			'BatchActionsForm',
			new FieldList(
				new HiddenField('csvIDs'),
				DropdownField::create(
					'Action',
					false,
					$actionsMap
				)
					->setAttribute('autocomplete', 'off')
					->setAttribute('data-placeholder', _t('LeftAndMain.DropdownBatchActionsDefault', 'Choose an action...'))
			),
			new FieldList(
				// TODO i18n
				new FormAction('submit', _t('Form.SubmitBtnLabel', "Go"))
			)
		);
		$form->addExtraClass('cms-batch-actions nostyle');
		$form->unsetValidator();

		$this->extend('updateBatchActionsForm', $form);
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
	public function getSilverStripeNavigator() {
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
		if($this->getRequest()->requestVar('ID') && is_numeric($this->getRequest()->requestVar('ID')))	{
			return $this->getRequest()->requestVar('ID');
		} elseif ($this->getRequest()->requestVar('CMSMainCurrentPageID') && is_numeric($this->getRequest()->requestVar('CMSMainCurrentPageID'))) {
			// see GridFieldDetailForm::ItemEditForm
			return $this->getRequest()->requestVar('CMSMainCurrentPageID');
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
		$id = (int)$id;
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
	 * Uses the number in <mymodule>/silverstripe_version
	 * (automatically replaced by build scripts).
	 * If silverstripe_version is empty,
	 * then attempts to get it from composer.lock
	 *
	 * @return string
	 */
	public function CMSVersion() {
		$versions = array();
		$modules = array(
			'silverstripe/framework' => array(
				'title' => 'Framework',
				'versionFile' => FRAMEWORK_PATH . '/silverstripe_version',
			)
		);
		if(defined('CMS_PATH')) {
			$modules['silverstripe/cms'] = array(
				'title' => 'CMS',
				'versionFile' => CMS_PATH . '/silverstripe_version',
			);
		}

		// Tries to obtain version number from composer.lock if it exists
		$composerLockPath = BASE_PATH . '/composer.lock';
		if (file_exists($composerLockPath)) {
			$cache = SS_Cache::factory('LeftAndMain_CMSVersion');
			$cacheKey = filemtime($composerLockPath);
			$versions = $cache->load($cacheKey);
			if($versions) {
				$versions = json_decode($versions, true);
			} else {
				$versions = array();
			}
			if(!$versions && $jsonData = file_get_contents($composerLockPath)) {
				$lockData = json_decode($jsonData);
				if($lockData && isset($lockData->packages)) {
					foreach ($lockData->packages as $package) {
						if(
							array_key_exists($package->name, $modules)
							&& isset($package->version)
						) {
							$versions[$package->name] = $package->version;
						}
					}
					$cache->save(json_encode($versions), $cacheKey);
				}
			}
		}

		// Fall back to static version file
		foreach($modules as $moduleName => $moduleSpec) {
			if(!isset($versions[$moduleName])) {
				if($staticVersion = file_get_contents($moduleSpec['versionFile'])) {
					$versions[$moduleName] = $staticVersion;
				} else {
					$versions[$moduleName] = _t('LeftAndMain.VersionUnknown', 'Unknown');
				}
			}
		}

		$out = array();
		foreach($modules as $moduleName => $moduleSpec) {
			$out[] = $modules[$moduleName]['title'] . ': ' . $versions[$moduleName];
		}
		return implode(', ', $out);
	}

	/**
	 * @return array
	 */
	public function SwitchView() {
		if($page = $this->currentPage()) {
			$nav = SilverStripeNavigator::get_for_record($page);
			return $nav['items'];
		}
	}

	/**
	 * @return SiteConfig
	 */
	public function SiteConfig() {
		return (class_exists('SiteConfig')) ? SiteConfig::current_site_config() : null;
	}

	/**
	 * The href for the anchor on the Silverstripe logo.
	 * Set by calling LeftAndMain::set_application_link()
	 *
	 * @config
	 * @var String
	 */
	private static $application_link = '//www.silverstripe.org/';

	/**
	 * Sets the href for the anchor on the Silverstripe logo in the menu
	 *
	 * @deprecated since version 4.0
	 *
	 * @param String $link
	 */
	public static function set_application_link($link) {
		Deprecation::notice('4.0', 'Use the "LeftAndMain.application_link" config setting instead');
		Config::inst()->update('LeftAndMain', 'application_link', $link);
	}

	/**
	 * @return String
	 */
	public function ApplicationLink() {
		return $this->stat('application_link');
	}

	/**
	 * The application name. Customisable by calling
	 * LeftAndMain::setApplicationName() - the first parameter.
	 *
	 * @config
	 * @var String
	 */
	private static $application_name = 'SilverStripe';

	/**
	 * @param String $name
	 * @deprecated since version 4.0
	 */
	public static function setApplicationName($name) {
		Deprecation::notice('4.0', 'Use the "LeftAndMain.application_name" config setting instead');
		Config::inst()->update('LeftAndMain', 'application_name', $name);
	}

	/**
	 * Get the application name.
	 *
	 * @return string
	 */
	public function getApplicationName() {
		return $this->stat('application_name');
	}

	/**
	 * @return string
	 */
	public function Title() {
		$app = $this->getApplicationName();

		return ($section = $this->SectionTitle()) ? sprintf('%s - %s', $app, $section) : $app;
	}

	/**
	 * Return the title of the current section. Either this is pulled from
	 * the current panel's menu_title or from the first active menu
	 *
	 * @return string
	 */
	public function SectionTitle() {
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
	public function MceRoot() {
		return MCE_ROOT;
	}

	/**
	 * Same as {@link ViewableData->CSSClasses()}, but with a changed name
	 * to avoid problems when using {@link ViewableData->customise()}
	 * (which always returns "ArrayData" from the $original object).
	 *
	 * @return String
	 */
	public function BaseCSSClasses() {
		return $this->CSSClasses('Controller');
	}

	/**
	 * @return String
	 */
	public function Locale() {
		return DBField::create_field('DBLocale', i18n::get_locale());
	}

	public function providePermissions() {
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
	 *
	 * @deprecated since version 4.0
	 */
	public static function require_javascript($file) {
		Deprecation::notice('4.0', 'Use "LeftAndMain.extra_requirements_javascript" config setting instead');
		Config::inst()->update('LeftAndMain', 'extra_requirements_javascript', array($file => array()));
	}

	/**
	 * Register the given stylesheet file as required.
	 * @deprecated since version 4.0
	 *
	 * @param $file String Filenames should be relative to the base, eg, THIRDPARTY_DIR . '/tree/tree.css'
	 * @param $media String Comma-separated list of media-types (e.g. "screen,projector")
	 * @see http://www.w3.org/TR/REC-CSS2/media.html
	 */
	public static function require_css($file, $media = null) {
		Deprecation::notice('4.0', 'Use "LeftAndMain.extra_requirements_css" config setting instead');
		Config::inst()->update('LeftAndMain', 'extra_requirements_css', array($file => array('media' => $media)));
	}

	/**
	 * Register the given "themeable stylesheet" as required.
	 * Themeable stylesheets have globally unique names, just like templates and PHP files.
	 * Because of this, they can be replaced by similarly named CSS files in the theme directory.
	 *
	 * @deprecated since version 4.0
	 *
	 * @param $name String The identifier of the file.  For example, css/MyFile.css would have the identifier "MyFile"
	 * @param $media String Comma-separated list of media-types (e.g. "screen,projector")
	 */
	public static function require_themed_css($name, $media = null) {
		Deprecation::notice('4.0', 'Use "LeftAndMain.extra_requirements_themedCss" config setting instead');
		Config::inst()->update('LeftAndMain', 'extra_requirements_themedCss', array($name => array('media' => $media)));
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
     * @var array
     */
	public $ids = array();

    /**
     * @var array
     */
	public $expanded = array();

	/**
	 * @param array $params Request params (unsanitized)
	 */
	public function __construct($params = null) {
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
			$parentsClause = DB::placeholders($parents);
			$res = DB::prepared_query(
				"SELECT \"ParentID\", \"ID\" FROM \"SiteTree\" WHERE \"ID\" in ($parentsClause)",
				array_keys($parents)
			);
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

		if(isset($params['ID'])) unset($params['ID']);
		if($treeClass = static::config()->tree_class) foreach($params as $name => $val) {
			// Partial string match against a variety of fields
			if(!empty($val) && singleton($treeClass)->hasDatabaseField($name)) {
				$predicate = sprintf('"%s" LIKE ?', $name);
				$where[$predicate] = "%$val%";
			}
		}

		return new SQLQuery(
			array("ParentID", "ID"),
			'SiteTree',
			$where
		);
	}

	public function mark($node) {
		$id = $node->ID;
		if(array_key_exists((int) $id, $this->expanded)) $node->markOpened();
		return array_key_exists((int) $id, $this->ids) ? $this->ids[$id] : false;
	}
}

/**
 * Allow overriding finished state for faux redirects.
 *
 * @package framework
 * @subpackage admin
 */
class LeftAndMain_HTTPResponse extends SS_HTTPResponse {

	protected $isFinished = false;

	public function isFinished() {
		return (parent::isFinished() || $this->isFinished);
	}

	public function setIsFinished($bool) {
		$this->isFinished = $bool;
	}

}

/**
 * Wrapper around objects being displayed in a tree.
 * Caution: Volatile API.
 *
 * @todo Implement recursive tree node rendering.
 *
 * @package framework
 * @subpackage admin
 */
class LeftAndMain_TreeNode extends ViewableData {

	/**
	 * Object represented by this node
	 *
	 * @var Object
	 */
	protected $obj;

	/**
	 * Edit link to the current record in the CMS
	 *
	 * @var string
	 */
	protected $link;

	/**
	 * True if this is the currently selected node in the tree
	 *
	 * @var bool
	 */
	protected $isCurrent;

	/**
	 * Name of method to count the number of children
	 *
	 * @var string
	 */
	protected $numChildrenMethod;


	/**
	 *
	 * @var LeftAndMain_SearchFilter
	 */
	protected $filter;

	/**
	 * @param Object $obj
	 * @param string $link
	 * @param bool $isCurrent
	 * @param string $numChildrenMethod
	 * @param LeftAndMain_SearchFilter $filter
	 */
	public function __construct($obj, $link = null, $isCurrent = false,
		$numChildrenMethod = 'numChildren', $filter = null
	) {
		parent::__construct();
		$this->obj = $obj;
		$this->link = $link;
		$this->isCurrent = $isCurrent;
		$this->numChildrenMethod = $numChildrenMethod;
		$this->filter = $filter;
	}

	/**
	 * Returns template, for further processing by {@link Hierarchy->getChildrenAsUL()}.
	 * Does not include closing tag to allow this method to inject its own children.
	 *
	 * @todo Remove hardcoded assumptions around returning an <li>, by implementing recursive tree node rendering
	 *
	 * @return string
	 */
	public function forTemplate() {
		$obj = $this->obj;

		return (string)SSViewer::execute_template('LeftAndMain_TreeNode', $obj, array(
			'Classes' => $this->getClasses(),
			'Link' => $this->getLink(),
			'Title' => sprintf(
				'(%s: %s) %s',
				trim(_t('LeftAndMain.PAGETYPE','Page type'), " :"),
				$obj->i18n_singular_name(),
				$obj->Title
			),
		));
	}

	/**
	 * Determine the CSS classes to apply to this node
	 *
	 * @return string
	 */
	public function getClasses() {
		// Get classes from object
		$classes = $this->obj->CMSTreeClasses($this->numChildrenMethod);
		if($this->isCurrent) {
			$classes .= ' current';
		}
		// Get status flag classes
		$flags = $this->obj->hasMethod('getStatusFlags')
			? $this->obj->getStatusFlags()
			: false;
		if ($flags) {
			$statuses = array_keys($flags);
			foreach ($statuses as $s) {
				$classes .= ' status-' . $s;
			}
		}
		// Get additional filter classes
		if($this->filter && ($filterClasses = $this->filter->getPageClasses($this->obj))) {
			if(is_array($filterClasses)) {
				$filterClasses = implode(' ' . $filterClasses);
			}
			$classes .= ' ' . $filterClasses;
		}
		return $classes ?: '';
	}

	public function getObj() {
		return $this->obj;
	}

	public function setObj($obj) {
		$this->obj = $obj;
		return $this;
	}

	public function getLink() {
		return $this->link;
	}

	public function setLink($link) {
		$this->link = $link;
		return $this;
	}

	public function getIsCurrent() {
		return $this->isCurrent;
	}

	public function setIsCurrent($bool) {
		$this->isCurrent = $bool;
		return $this;
	}

}

/**
 * Abstract interface for a class which may be used to filter the results displayed
 * in a nested tree
 */
interface LeftAndMain_SearchFilter {

	/**
	 * Method on {@link Hierarchy} objects which is used to traverse into children relationships.
	 *
	 * @return string
	 */
	public function getChildrenMethod();

	/**
	 * Method on {@link Hierarchy} objects which is used find the number of children for a parent page
	 *
	 * @return string
	 */
	public function getNumChildrenMethod();


	/**
	 * Returns TRUE if the given page should be included in the tree.
	 * Caution: Does NOT check view permissions on the page.
	 *
	 * @param DataObject $page
	 * @return bool
	 */
	public function isPageIncluded($page);

	/**
	 * Given a page, determine any additional CSS classes to apply to the tree node
	 *
	 * @param DataObject $page
	 * @return array|string
	 */
	public function getPageClasses($page);
}
