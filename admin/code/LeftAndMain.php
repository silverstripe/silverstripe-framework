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
class LeftAndMain extends Controller {
	
	/**
	 * The 'base' url for CMS administration areas.
	 * Note that if this is changed, many javascript
	 * behaviours need to be updated with the correct url
	 *
	 * @var string $url_base
	 */
	static $url_base = "admin";
	
	static $url_segment;
	
	static $url_rule = '/$Action/$ID/$OtherID';
	
	static $menu_title;
	
	static $menu_priority = 0;
	
	static $url_priority = 50;

	/**
	 * @var string A subclass of {@link DataObject}. 
	 * Determines what is managed in this interface,
	 * through {@link getEditForm()} and other logic.
	 */
	static $tree_class = null;
	
	/**
	* The url used for the link in the Help tab in the backend
	* Value can be overwritten if required in _config.php
	*/
	static $help_link = 'http://userhelp.silverstripe.org';

	static $allowed_actions = array(
		'index',
		'savetreenode',
		'getitem',
		'getsubtree',
		'myprofile',
		'printable',
		'show',
		'Member_ProfileForm',
		'EditorToolbar',
		'EditForm',
		'RootForm',
		'AddForm',
		'batchactions',
		'BatchActionsForm',
		'Member_ProfileForm',
	);
	
	/**
	 * Register additional requirements through the {@link Requirements class}.
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
	 * @param Member $member
	 * @return boolean
	 */
	function canView($member = null) {
		if(!$member && $member !== FALSE) {
			$member = Member::currentUser();
		}
		
		// cms menus only for logged-in members
		if(!$member) return false;
		
		// alternative decorated checks
		if($this->hasMethod('alternateAccessCheck')) {
			$alternateAllowed = $this->alternateAccessCheck();
			if($alternateAllowed === FALSE) return false;
		}
			
		// Default security check for LeftAndMain sub-class permissions
		if(!Permission::checkMember($member, "CMS_ACCESS_$this->class") && 
		   !Permission::checkMember($member, "CMS_ACCESS_LeftAndMain")) {
			return false;
		}
		
		return true;
	}
	
