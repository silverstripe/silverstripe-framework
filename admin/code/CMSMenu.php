<?php
/**
 * The object manages the main CMS menu. See {@link LeftAndMain::init()} for 
 * example usage.
 * 
 * The menu will be automatically populated with menu items for subclasses of 
 * {@link LeftAndMain}. That is, for each class in the CMS that creates an 
 * administration panel, a CMS menu item will be created. The default 
 * configuration will also include a 'help' link to the SilverStripe user 
 * documentation.
 *
 * Additional CMSMenu items can be added through {@link LeftAndMainExtension::init()}
 * extensions added to {@link LeftAndMain}.
 * 
 * @package framework
 * @subpackage admin
 */
class CMSMenu extends Object implements IteratorAggregate, i18nEntityProvider {
	
	/**
	 * An array of changes to be made to the menu items, in the order that the changes should be
	 * applied.  Each item is a map in one of the two forms:
	 *  - array('type' => 'add', 'item' => new CMSMenuItem(...) )
	 *  - array('type' => 'remove', 'code' => 'codename' )
	 */
	protected static $menu_item_changes = array();
	
	/**
	 * Set to true if clear_menu() is called, to indicate that the default menu shouldn't be
	 * included
	 */
	protected static $menu_is_cleared = false;

	/**
	 * Generate CMS main menu items by collecting valid 
	 * subclasses of {@link LeftAndMain}
	 */
	public static function populate_menu() {
		self::$menu_is_cleared = false;
	}

	/**
	 * Add a LeftAndMain controller to the CMS menu.
	 *
	 * @param string $controllerClass The class name of the controller
	 * @return The result of the operation
	 * @todo A director rule is added when a controller link is added, but it won't be removed
	 *			when the item is removed. Functionality needed in {@link Director}.
	 */	
	public static function add_controller($controllerClass) {
		if($menuItem = self::menuitem_for_controller($controllerClass)) {
			self::add_menu_item_obj($controllerClass, $menuItem);
		}
	}
	
	/**
	 * Return a CMSMenuItem to add the given controller to the CMSMenu
	 */
	protected static function menuitem_for_controller($controllerClass) {
		$urlBase      = Config::inst()->get($controllerClass, 'url_base', Config::FIRST_SET);
		$urlSegment   = Config::inst()->get($controllerClass, 'url_segment', Config::FIRST_SET);
		$menuPriority = Config::inst()->get($controllerClass, 'menu_priority', Config::FIRST_SET);
		
		// Don't add menu items defined the old way
		if($urlSegment === null && $controllerClass != "CMSMain") return;

		$link = Controller::join_links($urlBase, $urlSegment) . '/';

		// doesn't work if called outside of a controller context (e.g. in _config.php)
		// as the locale won't be detected properly. Use {@link LeftAndMain->MainMenu()} to update
		// titles for existing menu entries
		$defaultTitle = LeftAndMain::menu_title_for_class($controllerClass);
		$menuTitle = _t("{$controllerClass}.MENUTITLE", $defaultTitle);

		return new CMSMenuItem($menuTitle, $link, $controllerClass, $menuPriority);
	}

	
	/**
	 * Add an arbitrary URL to the CMS menu.
	 *
	 * @param string $code A unique identifier (used to create a CSS ID and its key in {@link $menu_items})
	 * @param string $menuTitle The link's title in the CMS menu
	 * @param string $url The url of the link
	 * @param integer $priority The menu priority (sorting order) of the menu item.  Higher priorities will be further
	 *                          left.
	 * @param array $attributes an array of attributes to include on the link.
	 *
	 * @return boolean The result of the operation.
	 */
	public static function add_link($code, $menuTitle, $url, $priority = -1, $attributes = null) {
		return self::add_menu_item($code, $menuTitle, $url, null, $priority, $attributes);
	}
	
	/**
	 * Add a navigation item to the main administration menu showing in the top bar.
	 *
	 * uses {@link CMSMenu::$menu_items}
	 *
	 * @param string $code Unique identifier for this menu item (e.g. used by {@link replace_menu_item()} and
	 * 					{@link remove_menu_item}. Also used as a CSS-class for icon customization.
	 * @param string $menuTitle Localized title showing in the menu bar 
	 * @param string $url A relative URL that will be linked in the menu bar.
	 * @param string $controllerClass The controller class for this menu, used to check permisssions.  
	 * 					If blank, it's assumed that this is public, and always shown to users who 
	 * 					have the rights to access some other part of the admin area.
	 * @param array $attributes an array of attributes to include on the link.
	 *
	 * @return boolean Success
	 */
	public static function add_menu_item($code, $menuTitle, $url, $controllerClass = null, $priority = -1,
											$attributes = null) {
		// If a class is defined, then force the use of that as a code.  This helps prevent menu item duplication
		if($controllerClass) {
			$code = $controllerClass;
		}
		
		return self::replace_menu_item($code, $menuTitle, $url, $controllerClass, $priority, $attributes);
	}
	
	/**
	 * Get a single menu item by its code value.
	 *
	 * @param string $code
	 * @return array
	 */
	public static function get_menu_item($code) {
		$menuItems = self::get_menu_items();
		return (isset($menuItems[$code])) ? $menuItems[$code] : false; 
	}
	
