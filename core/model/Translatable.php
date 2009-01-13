<?php
/**
 * The Translatable decorator allows your DataObjects to have versions in different languages,
 * defining which fields are can be translated.
 * 
 * Common language names (e.g. 'en') are used in Translatable for
 * database-entities. On the other hand, the file-based i18n-translations 
 * always have a "locale" (e.g. 'en_US').
 * 
 * You can enable {Translatabe} for any DataObject-subclass:
 * <example>
 * static $extensions = array(
 * 	"Translatable('MyTranslatableVarchar', 'OtherTranslatableText')"
 * );
 * </example>
 * 
 * Caution: Does not apply any character-set conversion, it is assumed that all content
 * is stored and represented in UTF-8 (Unicode). Please make sure your database and
 * HTML-templates adjust to this.
 * 
 * @author Bernat Foj Capell <bernat@silverstripe.com>
 * @author Michael Gall <michael (at) wakeless (dot) net>
 * @author Ingo Schommer <ingo (at) silverstripe (dot) com>
 * 
 * @package sapphire
 * @subpackage misc
 */
class Translatable extends DataObjectDecorator {
	
	/**
	 * Indicates if the multilingual feature is enabled
	 *
	 * @var boolean
	 */
	protected static $enabled = false;

	/**
	 * The 'default' language.
	 * @var string
	 */
	protected static $default_lang = 'en';
	
	/**
	 * The language in which we are reading dataobjects.
	 * Usually stored in session, specific to the "site mode":
	 * either 'site' or 'cms'.
	 * @see Director::get_site_mode()
	 * @var string
	 */
	protected static $reading_lang = null;
	
	/**
	 * Indicates if the start language has been determined using choose_site_lang
	 * @var boolean
	 */
	protected static $language_decided = false;
	
	/**
	 * Indicates whether the 'Lang' transformation when modifying queries should be bypassed
	 * If it's true
	 *
	 * @var boolean
	 */
	protected static $bypass = false;
	
	/**
	 * A cached list of existing tables
	 *
	 * @var mixed
	 */
	protected static $tableList = null;
	
	/**
	 * Dataobject's original ID when we're creating a new language version of an object
	 *
	 * @var unknown_type
	 */
	protected static $creatingFromID;

	/**
	 * An array of fields that can be translated.
	 * @var array
	 */
	protected $translatableFields;

	/**
	 * A map of the field values of the original (untranslated) DataObject record
	 * @var array
	 */
	protected $original_values = null;

	/**
	 * Overloaded getter for $Lang property.
	 * Not all pages in the database have their language property explicitly set,
	 * so we fall back to {@link Translatable::default_lang()}.
	 */
	function getLang() {
		$record = $this->owner->toMap();
		return (isset($record["Lang"])) ? $record["Lang"] : Translatable::default_lang();
	}

	/**
	 * Checks if a table given table exists in the db
	 *
	 * @param mixed $table Table name
	 * @return boolean Returns true if $table exists.
	 */
	static function table_exists($table) {
		if (!self::$tableList) self::$tableList = DB::tableList();
		return isset(self::$tableList[strtolower($table)]);
	}
	