	/**
	 * @uses LeftAndMainDecorator->init()
	 * @uses LeftAndMainDecorator->accessedCMS()
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
			_t('LeftAndMain.HELP', 'Help', PR_HIGH, 'Menu title'), 
			self::$help_link
		);

		// Allow customisation of the access check by a decorator
		// Also all the canView() check to execute Director::redirect()
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
					return Director::redirect($candidate->Link);
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
		if(Director::redirected_to()) return;

		// Audit logging hook
		if(empty($_REQUEST['executeForm']) && !$this->isAjax()) $this->extend('accessedCMS');

		// Set the members html editor config
		HtmlEditorConfig::set_active(Member::currentUser()->getHtmlEditorConfigForCMS());
		
		
		// Set default values in the config if missing.  These things can't be defined in the config
		// file because insufficient information exists when that is being processed
		$htmlEditorConfig = HtmlEditorConfig::get_active();
		$htmlEditorConfig->setOption('language', i18n::get_tinymce_lang());
		if(!$htmlEditorConfig->getOption('content_css')) {
			$cssFiles = 'sapphire/admin/css/editor.css';
			
			// Use theme from the site config
			if(class_exists('SiteConfig') && ($config = SiteConfig::current_site_config()) && $config->Theme) {
				$theme = $config->Theme;
			} elseif(SSViewer::current_theme()) {
				$theme = SSViewer::current_theme();
			} else {
				$theme = false;
			}
			
			if($theme) $cssFiles .= ',' . THEMES_DIR . "/{$theme}/css/editor.css";
			else if(project()) $cssFiles .= ',' . project() . '/css/editor.css';

			$htmlEditorConfig->setOption('content_css', $cssFiles);
		}
		

		Requirements::css(SAPPHIRE_ADMIN_DIR . '/css/typography.css');
		Requirements::css(SAPPHIRE_ADMIN_DIR . '/css/layout.css');
		Requirements::css(SAPPHIRE_ADMIN_DIR . '/css/cms_left.css');
		Requirements::css(SAPPHIRE_ADMIN_DIR . '/css/cms_right.css');
		Requirements::css(SAPPHIRE_DIR . '/css/Form.css');
		
		Requirements::javascript(SAPPHIRE_DIR . '/javascript/prototypefix/intro.js');
		Requirements::javascript(SAPPHIRE_DIR . '/thirdparty/prototype/prototype.js');
		Requirements::javascript(SAPPHIRE_DIR . '/javascript/prototypefix/outro.js');
		
		Requirements::javascript(SAPPHIRE_DIR . '/thirdparty/jquery/jquery.js');
		Requirements::javascript(SAPPHIRE_DIR . '/javascript/jquery_improvements.js');

		Requirements::javascript(SAPPHIRE_DIR . '/thirdparty/jquery-ui/jquery-ui.js');  //import all of jquery ui

		Requirements::javascript(SAPPHIRE_ADMIN_DIR . '/thirdparty/jquery-layout/jquery.layout.js');
		Requirements::javascript(SAPPHIRE_ADMIN_DIR . '/thirdparty/jquery-layout/jquery.layout.state.js');
		Requirements::javascript(SAPPHIRE_DIR . '/thirdparty/json-js/json2.js');
		Requirements::javascript(SAPPHIRE_DIR . '/thirdparty/jquery-metadata/jquery.metadata.js');
		Requirements::javascript(SAPPHIRE_ADMIN_DIR . '/javascript/jquery-fitheighttoparent/jquery.fitheighttoparent.js');
		
		Requirements::javascript(SAPPHIRE_ADMIN_DIR . '/javascript/ssui.core.js');
		// @todo Load separately so the CSS files can be inlined
		Requirements::css(SAPPHIRE_DIR . '/thirdparty/jquery-ui-themes/smoothness/jquery.ui.all.css');
		
		// entwine
		Requirements::javascript(SAPPHIRE_DIR . '/thirdparty/jquery-entwine/dist/jquery.entwine-dist.js');
		
		// Required for TreeTools panel above tree
		Requirements::javascript(SAPPHIRE_DIR . '/javascript/TabSet.js');
		
		Requirements::javascript(SAPPHIRE_DIR . '/thirdparty/behaviour/behaviour.js');
		Requirements::javascript(SAPPHIRE_DIR . '/thirdparty/jquery-cookie/jquery.cookie.js');
		Requirements::javascript(SAPPHIRE_ADMIN_DIR . '/thirdparty/jquery-notice/jquery.notice.js');
		Requirements::javascript(SAPPHIRE_DIR . '/javascript/jquery-ondemand/jquery.ondemand.js');
		Requirements::javascript(SAPPHIRE_ADMIN_DIR . '/javascript/jquery-changetracker/lib/jquery.changetracker.js');
		Requirements::add_i18n_javascript(SAPPHIRE_DIR . '/javascript/lang');
		Requirements::add_i18n_javascript(SAPPHIRE_ADMIN_DIR . '/javascript/lang');
		
		Requirements::javascript(SAPPHIRE_DIR . '/thirdparty/scriptaculous/effects.js');
		Requirements::javascript(SAPPHIRE_DIR . '/thirdparty/scriptaculous/dragdrop.js');
		Requirements::javascript(SAPPHIRE_DIR . '/thirdparty/scriptaculous/controls.js');
		
		Requirements::javascript(THIRDPARTY_DIR . '/tree/tree.js');
		Requirements::css(THIRDPARTY_DIR . '/tree/tree.css');
		Requirements::javascript(THIRDPARTY_DIR . '/jstree/jquery.jstree.js');
		Requirements::css(THIRDPARTY_DIR . '/jstree/themes/apple/style.css');
		
		Requirements::javascript(SAPPHIRE_ADMIN_DIR . '/javascript/LeftAndMain.js');
		Requirements::javascript(SAPPHIRE_ADMIN_DIR . '/javascript/LeftAndMain.Tree.js');
		Requirements::javascript(SAPPHIRE_ADMIN_DIR . '/javascript/LeftAndMain.EditForm.js');
		Requirements::javascript(SAPPHIRE_ADMIN_DIR . '/javascript/LeftAndMain.AddForm.js');
		Requirements::javascript(SAPPHIRE_ADMIN_DIR . '/javascript/LeftAndMain.BatchActions.js');

		Requirements::themedCSS('typography');

		foreach (self::$extra_requirements['javascript'] as $file) {
			Requirements::javascript($file[0]);
		}
		
		foreach (self::$extra_requirements['css'] as $file) {
			Requirements::css($file[0], $file[1]);
		}
		
		foreach (self::$extra_requirements['themedcss'] as $file) {
			Requirements::themedCSS($file[0], $file[1]);
		}
		
		Requirements::css(SAPPHIRE_ADMIN_DIR . '/css/unjquery.css');
		
		// Javascript combined files
		Requirements::combine_files(
			'base.js',
			array(
				'sapphire/thirdparty/prototype/prototype.js',
				'sapphire/thirdparty/behaviour/behaviour.js',
				'sapphire/thirdparty/jquery/jquery.js',
				'sapphire/thirdparty/jquery-livequery/jquery.livequery.js',
				'sapphire/javascript/jquery-ondemand/jquery.ondemand.js',
				'sapphire/thirdparty/jquery-ui/jquery-ui.js',
				'sapphire/javascript/i18n.js',
			)
		);

		Requirements::combine_files(
			'leftandmain.js',
			array(
				'sapphire/thirdparty/scriptaculous/effects.js',
				'sapphire/thirdparty/scriptaculous/dragdrop.js',
				'sapphire/thirdparty/scriptaculous/controls.js',
				'sapphire/admin/javascript/LeftAndMain.js',
				'sapphire/javascript/tree/tree.js',
				'sapphire/javascript/TreeDropdownField.js',
			)
		);

		$dummy = null;
		$this->extend('init', $dummy);

		// The user's theme shouldn't affect the CMS, if, for example, they have replaced
		// TableListField.ss or Form.ss.
		SSViewer::set_theme(null);
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
		if(!$this->stat('url_segment', true))
			self::$url_segment = $this->class;
		return Controller::join_links(
			$this->stat('url_base', true),
			$this->stat('url_segment', true),
			'/', // trailing slash needed if $action is null!
			"$action"
		);
	}
	
	/**
	 * Returns the menu title for the given LeftAndMain subclass.
	 * Implemented static so that we can get this value without instantiating an object.
	 * Menu title is *not* internationalised.
	 */
	static function menu_title_for_class($class) {
		$title = eval("return $class::\$menu_title;");
		if(!$title) $title = preg_replace('/Admin$/', '', $class);
		return $title;
	}