	/**
	 * Get all menu entries.
	 *
	 * @return array
	 */
	public static function get_menu_items() {
		$menuItems = array();

		// Set up default menu items
		if(!self::$menu_is_cleared) {
			$cmsClasses = self::get_cms_classes();
			foreach($cmsClasses as $cmsClass) {
				$menuItem = self::menuitem_for_controller($cmsClass);
				if($menuItem) $menuItems[Convert::raw2htmlname(str_replace('\\', '-', $cmsClass))] = $menuItem;
			}
		}
		
		// Apply changes
		foreach(self::$menu_item_changes as $change) {
			switch($change['type']) {
				case 'add':
					$menuItems[$change['code']] = $change['item'];
					break;
				
				case 'remove':
					unset($menuItems[$change['code']]);
					break;
					
				default:
					user_error("Bad menu item change type {$change[type]}", E_USER_WARNING);
			}
		}
		
		// Sort menu items according to priority, then title asc
		$menuPriority = array();
		$menuTitle    = array();
		foreach($menuItems as $key => $menuItem) {
			$menuPriority[$key] = is_numeric($menuItem->priority) ? $menuItem->priority : 0;
			$menuTitle[$key]    = $menuItem->title;
		}
		array_multisort($menuPriority, SORT_DESC, $menuTitle, SORT_ASC, $menuItems);
		
		return $menuItems;
	}
	
	/**
	 * Get all menu items that the passed member can view.
	 * Defaults to {@link Member::currentUser()}.
	 * 
	 * @param Member $member
	 * @return array
	 */
	public static function get_viewable_menu_items($member = null) {
		if(!$member && $member !== FALSE) {
			$member = Member::currentUser();
		}
		
		$viewableMenuItems = array();
		$allMenuItems = self::get_menu_items();
		if($allMenuItems) foreach($allMenuItems as $code => $menuItem) {
			// exclude all items which have a controller to perform permission
			// checks on
			if($menuItem->controller) {
				$controllerObj = singleton($menuItem->controller);
				if(Controller::has_curr()) {
					// Necessary for canView() to have request data available,
					// e.g. to check permissions against LeftAndMain->currentPage()
					$controllerObj->setRequest(Controller::curr()->getRequest());
					if(!$controllerObj->canView($member)) continue;
				}
			}
			
			$viewableMenuItems[$code] = $menuItem;
		}
		
		return $viewableMenuItems;
	}
	
	/**
	 * Removes an existing item from the menu.
	 *
	 * @param string $code Unique identifier for this menu item
	 */
	public static function remove_menu_item($code) {
		self::$menu_item_changes[] = array('type' => 'remove', 'code' => $code);
	}
	
	/**
	 * Clears the entire menu
	 */
	public static function clear_menu() {
		self::$menu_item_changes = array();
		self::$menu_is_cleared = true;
	}

	/**
	 * Replace a navigation item to the main administration menu showing in the top bar.
	 *
	 * @param string $code Unique identifier for this menu item (e.g. used by {@link replace_menu_item()} and
	 * 					{@link remove_menu_item}. Also used as a CSS-class for icon customization.
	 * @param string $menuTitle Localized title showing in the menu bar 
	 * @param string $url A relative URL that will be linked in the menu bar.
	 * 					Make sure to add a matching route via {@link Director::$rules} to this url.
	 * @param string $controllerClass The controller class for this menu, used to check permisssions.  
	 * 					If blank, it's assumed that this is public, and always shown to users who 
	 * 					have the rights to access some other part of the admin area.
	 * @param array $attributes an array of attributes to include on the link.
	 *
	 * @return boolean Success
	 */
	public static function replace_menu_item($code, $menuTitle, $url, $controllerClass = null, $priority = -1,
												$attributes = null) {
		$item = new CMSMenuItem($menuTitle, $url, $controllerClass, $priority);

		if($attributes) {
			$item->setAttributes($attributes);
		}

		self::$menu_item_changes[] = array(
			'type' => 'add',
			'code' => $code,
			'item' => $item,
		);
	}
	
	/**
	 * Add a previously built menu item object to the menu
	 */
	protected static function add_menu_item_obj($code, $cmsMenuItem) {
		self::$menu_item_changes[] = array(
			'type' => 'add',
			'code' => $code,
			'item' => $cmsMenuItem,
		);
	}

	/**
	 * A utility funciton to retrieve subclasses of a given class that
	 * are instantiable (ie, not abstract) and have a valid menu title.
	 *
	 * @todo A variation of this function could probably be moved to {@link ClassInfo}
	 * @param string $root The root class to begin finding subclasses
	 * @param boolean $recursive Look for subclasses recursively?
	 * @return array Valid, unique subclasses
	 */
	public static function get_cms_classes($root = 'LeftAndMain', $recursive = true) {
		$subClasses = array_values(ClassInfo::subclassesFor($root));
		foreach($subClasses as $className) {
			if($recursive && $className != $root) {
				$subClasses = array_merge($subClasses, array_values(ClassInfo::subclassesFor($className)));
			}
		}
		$subClasses = array_unique($subClasses);
		foreach($subClasses as $key => $className) {
			// Remove abstract classes and LeftAndMain
			if('LeftAndMain' == $className || ClassInfo::classImplements($className, 'TestOnly')) {
				unset($subClasses[$key]);
			} else {
				// Separate conditional to avoid autoloading the class
				$classReflection = new ReflectionClass($className);
				if(!$classReflection->isInstantiable()) unset($subClasses[$key]);
			}
		}
		
		return $subClasses;
	}
	
	/**
	 * IteratorAggregate Interface Method.  Iterates over the menu items.
	 */
	public function getIterator() {
		return new ArrayIterator(self::get_menu_items());
	}

	/**
	 * Provide menu titles to the i18n entity provider
	 */
	public function provideI18nEntities() {
		$cmsClasses = self::get_cms_classes();
		$entities = array();
		foreach($cmsClasses as $cmsClass) {
			$defaultTitle = LeftAndMain::menu_title_for_class($cmsClass);
			$ownerModule = i18n::get_owner_module($cmsClass);
			$entities["{$cmsClass}.MENUTITLE"] = array($defaultTitle, 'Menu title', $ownerModule);
		}
		return $entities;
	}
}