	/**
	 * Choose the language the site is currently on.
	 * If $_GET['lang'] or $_COOKIE['lang'] is set, then it will use that language, and store it in the session.
	 * Otherwise it checks the session for a possible stored language, either from namespace to the site_mode
	 * ('site' or 'cms'), or for a 'global' language setting. 
	 * The final option is the member preference.
	 * 
	 * @uses Director::get_site_mode()
	 * 
	 * @param $langsAvailable array A numerical array of languages which are valid choices (optional)
	 * @return string Selected language (also saved in $reading_lang).
	 */
	static function choose_site_lang($langsAvailable = array()) {
		$siteMode = Director::get_site_mode(); // either 'cms' or 'site'
		if(self::$reading_lang) {
			self::$language_decided = true;
			return self::$reading_lang;
		}

		if(
			(isset($_GET['lang']) && !$langsAvailable) 
			|| (isset($_GET['lang']) && in_array($_GET['lang'], $langsAvailable))
		) {
			// get from GET parameter
			self::set_reading_lang($_GET['lang']);
		/*
		} elseif(isset($_COOKIE['lang.' . $siteMode]) && $siteMode && (!isset($langsAvailable) || in_array($_COOKIE['lang.' . $siteMode], $langsAvailable))) {
			// get from namespaced cookie
			self::set_reading_lang($_COOKIE[$siteMode . '.lang']);
		} elseif(isset($_COOKIE['lang']) && (!isset($langsAvailable) || in_array($_COOKIE['lang'], $langsAvailable))) {
			// get from generic cookie
			self::set_reading_lang($_COOKIE['lang']);
		} else if(Session::get('lang.' . $siteMode) && (!isset($langsAvailable) || in_array(Session::get('lang.' . $siteMode), $langsAvailable))) {
			// get from namespaced session ('cms' or 'site') 
			self::set_reading_lang(Session::get('lang.' . $siteMode));
		} else if(Session::get('lang.global') && (!isset($langsAvailable) || in_array(Session::get('lang.global'), $langsAvailable))) {
			// get from global session 
			self::set_reading_lang(Session::get('lang.global'));
		} else {
			get default lang stored in class
			self::set_reading_lang(self::default_lang());
		*/
		}
		self::$language_decided = true;
		return self::$reading_lang; 
	}
		
	/**
	 * Get the current reading language.
	 * This value has to be set before the schema is built with translatable enabled,
	 * any changes after this can cause unintended side-effects.
	 * 
	 * @return string
	 */
	static function default_lang() {
		return self::$default_lang;
	}
	
	/**
	 * Set default language.
	 * 
	 * @param $lang String
	 */
	static function set_default_lang($lang) {
		self::$default_lang = $lang;
	}

	/**
	 * Check whether the default and current reading language are the same.
	 * @return boolean Return true if both default and reading language are the same.
	 */
	static function is_default_lang() {
		return (!self::current_lang() || self::$default_lang == self::current_lang());
	}

	/**
	 * Get the current reading language.
	 * @return string
	 */
	static function current_lang() {
		if (!self::$language_decided) self::choose_site_lang();
		return self::$reading_lang;
	}
		
	/**
	 * Set the reading language, either namespaced to 'site' (website content)
	 * or 'cms' (management backend).
	 * 
	 * @param string $lang New reading language.
	 */
	static function set_reading_lang($lang) {
		//Session::set('currentLang',$lang); 
		self::$reading_lang = $lang;
	}	
	
	/**
	 * Get a singleton instance of a class in the given language.
	 * @param string $class The name of the class.
	 * @param string $lang  The name of the language.
	 * @param string $filter A filter to be inserted into the WHERE clause.
	 * @param boolean $cache Use caching (default: false)
	 * @param string $orderby A sort expression to be inserted into the ORDER BY clause.
	 * @return DataObject
	 */
	static function get_one_by_lang($class, $lang, $filter = '', $cache = false, $orderby = "") {
		$orig = Translatable::current_lang();
		Translatable::set_reading_lang($lang);
		$do = DataObject::get_one($class, $filter, $cache, $orderby);
		Translatable::set_reading_lang($orig);
		return $do;
	}

	/**
	 * Get all the instances of the given class translated to the given language
	 *
	 * @param string $class The name of the class
	 * @param string $lang  The name of the language
	 * @param string $filter A filter to be inserted into the WHERE clause.
	 * @param string $sort A sort expression to be inserted into the ORDER BY clause.
	 * @param string $join A single join clause.  This can be used for filtering, only 1 instance of each DataObject will be returned.
	 * @param string $limit A limit expression to be inserted into the LIMIT clause.
	 * @param string $containerClass The container class to return the results in.
	 * @param string $having A filter to be inserted into the HAVING clause.
	 * @return mixed The objects matching the conditions.
	 */
	static function get_by_lang($class, $lang, $filter = '', $sort = '', $join = "", $limit = "", $containerClass = "DataObjectSet", $having = "") {
		$oldLang = self::current_lang();
		self::set_reading_lang($lang);
		$result = DataObject::get($class, $filter, $sort, $join, $limit, $containerClass, $having);
		self::set_reading_lang($oldLang);
		return $result;
	}
	