	public function show($request) {
		// TODO Necessary for TableListField URLs to work properly
		if($request->param('ID')) $this->setCurrentPageID($request->param('ID'));
		
		if($this->isAjax()) {
			SSViewer::setOption('rewriteHashlinks', false);
			$form = $this->getEditForm($request->param('ID'));
			$content = $form->formHtmlContent();
		} else {
			// Rendering is handled by template, which will call EditForm() eventually
			$content = $this->renderWith($this->getViewer('show'));
		}
		
		if($this->ShowSwitchView()) {
			$content .= '<div id="AjaxSwitchView">' . $this->SwitchView() . '</div>';
		}
		
		return $content;
	}

	/**
	 * @deprecated 2.4 Please use show()
	 */
	public function getitem($request) {
		$form = $this->getEditForm($request->getVar('ID'));
		return $form->formHtmlContent();
	}

	//------------------------------------------------------------------------------------------//
	// Main UI components

	/**
	 * Returns the main menu of the CMS.  This is also used by init() 
	 * to work out which sections the user has access to.
	 * 
	 * @return DataObjectSet
	 */
	public function MainMenu() {
		// Don't accidentally return a menu if you're not logged in - it's used to determine access.
		if(!Member::currentUser()) return new DataObjectSet();

		// Encode into DO set
		$menu = new DataObjectSet();
		$menuItems = CMSMenu::get_viewable_menu_items();
		if($menuItems) foreach($menuItems as $code => $menuItem) {
			// alternate permission checks (in addition to LeftAndMain->canView())
			if(
				isset($menuItem->controller) 
				&& $this->hasMethod('alternateMenuDisplayCheck')
				&& !$this->alternateMenuDisplayCheck($menuItem->controller)
			) {
				continue;
			}

			$linkingmode = "";
			
			if(strpos($this->Link(), $menuItem->url) !== false) {
				if($this->Link() == $menuItem->url) {
					$linkingmode = "current";
				
				// default menu is the one with a blank {@link url_segment}
				} else if(singleton($menuItem->controller)->stat('url_segment') == '') {
					if($this->Link() == $this->stat('url_base').'/') $linkingmode = "current";

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
			
			$menu->push(new ArrayData(array(
				"MenuItem" => $menuItem,
				"Title" => Convert::raw2xml($title),
				"Code" => $code,
				"Link" => $menuItem->url,
				"LinkingMode" => $linkingmode
			)));
		}
		
		// if no current item is found, assume that first item is shown
		//if(!isset($foundCurrent)) 
		return $menu;
	}

	public function CMSTopMenu() {
		return $this->renderWith(array('CMSTopMenu_alternative','CMSTopMenu'));
	}

	/**
	 * Return a list of appropriate templates for this class, with the given suffix
	 */
	protected function getTemplatesWithSuffix($suffix) {
		$classes = array_reverse(ClassInfo::ancestry($this->class));
		foreach($classes as $class) {
			$templates[] = $class . $suffix;
			if($class == 'LeftAndMain') break;
		}
		return $templates;
	}

	public function Left() {
		return $this->renderWith($this->getTemplatesWithSuffix('_left'));
	}

	public function Right() {
		return $this->renderWith($this->getTemplatesWithSuffix('_right'));
	}

	public function getRecord($id) {
		$className = $this->stat('tree_class');
		if($id instanceof $className) {
			return $id;
		} else if(is_numeric($id)) {
			return DataObject::get_by_id($className, $id);
		} else {
			return false;
		}
	}
	
	/**
	 * @return String HTML
	 */
	public function SiteTreeAsUL() {
		return $this->getSiteTreeFor($this->stat('tree_class'));
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
		// Default childrenMethod and numChildrenMethod
		if (!$childrenMethod) $childrenMethod = 'AllChildrenIncludingDeleted';
		if (!$numChildrenMethod) $numChildrenMethod = 'numChildren';
		
		// Get the tree root
		$obj = $rootID ? $this->getRecord($rootID) : singleton($className);
		
		// Mark the nodes of the tree to return
		if ($filterFunction) $obj->setMarkingFilterFunction($filterFunction);

		$obj->markPartialTree($minNodeCount, $this, $childrenMethod, $numChildrenMethod);
		
		// Ensure current page is exposed
		if($p = $this->currentPage()) $obj->markToExpose($p);
		
		// NOTE: SiteTree/CMSMain coupling :-(
		if(class_exists('SiteTree')) {
			SiteTree::prepopuplate_permission_cache('CanEditType', $obj->markedNodeIDs(), 'SiteTree::can_edit_multiple');
		}

		// getChildrenAsUL is a flexible and complex way of traversing the tree
		$titleEval = '
			"<li id=\"record-$child->ID\" data-id=\"$child->ID\" class=\"" . $child->CMSTreeClasses($extraArg) . "\">" .
			"<ins class=\"jstree-icon\">&nbsp;</ins>" .
			"<a href=\"" . Controller::join_links(substr($extraArg->Link(),0,-1), "show", $child->ID) . "\" title=\"' 
			. _t('LeftAndMain.PAGETYPE','Page type: ') 
			. '".$child->class."\" ><ins class=\"jstree-icon\">&nbsp;</ins>" . ($child->TreeTitle) . 
			"</a>"
		';

		$html = $obj->getChildrenAsUL(
			"", 
			$titleEval,
			$this, 
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
			
			$html = "<ul id=\"sitetree\" class=\"tree unformatted\"><li id=\"record-0\" data-id=\"0\"class=\"Root nodelete\"><a href=\"$rootLink\"><strong>$treeTitle</strong></a>"
				. $html . "</li></ul>";
		}

		return $html;
	}

	/**
	 * Get a subtree underneath the request param 'ID'.
	 * If ID = 0, then get the whole tree.
	 */
	public function getsubtree($request) {
		if($filterClass = $request->requestVar('FilterClass')) {
			if(!is_subclass_of($filterClass, 'CMSSiteTreeFilter')) {
				throw new Exception(sprintf('Invalid filter class passed: %s', $filterClass));
			}

			$filter = new $filterClass($request->requestVars());
		} else {
			$filter = null;
		}
		
		$html = $this->getSiteTreeFor(
			$this->stat('tree_class'), 
			$request->getVar('ID'), 
			($filter) ? $filter->getChildrenMethod() : null, 
			null,
			($filter) ? array($filter, 'isPageIncluded') : null, 
			$request->getVar('minNodeCount')
		);

		// Trim off the outer tag
		$html = preg_replace('/^[\s\t\r\n]*<ul[^>]*>/','', $html);
		$html = preg_replace('/<\/ul[^>]*>[\s\t\r\n]*$/','', $html);
		
		return $html;
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
		} else {
			if(!singleton($this->stat('tree_class'))->canCreate()) return Security::permissionFailure($this);
			$record = $this->getNewItem($SQL_id, false);
		}
		
		// save form data into record
		$form->saveInto($record, true);
		$record->write();
		$this->extend('onAfterSave', $record);

		$this->response->addHeader('X-Status', _t('LeftAndMain.SAVEDUP'));
		
		// write process might've changed the record, so we reload before returning
		$form = $this->getEditForm($record->ID);
		
		return $form->formHtmlContent();
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

			$this->response->addHeader('X-Status', _t('LeftAndMain.SAVED','saved'));
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
			
			$this->response->addHeader('X-Status', _t('LeftAndMain.SAVED','saved'));
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

	public function getEditForm($id = null) {
		if(!$id) $id = $this->currentPageID();
		
		if(is_object($id)) {
			$record = $id;
		} else {
			$record = ($id && $id != "root") ? $this->getRecord($id) : null;
			if($record && !$record->canView()) return Security::permissionFailure($this);
		}

		if($record) {
			$fields = $record->getCMSFields();
			if ($fields == null) {
				user_error(
					"getCMSFields() returned null  - it should return a FieldSet object. 
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
			
			if($record->hasMethod('getAllCMSActions')) {
				$actions = $record->getAllCMSActions();
			} else {
				$actions = $record->getCMSActions();
				// add default actions if none are defined
				if(!$actions || !$actions->Count()) {
					if($record->canEdit()) {
						$actions->push(new FormAction('save',_t('CMSMain.SAVE','Save')));
					}
				}
			}
			
			$form = new Form($this, "EditForm", $fields, $actions);
			$form->loadDataFrom($record);
			
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
				$validator->setJavascriptValidationHandler('prototype');
				$form->setValidator($validator);
			} else {
				$form->unsetValidator();
			}
		
			if(!$record->canEdit()) {
				$readonlyFields = $form->Fields()->makeReadonly();
				$form->setFields($readonlyFields);
			}
		} else {
			$form = $this->RootForm();
		}
		
		return $form;
	}	
	
	function RootForm() {
		return $this->EmptyForm();
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
			new FieldSet(
				new HeaderField(
					'WelcomeHeader',
					$this->getApplicationName()
				),
				new LiteralField(
					'WelcomeText',
					sprintf('<p id="WelcomeMessage">%s %s. %s</p>',
						_t('LeftAndMain_right.ss.WELCOMETO','Welcome to'),
						$this->getApplicationName(),
						_t('CHOOSEPAGE','Please choose an item from the left.')
					)
				)
			), 
			new FieldSet()
		);
		$form->unsetValidator();
		
		return $form;
	}
	
	/**
	 * @return Form
	 */
	function AddForm() {
		$class = $this->stat('tree_class');
		
		$typeMap = array($class => singleton($class)->i18n_singular_name());
		$typeField = new DropdownField('Type', false, $typeMap, $class);
		$form = new Form(
			$this,
			'AddForm',
			new FieldSet(
				new HiddenField('ParentID'),
				$typeField->performReadonlyTransformation()
			),
			new FieldSet(
				new FormAction('doAdd', _t('AssetAdmin_left.ss.GO','Go'))
			)
		);
		$form->addExtraClass('actionparams');
		
		return $form;
	}
	
	/**
	 * Add a new group and return its details suitable for ajax.
	 */
	public function doAdd($data, $form) {
		$class = $this->stat('tree_class');
		
		// check create permissions
		if(!singleton($class)->canCreate()) return Security::permissionFailure($this);
		
		// check addchildren permissions
		if(
			singleton($class)->hasDatabaseField('Hierarchy') 
			&& isset($data['ParentID'])
			&& is_numeric($data['ParentID'])
		) {
			$parentRecord = DataObject::get_by_id($class, $data['ParentID']);
			if(
				$parentRecord->hasMethod('canAddChildren') 
				&& !$parentRecord->canAddChildren()
			) return Security::permissionFailure($this);
		}
		
		$record = Object::create($class);
		$form->saveInto($record);
		$record->write();

		// Used in TinyMCE inline folder creation
		if(isset($data['returnID'])) {
			return $record->ID;
		} else if($this->isAjax()) {
			$form = $this->getEditForm($record->ID);
			return $form->formHtmlContent();
		} else {
			return $this->redirect(Controller::join_links($this->Link('show'), $record->ID));
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
		$actionsMap = array();
		foreach($actions as $action) $actionsMap[$action->Link] = $action->Title;
		
		$form = new Form(
			$this,
			'BatchActionsForm',
			new FieldSet(
				new LiteralField(
					'Intro',
					sprintf('<p><small>%s</small></p>',
						_t(
							'CMSMain_left.ss.SELECTPAGESACTIONS',
							'Select the pages that you want to change &amp; then click an action:'
						)
					)
				),
				new HiddenField('csvIDs'),
				new DropdownField(
					'Action',
					false,
					$actionsMap
				)
			),
			new FieldSet(
				// TODO i18n
				new FormAction('submit', "Go")
			)
		);
		$form->addExtraClass('actionparams');
		$form->unsetValidator();
		
		return $form;
	}
	
	public function myprofile() {
		$form = $this->Member_ProfileForm();
		return $this->customise(array(
			'Form' => $form
		))->renderWith('BlankPage');
	}
	
	public function Member_ProfileForm() {
		return new Member_ProfileForm($this, 'Member_ProfileForm', Member::currentUser());
	}

	public function printable() {
		$form = $this->getEditForm($this->currentPageID());
		if(!$form) return false;
		
		$form->transform(new PrintableTransformation());
		$form->setActions(null);

		Requirements::clear();
		Requirements::css(SAPPHIRE_ADMIN_DIR . '/css/LeftAndMain_printable.css');
		return array(
			"PrintForm" => $form
		);
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
		if($this->request->requestVar('ID'))	{
			return $this->request->requestVar('ID');
		} elseif ($this->request->param('ID') && is_numeric($this->request->param('ID'))) {
			return $this->request->param('ID');
		} elseif(Session::get("{$this->class}.currentPage")) {
			return Session::get("{$this->class}.currentPage");
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
		Session::set("{$this->class}.currentPage", $id);
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
	 * Get the staus of a certain page and version.
	 *
	 * This function is used for concurrent editing, and providing alerts
	 * when multiple users are editing a single page. It echoes a json
	 * encoded string to the UA.
	 */

	/**
	 * Return the version number of this application.
	 * Uses the subversion path information in <mymodule>/silverstripe_version
	 * (automacially replaced $URL$ placeholder).
	 * 
	 * @return string
	 */
	public function CMSVersion() {
		$sapphireVersionFile = file_get_contents(BASE_PATH . '/sapphire/silverstripe_version');		
		$sapphireVersion = $this->versionFromVersionFile($sapphireVersionFile);

		return "sapphire: $sapphireVersion";
	}
	
	/**
	 * Return the version from the content of a silverstripe_version file
	 */
	public function versionFromVersionFile($fileContent) {
		if(preg_match('/\/trunk\/silverstripe_version/', $fileContent)) {
			return "trunk";
		} else {
			preg_match("/\/(?:branches|tags\/rc|tags\/beta|tags\/alpha|tags)\/([A-Za-z0-9._-]+)\/silverstripe_version/", $fileContent, $matches);
			return ($matches) ? $matches[1] : null;
		}
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
	 * The application name. Customisable by calling
	 * LeftAndMain::setApplicationName() - the first parameter.
	 * 
	 * @var String
	 */
	static $application_name = 'SilverStripe CMS';
	
	/**
	 * The application logo text. Customisable by calling
	 * LeftAndMain::setApplicationName() - the second parameter.
	 *
	 * @var String
	 */
	static $application_logo_text = 'SilverStripe';

	/**
	 * Set the application name, and the logo text.
	 *
	 * @param String $name The application name
	 * @param String $logoText The logo text
	 */
	static $application_link = "http://www.silverstripe.org/";
	
	/**
	 * @param String $name
	 * @param String $logoText
	 * @param String $link (Optional)
	 */
	static function setApplicationName($name, $logoText = null, $link = null) {
		self::$application_name = $name;
		self::$application_logo_text = $logoText ? $logoText : $name;
		if($link) self::$application_link = $link;
	}

	/**
	 * Get the application name.
	 * @return String
	 */
	function getApplicationName() {
		return self::$application_name;
	}
	
	/**
	 * Get the application logo text.
	 * @return String
	 */
	function getApplicationLogoText() {
		return self::$application_logo_text;
	}
	
	/**
	 * @return String
	 */
	function ApplicationLink() {
		return self::$application_link;
	}

	/**
	 * Return the title of the current section, as shown on the main menu
	 */
	function SectionTitle() {
		// Get menu - use obj() to cache it in the same place as the template engine
		$menu = $this->obj('MainMenu');
		
		foreach($menu as $menuItem) {
			if($menuItem->LinkingMode == 'current') return $menuItem->Title;
		}
	}

	/**
	 * The application logo path. Customisable by calling
	 * LeftAndMain::setLogo() - the first parameter.
	 *
	 * @var unknown_type
	 */
	static $application_logo = 'sapphire/admin/images/mainmenu/logo.gif';

	/**
	 * The application logo style. Customisable by calling
	 * LeftAndMain::setLogo() - the second parameter.
	 *
	 * @var String
	 */
	static $application_logo_style = '';
	
	/**
	 * Set the CMS application logo.
	 *
	 * @param String $logo Relative path to the logo
	 * @param String $logoStyle Custom CSS styles for the logo
	 * 							e.g. "border: 1px solid red; padding: 5px;"
	 */
	static function setLogo($logo, $logoStyle) {
		self::$application_logo = $logo;
		self::$application_logo_style = $logoStyle;
		self::$application_logo_text = '';
	}
	
	/**
	 * The height of the image should be around 164px to avoid the overlaping between the image and loading animation graphic.
	 * If the given image's height is significantly larger or smaller, adjust the loading animation's top offset in 
	 * positionLoadingSpinner() in LeftAndMain.js
	 */
	protected static $loading_image = 'sapphire/admin/images/logo.gif';
	
	/**
	 * Set the image shown when the CMS is loading.
	 */
	static function set_loading_image($loadingImage) {
		self::$loading_image = $loadingImage;
	}
	
	function LoadingImage() {
		return self::$loading_image;
	}
	
	/**
	 * Combines an optional background image and additional CSS styles,
	 * set through {@link setLogo()}.
	 * 
	 * @return String CSS attribute
	 */
	function LogoStyle() {
		$attr = self::$application_logo_style;
		if(self::$application_logo) $attr .= "background: url(" . self::$application_logo . ") no-repeat; ";
		return $attr;
	}

	/**
	 * Return the base directory of the tiny_mce codebase
	 */
	function MceRoot() {
		return MCE_ROOT;
	}
	
	/**
	 * Register the given javascript file as required in the CMS.
	 * Filenames should be relative to the base, eg, SAPPHIRE_DIR . '/javascript/loader.js'
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
?>