	/**
	 * Get a record in his original language version.
	 * @param string $class The name of the class.
	 * @param string $originalLangID  The original record id.
	 * @return DataObject
	 */
	static function get_original($class, $originalLangID) {
		$baseClass = $class;
		while( ($p = get_parent_class($baseClass)) != "DataObject") $baseClass = $p;
		return self::get_one_by_lang($class,self::default_lang(),"\"$baseClass\".\"ID\" = $originalLangID");
	}
	
	/**
	 * Gets all translations for this specific page.
	 * Doesn't include the original language code ({@link Translatable::default_lang()}).
	 * 
	 * @return array Numeric array of all language codes, sorted alphabetically.
	 */
	function getTranslatedLangs() {
		$langs = array();
		
		$class = ClassInfo::baseDataClass($this->owner->class); //Base Class
		if($this->owner->hasExtension("Versioned")  && Versioned::current_stage() == "Live") {
			$class = $class."_Live";
		}
		
		$id = $this->owner->ID;
		if(is_numeric($id)) {
			$query = new SQLQuery('distinct Lang',"$class","(\"$class\".\"OriginalID\" =$id)");
			$langs = $query->execute()->column();
		}
		if($langs) {
			$langCodes = array_values($langs);
			sort($langCodes);
			return $langCodes;
		} else {
			return array();
		};
	}

	/**
	 * Get a list of languages in which a given element has been translated
	 *
	 * @param string $class Name of the class of the element
	 * @param int $id ID of the element
	 * @return array List of languages
	 */
	static function get_langs_by_id($class, $id) {
		$do = DataObject::get_by_id($class, $id);
		return ($do ? $do->getTranslatedLangs() : array());
	}

	/**
	 * Enables the multilingual feature
	 *
	 */
	static function enable() {
		self::$enabled = true;
	}

	/**
	 * Disable the multilingual feature
	 *
	 */
	static function disable() {
		self::$enabled = false;
	}
	
	/**
	 * Check whether multilingual support has been enabled
	 *
	 * @return boolean True if enabled
	 */
	static function is_enabled() {
		return self::$enabled;
	}
	
	/**
	 * When creating, set the original ID value
	 *
	 * @param int $id
	 */
	static function creating_from($id) {
		self::$creatingFromID = $id;
	}

	
		//-----------------------------------------------------------------------------------------------//
	
		
	/**
	 * Construct a new Translatable object.
	 * @var array $translatableFields The different fields of the object that can be translated.
	 */
	function __construct($translatableFields) {
		parent::__construct();

		// @todo Disabled selection of translatable fields - we're setting all fields as translatable in setOwner()
		/*
		if(!is_array($translatableFields)) {
			$translatableFields = func_get_args();
		}
		$this->translatableFields = $translatableFields;
		*/

		// workaround for extending a method on another decorator (Hierarchy):
		// split the method into two calls, and overwrite the wrapper AllChildrenIncludingDeleted()
		// Has to be executed even with Translatable disabled, as it overwrites the method with same name
		// on Hierarchy class, and routes through to Hierarchy->doAllChildrenIncludingDeleted() instead.
		$this->createMethod("AllChildrenIncludingDeleted",
			"
			\$context = (isset(\$args[0])) ? \$args[0] : null;
			if(\$context && \$obj->getLang() == \$context->Lang && \$obj->isTranslation()) { 
				// if the language matches the context (e.g. CMSMain), and object is translated,
				// then call method on original language instead
				return \$obj->getOwner()->getOriginalPage()->doAllChildrenIncludingDeleted(\$context);
			} else if(\$obj->getOwner()->hasExtension('Hierarchy') ) {
				return \$obj->getOwner()->extInstance('Hierarchy')->doAllChildrenIncludingDeleted(\$context);
			} else {
				return null;
			}"
		);
	}
	
	function setOwner(Object $owner) {
		parent::setOwner($owner);

		// setting translatable fields by inspecting owner - this should really be done in the constructor
		$this->translatableFields = array_keys($this->owner->inheritedDatabaseFields());
	}
	
	function extraStatics() {
		if(!Translatable::is_enabled()) return;
		
		if(get_class($this->owner) == ClassInfo::baseDataClass(get_class($this->owner))) {
			return array(
				"db" => array(
						"Lang" => "Varchar(12)",
						"OriginalID" => "Int"
				),
				"defaults" => array(
					"Lang" => Translatable::default_lang()
				)
			);
		} else {
			return array();
		}
	}
	
	function findOriginalIDs() {
		if(!$this->isTranslation()) {
			$query = new SQLQuery("ID", 
				ClassInfo::baseDataClass($this->owner->class), 
				array("OriginalID = ".$this->owner->ID)
			);
			$ret = $query->execute()->column();
			
		} else {
			return array();
		}
	}

	function augmentSQL(SQLQuery &$query) {
		if(!Translatable::is_enabled()) return;
		
		$lang = Translatable::current_lang();
		$baseTable = ClassInfo::baseDataClass($this->owner->class);
		$where = $query->where;
		if (
			$lang
			&& !$query->filtersOnID() 
			&& array_search($baseTable, array_keys($query->from)) !== false 
			&& !$this->isTranslation() 
			//&& !$query->filtersOnFK()
		)  {
			$qry = "\"Lang\" = '$lang'";
			if(Translatable::is_default_lang()) {
				$qry .= " OR \"Lang\" = '' ";
				$qry .= " OR \"Lang\" IS NULL ";
			}
			$query->where[] = $qry; 
		}
	}
	
	function augmentNumChildrenCountQuery(SQLQuery $query) {
		if(!Translatable::is_enabled()) return;
		
		if($this->isTranslation()) {
			$query->where[0] = '"ParentID" = '.$this->getOriginalPage()->ID;
		}
	}
	
	/**
	 * @var SiteTree $cache_originalPage Cached representation of the original page for this translation
	 * (if at all in translation mode)
	 */
	private $cache_originalPage = null;
	
	function setOriginalPage($original) {
		if($original instanceof DataObject) {
			$this->owner->OriginalID = $original->ID;
		} else {
			$this->owner->OriginalID = $original;
		}
	}
	
	function getOriginalPage() {
		if($this->isTranslation()) {
			if(!$this->cache_originalPage) {
				$orig = Translatable::current_lang();
				Translatable::set_reading_lang(Translatable::default_lang());
				$this->cache_originalPage = DataObject::get_by_id($this->owner->class, $this->owner->OriginalID);
				Translatable::set_reading_lang($orig);
			}
			return $this->cache_originalPage;
		} else {
			return $this->owner;
		}
	}
	
	function isTranslation() {
		if($this->getLang() && ($this->getLang() != Translatable::default_lang()) && $this->owner->exists()) {
			return true;
		} else {
			return false;
		}
	}
	
	/**
	 * Determine if a table needs Versioned support
	 * This is called at db/build time
	 *
	 * @param string $table Table name
	 * @return boolean
	 */
	function isVersionedTable($table) {
		return false;
	}

	function contentcontrollerInit($controller) {
		if(!Translatable::is_enabled()) return;
		Translatable::choose_site_lang();
		$controller->Lang = Translatable::current_lang();
	}
	
	function modelascontrollerInit($controller) {
		if(!Translatable::is_enabled()) return;
		
		//$this->contentcontrollerInit($controller);
	}
	
	function initgetEditForm($controller) {
		if(!Translatable::is_enabled()) return;
		
		$this->contentcontrollerInit($controller);
	}

	function augmentWrite(&$manipulation) {
		if(!Translatable::is_enabled()) return;
		
		if(!$this->isTranslation()) {
			$ids = $this->findOriginalIDs();
			if(!$ids || count($ids) == 0) return;
		}
		$newManip = array();
		foreach($manipulation as $table => $manip) {
			if(strpos($table, "_versions") !== false) continue;
			/*
			foreach($this->fieldBlackList as $blackField) {
				if(isset($manip["fields"][$blackField])) {
					if($this->isTranslation()) {
						unset($manip["fields"][$blackField]);
					} else {
						if(!isset($newManip[$table])) {
							$newManip[$table] = array("command" =>"update", 
							"where" => "ID in (".implode(",", $ids).")",
							"fields" => array());
						}
						$newManip[$table]["fields"][$blackField] = $manip["fields"][$blackField];
					}
				}
			}
			*/
		}
		DB::manipulate($newManip);	
	}

	//-----------------------------------------------------------------------------------------------//
	
	function updateCMSFields(FieldSet &$fields) {
		if(!Translatable::is_enabled()) return;
		
		// used in CMSMain->init() to set language state when reading/writing record
		$fields->push(new HiddenField("Lang", "Lang", $this->getLang()) );
		$fields->push(new HiddenField("OriginalID", "OriginalID", $this->owner->OriginalID) );

		// if a language other than default language is used, we're in "translation mode",
		// hence have to modify the original fields
		$creating = false;
		$baseClass = $this->owner->class;
		$allFields = $fields->toArray();
		while( ($p = get_parent_class($baseClass)) != "DataObject") $baseClass = $p;
		$isTranslationMode = (Translatable::default_lang() != $this->getLang() && $this->getLang());

		if($isTranslationMode) {
			$originalLangID = Session::get($this->owner->ID . '_originalLangID');
			
			$translatableFieldNames = $this->getTranslatableFields();
			$allDataFields = $fields->dataFields();
			$originalRecord = $this->owner->getOriginalPage();
			$transformation = new Translatable_Transformation($originalRecord);
			
			// iterate through sequential list of all datafields in fieldset
			// (fields are object references, so we can replace them with the translatable CompositeField)
			foreach($allDataFields as $dataField) {
				
				if(in_array($dataField->Name(), $translatableFieldNames)) {
					// if the field is translatable, perform transformation
					$fields->replaceField($dataField->Name(), $transformation->transformFormField($dataField));
				} else {
					// else field shouldn't be editable in translation-mode, make readonly
					$fields->replaceField($dataField->Name(), $dataField->performReadonlyTransformation());
				}
			}
		} elseif($this->owner->isNew()) {
			$fields->addFieldsToTab(
				'Root',
				new Tab(_t('Translatable.TRANSLATIONS', 'Translations'),
					new LiteralField('SaveBeforeCreatingTranslationNote',
						sprintf('<p class="message">%s</p>',
							_t('Translatable.NOTICENEWPAGE', 'Please save this page before creating a translation')
						)
					)
				)
			);
		} else {
			// if we're not in "translation mode", show a dropdown to create a new translation.
			// this action should just be possible when showing the default language,
			// you can't create new translations from within a "translation mode" form.
			$alreadyTranslatedLangs = $this->getTranslatedLangs();
			
			$fields->addFieldsToTab(
				'Root',
				new Tab(_t('Translatable.TRANSLATIONS', 'Translations'),
					new HeaderField('CreateTransHeader', _t('Translatable.CREATE', 'Create new translation'), 2),
					$langDropdown = new LanguageDropdownField("NewTransLang", _t('Translatable.NEWLANGUAGE', 'New language'), $alreadyTranslatedLangs),
					$createButton = new InlineFormAction('createtranslation',_t('Translatable.CREATEBUTTON', 'Create'))
				)
			);

			if($alreadyTranslatedLangs) {
				$fields->addFieldToTab(
					'Root.Translations',
					new HeaderField('ExistingTransHeader', _t('Translatable.EXISTING', 'Existing translations:'), 3)
				);
				$existingTransHTML = '<ul>';
				foreach($alreadyTranslatedLangs as $i => $langCode) {
					$existingTranslation = $this->owner->getTranslation($langCode);
					$existingTransHTML .= sprintf('<li><a href="%s">%s</a></li>',
						sprintf('admin/show/%d/?lang=%s', $existingTranslation->ID, $langCode),
						i18n::get_language_name($langCode)
					);
				}
				$existingTransHTML .= '</ul>';
				$fields->addFieldToTab(
					'Root.Translations',
					new LiteralField('existingtrans',$existingTransHTML)
				);
			}
			

			$langDropdown->addExtraClass('languageDropdown');
			$createButton->addExtraClass('createTranslationButton');
			
			// disable creation of new pages via javascript
			$createButton->includeDefaultJS(false);
		}
	}
	
	/**
	 * Get a list of fields from the tables created by this extension
	 *
	 * @param string $table Name of the table
	 * @return array Map where the keys are db, indexes and the values are the table fields
	 */
	function fieldsInExtraTables($table){
		return array('db'=>null,'indexes'=>null);
	}
	
	/**
	 * Get the names of all translatable fields on this class
	 * as a numeric array.
	 * @todo Integrate with blacklist once branches/translatable is merged back.
	 * 
	 * @return array
	 */
	function getTranslatableFields() {
		return $this->translatableFields;
	}
		
	/**
	 * Return the base table - the class that directly extends DataObject.
	 * @return string
	 */
	function baseTable($stage = null) {
		$tableClasses = ClassInfo::dataClassesFor($this->owner->class);
		$baseClass = array_shift($tableClasses);
		return (!$stage || $stage == $this->defaultStage) ? $baseClass : $baseClass . "_$stage";		
	}
	
	function extendWithSuffix($table) {
		return $table;
	}
	
	/**
	 * Gets an existing translation based on the language code.
	 * Use {@link hasTranslation()} as a quicker alternative to check
	 * for an existing translation without getting the actual object.
	 * 
	 * @param String $lang
	 * @return DataObject Translated object
	 */
	function getTranslation($lang) {
		if($this->owner->exists() && !$this->owner->isTranslation()) {
			$orig = Translatable::current_lang();
			$this->owner->flushCache();
			Translatable::set_reading_lang($lang);
			
			$filter = array("`OriginalID` = '".$this->owner->ID."'");
			
			if($this->owner->hasExtension("Versioned") && Versioned::current_stage()) {
				$translation = Versioned::get_one_by_stage($this->owner->class, Versioned::current_stage(), $filter);
			} else {
				$translation = DataObject::get_one($this->owner->class, $filter);
			}

			Translatable::set_reading_lang($orig);
			
			return $translation;
		}
	}
	
	/**
	 * Creates a new translation for the owner object of this decorator.
	 * Checks {@link getTranslation()} to return an existing translation
	 * instead of creating a duplicate. Writes the record to the database before
	 * returning it.
	 * 
	 * @param string $lang
	 * @return DataObject The translated object
	 */
	function createTranslation($lang) {
		$existingTranslation = $this->getTranslation($lang);
		if($existingTranslation) return $existingTranslation;
		
		$class = $this->owner->class;
		$newTranslation = new $class;
		$newTranslation->update($this->owner->toMap());
		$newTranslation->ID = 0;
		$newTranslation->setOriginalPage($this->owner->ID);
		$newTranslation->Lang = $lang;
		$newTranslation->write();
		
		return $newTranslation;
	}
	
	/**
	 * Returns TRUE if the current record has a translation in this language.
	 * Use {@link getTranslation()} to get the actual translated record from
	 * the database.
	 * 
	 * @return boolean
	 */
	function hasTranslation($lang) {
		return ($this->owner->exists()) && (array_search($lang, $this->getTranslatedLangs()) !== false);
	}
	
	function augmentStageChildren(DataObjectSet $children, $showall = false) {
		if(!Translatable::is_enabled()) return;
		
		if($this->isTranslation()) {
			$children->merge($this->getOriginalPage()->stageChildren($showall));
		}
	}
	
	function augmentAllChildrenIncludingDeleted(DataObjectSet $children, $context = null) {
		if(!Translatable::is_enabled()) return false;

		$find = array();
		$replace = array();
		
		// @todo check usage of $context
		if($context && $context->Lang /*&& $this->owner->Lang != $context->Lang */&& $context->Lang != Translatable::default_lang()) {
			if($children) {
				foreach($children as $child) {
					if($child->hasTranslation($context->Lang)) {
						$trans = $child->getTranslation($context->Lang);
						$find[] = $child;
						$replace[] = $trans;
					}
				}
				foreach($find as $i => $found) {
					$children->replace($found, $replace[$i]);
				}
			}
		}
		
	}
	
	/**
	 * Get a list of languages with at least one element translated in (including the default language)
	 *
	 * @param string $className Look for languages in elements of this class
	 * @return array Map of languages in the form langCode => langName
	 */
	static function get_existing_content_languages($className = 'SiteTree', $where = '') {
		if(!Translatable::is_enabled()) return false;
		$baseTable = ClassInfo::baseDataClass($className);
		$query = new SQLQuery('Distinct Lang',$baseTable,$where,"",'Lang');
		$dbLangs = $query->execute()->column();
		$langlist = array_merge((array)Translatable::default_lang(), (array)$dbLangs);
		$returnMap = array();
		$allCodes = array_merge(i18n::$all_locales, i18n::$common_languages);
		foreach ($langlist as $langCode) {
			if($langCode)
				$returnMap[$langCode] = (is_array($allCodes[$langCode]) ? $allCodes[$langCode][0] : $allCodes[$langCode]);
		}
		return $returnMap;
	}
		
}

/**
 * Transform a formfield to a "translatable" representation,
 * consisting of the original formfield plus a readonly-version
 * of the original value, wrapped in a CompositeField.
 * 
 * @param DataObject $original Needs the original record as we populate the readonly formfield with the original value
 * 
 * @package sapphire
 * @subpackage misc
 */
class Translatable_Transformation extends FormTransformation {
	
	/**
	 * @var DataObject
	 */
	private $original = null;
	
	function __construct(DataObject $original) {
		$this->original = $original;
		parent::__construct();
	}
	
	/**
	 * Returns the original DataObject attached to the Transformation
	 *
	 * @return DataObject
	 */
	function getOriginal() {
		return $this->original;
	}
	
	/**
	 * @todo transformTextareaField() not used at the moment
	 */
	function transformTextareaField(TextareaField $field) {
		$nonEditableField = new ToggleField($fieldname,$field->Title(),'','+','-');
		$nonEditableField->labelMore = '+';
		$nonEditableField->labelLess = '-';
		return $this->baseTransform($nonEditableField, $field);
		
		return $nonEditableField;
	}
	
	function transformFormField(FormField $field) {
		$newfield = $field->performReadOnlyTransformation();
		return $this->baseTransform($newfield, $field);
	}
	
	protected function baseTransform($nonEditableField, $originalField) {
		$fieldname = $originalField->Name();
		
		$nonEditableField_holder = new CompositeField($nonEditableField);
		$nonEditableField_holder->setName($fieldname.'_holder');
		$nonEditableField_holder->addExtraClass('originallang_holder');
		$nonEditableField->setValue($this->original->$fieldname);
		$nonEditableField->setName($fieldname.'_original');
		$nonEditableField->addExtraClass('originallang');
		$nonEditableField->setTitle('Original '.$originalField->Title());
		
		$nonEditableField_holder->insertBefore($originalField, $fieldname.'_original');
		return $nonEditableField_holder;
	}
	
	
}

?>