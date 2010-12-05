<?php
/**
 * The Translatable decorator allows your DataObjects to have versions in different languages,
 * defining which fields are can be translated. Translatable can be applied
 * to any {@link DataObject} subclass, but is mostly used with {@link SiteTree}.
 * Translatable is compatible with the {@link Versioned} extension.
 * To avoid cluttering up the database-schema of the 99% of sites without multiple languages,
 * the translation-feature is disabled by default.
 * 
 * Locales (e.g. 'en_US') are used in Translatable for identifying a record by language,
 * see section "Locales and Language Tags".
 * 
 * <h2>Configuration</h2>
 * 
 * <h3>Through Object::add_extension()</h3>
 * Enabling Translatable through {@link Object::add_extension()} in your _config.php:
 * <code>
 * Object::add_extension('MyClass', 'Translatable');
 * </code>
 * This is the recommended approach for enabling Translatable.
 * 
 * <h3>Through $extensions</h3>
 * <code>
 * class MyClass extends DataObject {
 *   static $extensions = array(
 *     "Translatable"
 *   );
 * }
 * </code>
 * 
 * Make sure to rebuild the database through /dev/build after enabling translatable.
 * Use the correct {@link set_default_locale()} before building the database
 * for the first time, as this locale will be written on all new records.
 * 
 * <h3>"Default" locales</h3>
 * 
 * Important: If the "default language" of your site is not US-English (en_US), 
 * please ensure to set the appropriate default language for
 * your content before building the database with Translatable enabled:
 * <code>
 * Translatable::set_default_locale(<locale>); // e.g. 'de_DE' or 'fr_FR'
 * </code>
 * 
 * For the Translatable class, a "locale" consists of a language code plus a region code separated by an underscore, 
 * for example "de_AT" for German language ("de") in the region Austria ("AT").
 * See http://www.w3.org/International/articles/language-tags/ for a detailed description.
 * 
 * <h2>Usage</h2>
 *
 * Getting a translation for an existing instance: 
 * <code>
 * $translatedObj = Translatable::get_one_by_locale('MyObject', 'de_DE');
 * </code>
 * 
 * Getting a translation for an existing instance: 
 * <code>
 * $obj = DataObject::get_by_id('MyObject', 99); // original language
 * $translatedObj = $obj->getTranslation('de_DE');
 * </code>
 * 
 * Getting translations through {@link Translatable::set_current_locale()}.
 * This is *not* a recommended approach, but sometimes inavoidable (e.g. for {@link Versioned} methods).
 * <code>
 * $origLocale = Translatable::get_current_locale();
 * Translatable::set_current_locale('de_DE');
 * $obj = Versioned::get_one_by_stage('MyObject', "ID = 99");
 * Translatable::set_current_locale($origLocale);
 * </code>
 * 
 * Creating a translation: 
 * <code>
 * $obj = new MyObject();
 * $translatedObj = $obj->createTranslation('de_DE');
 * </code>
 *
 * <h2>Usage for SiteTree</h2>
 * 
 * Translatable can be used for subclasses of {@link SiteTree} as well. 
 * 
 * <code>
 * Object::add_extension('SiteTree', 'Translatable');
 * Object::add_extension('SiteConig', 'Translatable');
 * </code>
 * 
 * If a child page translation is requested without the parent
 * page already having a translation in this language, the extension
 * will recursively create translations up the tree.
 * Caution: The "URLSegment" property is enforced to be unique across
 * languages by auto-appending the language code at the end.
 * You'll need to ensure that the appropriate "reading language" is set
 * before showing links to other pages on a website through $_GET['locale'].
 * Pages in different languages can have different publication states
 * through the {@link Versioned} extension.
 * 
 * Note: You can't get Children() for a parent page in a different language
 * through set_current_locale(). Get the translated parent first.
 * 
 * <code>
 * // wrong
 * Translatable::set_current_locale('de_DE');
 * $englishParent->Children(); 
 * // right
 * $germanParent = $englishParent->getTranslation('de_DE');
 * $germanParent->Children();
 * </code>
 *
 * <h2>Translation groups</h2>
 * 
 * Each translation can have one or more related pages in other languages. 
 * This relation is optional, meaning you can
 * create translations which have no representation in the "default language".
 * This means you can have a french translation with a german original, 
 * without either of them having a representation
 * in the default english language tree.
 * Caution: There is no versioning for translation groups,
 * meaning associating an object with a group will affect both stage and live records.
 * 
 * SiteTree database table (abbreviated)
 * ^ ID ^ URLSegment ^ Title ^ Locale ^
 * | 1 | about-us | About us | en_US |
 * | 2 | ueber-uns | Über uns | de_DE |
 * | 3 | contact | Contact | en_US |
 * 
 * SiteTree_translationgroups database table
 * ^ TranslationGroupID ^ OriginalID ^
 * | 99 | 1 |
 * | 99 | 2 |
 * | 199 | 3 |
 *
 * <h2>Character Sets</h2>
 * 
 * Caution: Does not apply any character-set conversion, it is assumed that all content
 * is stored and represented in UTF-8 (Unicode). Please make sure your database and
 * HTML-templates adjust to this.
 * 
 * <h2>Permissions</h2>
 * 
 * Authors without administrative access need special permissions to edit locales other than
 * the default locale.
 * 
 * - TRANSLATE_ALL: Translate into all locales
 * - Translate_<locale>: Translate a specific locale. Only available for all locales set in
 *   `Translatable::set_allowed_locales()`.
 * 
 * Note: If user-specific view permissions are required, please overload `SiteTree->canView()`.
 * 
 * <h2>Uninstalling/Disabling</h2>
 * 
 * Disabling Translatable after creating translations will lead to all
 * pages being shown in the default sitetree regardless of their language.
 * It is advised to start with a new database after uninstalling Translatable,
 * or manually filter out translated objects through their "Locale" property
 * in the database.
 * 
 * @see http://doc.silverstripe.org/doku.php?id=multilingualcontent
 *
 * @author Ingo Schommer <ingo (at) silverstripe (dot) com>
 * @author Michael Gall <michael (at) wakeless (dot) net>
 * @author Bernat Foj Capell <bernat@silverstripe.com>
 * 
 * @package sapphire
 * @subpackage i18n
 */
class Translatable extends DataObjectDecorator implements PermissionProvider {

	/**
	 * The 'default' language.
	 * @var string
	 */
	protected static $default_locale = 'en_US';
	
	/**
	 * The language in which we are reading dataobjects.
	 *
	 * @var string
	 */
	protected static $current_locale = null;
	
	/**
	 * A cached list of existing tables
	 *
	 * @var mixed
	 */
	protected static $tableList = null;

	/**
	 * An array of fields that can be translated.
	 * @var array
	 */
	protected $translatableFields = null;

	/**
	 * A map of the field values of the original (untranslated) DataObject record
	 * @var array
	 */
	protected $original_values = null;
	
	/**
	 * If this is set to TRUE then {@link augmentSQL()} will automatically add a filter
	 * clause to limit queries to the current {@link get_current_locale()}. This camn be
	 * disabled using {@link disable_locale_filter()}
	 *
	 * @var bool
	 */
	protected static $locale_filter_enabled = true;
	
	/**
	 * @var array All locales in which a translation can be created.
	 * This limits the choice in the CMS language dropdown in the
	 * "Translation" tab, as well as the language dropdown above
	 * the CMS tree. If not set, it will default to showing all
	 * common locales.
	 */
	protected static $allowed_locales = null;
	
	/**
	 * Reset static configuration variables to their default values
	 */
	static function reset() {
		self::enable_locale_filter();
		self::$default_locale = 'en_US';
		self::$current_locale = null;
		self::$allowed_locales = null;
	}
	
	/**
	 * Choose the language the site is currently on.
	 *
	 * If $_GET['locale'] is currently set, then that locale will be used. Otherwise the member preference (if logged
	 * in) or default locale will be used.
	 * 
	 * @todo Re-implement cookie and member option
	 * 
	 * @param $langsAvailable array A numerical array of languages which are valid choices (optional)
	 * @return string Selected language (also saved in $current_locale).
	 */
	static function choose_site_locale($langsAvailable = array()) {
		if(self::$current_locale) {
			return self::$current_locale;
		}

		if((isset($_GET['locale']) && !$langsAvailable) || (isset($_GET['locale']) && in_array($_GET['locale'], $langsAvailable))) {
			// get from GET parameter
			self::set_current_locale($_GET['locale']);
		} else {
			self::set_current_locale(self::default_locale());
		}

		return self::$current_locale; 
	}
		
	/**
	 * Get the current reading language.
	 * This value has to be set before the schema is built with translatable enabled,
	 * any changes after this can cause unintended side-effects.
	 * 
	 * @return string
	 */
	static function default_locale() {
		return self::$default_locale;
	}
	
	/**
	 * Set default language. Please set this value *before* creating
	 * any database records (like pages), as this locale will be attached
	 * to all new records.
	 * 
	 * @param $locale String
	 */
	static function set_default_locale($locale) {
		if($locale && !i18n::validate_locale($locale)) throw new InvalidArgumentException(sprintf('Invalid locale "%s"', $locale));
		
		$localeList = i18n::get_locale_list();
		if(isset($localeList[$locale])) {
			self::$default_locale = $locale;
		} else {
			user_error("Translatable::set_default_locale(): '$locale' is not a valid locale.", E_USER_WARNING);
		}
	}

	/**
	 * Get the current reading language.
	 * If its not chosen, call {@link choose_site_locale()}.
	 * 
	 * @return string
	 */
	static function get_current_locale() {
		return (self::$current_locale) ? self::$current_locale : self::choose_site_locale();
	}
		
	/**
	 * Set the reading language, either namespaced to 'site' (website content)
	 * or 'cms' (management backend). This value is used in {@link augmentSQL()}
	 * to "auto-filter" all SELECT queries by this language.
	 * See {@link disable_locale_filter()} on how to override this behaviour temporarily.
	 * 
	 * @param string $lang New reading language.
	 */
	static function set_current_locale($locale) {
		if($locale && !i18n::validate_locale($locale)) throw new InvalidArgumentException(sprintf('Invalid locale "%s"', $locale));
		
		self::$current_locale = $locale;
	}	
	
	/**
	 * Get a singleton instance of a class in the given language.
	 * @param string $class The name of the class.
	 * @param string $locale  The name of the language.
	 * @param string $filter A filter to be inserted into the WHERE clause.
	 * @param boolean $cache Use caching (default: false)
	 * @param string $orderby A sort expression to be inserted into the ORDER BY clause.
	 * @return DataObject
	 */
	static function get_one_by_locale($class, $locale, $filter = '', $cache = false, $orderby = "") {
		if($locale && !i18n::validate_locale($locale)) throw new InvalidArgumentException(sprintf('Invalid locale "%s"', $locale));
		
		$orig = Translatable::get_current_locale();
		Translatable::set_current_locale($locale);
		$do = DataObject::get_one($class, $filter, $cache, $orderby);
		Translatable::set_current_locale($orig);
		return $do;
	}

	/**
	 * Get all the instances of the given class translated to the given language
	 *
	 * @param string $class The name of the class
	 * @param string $locale  The name of the language
	 * @param string $filter A filter to be inserted into the WHERE clause.
	 * @param string $sort A sort expression to be inserted into the ORDER BY clause.
	 * @param string $join A single join clause.  This can be used for filtering, only 1 instance of each DataObject will be returned.
	 * @param string $limit A limit expression to be inserted into the LIMIT clause.
	 * @param string $containerClass The container class to return the results in.
	 * @param string $having A filter to be inserted into the HAVING clause.
	 * @return mixed The objects matching the conditions.
	 */
	static function get_by_locale($class, $locale, $filter = '', $sort = '', $join = "", $limit = "", $containerClass = "DataObjectSet", $having = "") {
		if($locale && !i18n::validate_locale($locale)) throw new InvalidArgumentException(sprintf('Invalid locale "%s"', $locale));
		
		$oldLang = self::get_current_locale();
		self::set_current_locale($locale);
		$result = DataObject::get($class, $filter, $sort, $join, $limit, $containerClass, $having);
		self::set_current_locale($oldLang);
		return $result;
	}
	
	/**
	 * @return bool
	 */
	public static function locale_filter_enabled() {
		return self::$locale_filter_enabled;
	}
	
	/**
	 * Enables automatic filtering by locale. This is normally called after is has been
	 * disabled using {@link disable_locale_filter()}.
	 */
	public static function enable_locale_filter() {
		self::$locale_filter_enabled = true;
	}
	
	/**
	 * Disables automatic locale filtering in {@link augmentSQL()}. This can be re-enabled
	 * using {@link enable_locale_filter()}.
	 */
	public static function disable_locale_filter() {
		self::$locale_filter_enabled = false;
	}
	
	/**
	 * Gets all translations for this specific page.
	 * Doesn't include the language of the current record.
	 * 
	 * @return array Numeric array of all locales, sorted alphabetically.
	 */
	function getTranslatedLocales() {
		$langs = array();
		
		$baseDataClass = ClassInfo::baseDataClass($this->owner->class); //Base Class
		$translationGroupClass = $baseDataClass . "_translationgroups";
		if($this->owner->hasExtension("Versioned")  && Versioned::current_stage() == "Live") {
			$baseDataClass = $baseDataClass . "_Live";
		}
		
		$translationGroupID = $this->getTranslationGroup();
		if(is_numeric($translationGroupID)) {
			$query = new SQLQuery(
				'DISTINCT "Locale"',
				sprintf(
					'"%s" LEFT JOIN "%s" ON "%s"."OriginalID" = "%s"."ID"',
					$baseDataClass,
					$translationGroupClass,
					$translationGroupClass,
					$baseDataClass
				), // from
				sprintf(
					'"%s"."TranslationGroupID" = %d AND "%s"."Locale" != \'%s\'',
					$translationGroupClass,
					$translationGroupID,
					$baseDataClass,
					$this->owner->Locale
				) // where
			);
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
	 * Gets all locales that a member can access
	 * as defined by {@link $allowed_locales}
	 * and {@link canTranslate()}.
	 * If {@link $allowed_locales} is not set and
	 * the user has the `TRANSLATE_ALL` permission,
	 * the method will return all available locales in the system.
	 * 
	 * @param Member $member
	 * @return array Map of locales
	 */
	function getAllowedLocalesForMember($member) {
		$locales = self::get_allowed_locales();
		if(!$locales) $locales = i18n::get_common_locales();
		if($locales) foreach($locales as $k => $locale) {
			if(!$this->canTranslate($member, $locale)) unset($locales[$k]);
		}

		return $locales;
	}

	/**
	 * Get a list of languages in which a given element has been translated.
	 * 
	 * @deprecated 2.4 Use {@link getTranslations()}
	 *
	 * @param string $class Name of the class of the element
	 * @param int $id ID of the element
	 * @return array List of languages
	 */
	static function get_langs_by_id($class, $id) {
		$do = DataObject::get_by_id($class, $id);
		return ($do ? $do->getTranslatedLocales() : array());
	}

	/**
	 * Enables the multilingual feature
	 *
	 * @deprecated 2.4 Use Object::add_extension('SiteTree', 'Translatable')
	 */
	static function enable() {
		Object::add_extension('SiteTree', 'Translatable');
	}

	/**
	 * Disable the multilingual feature
	 *
	 * @deprecated 2.4 Use Object::remove_extension('SiteTree', 'Translatable')
	 */
	static function disable() {
		Object::remove_extension('SiteTree', 'Translatable');
	}
	
	/**
	 * Check whether multilingual support has been enabled
	 *
	 * @deprecated 2.4 Use Object::has_extension('SiteTree', 'Translatable')
	 * @return boolean True if enabled
	 */
	static function is_enabled() {
		return Object::has_extension('SiteTree', 'Translatable');
	}
	
		
	/**
	 * Construct a new Translatable object.
	 * @var array $translatableFields The different fields of the object that can be translated.
	 * This is currently not implemented, all fields are marked translatable (see {@link setOwner()}).
	 */
	function __construct($translatableFields = null) {
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
		// Caution: There's an additional method for augmentAllChildrenIncludingDeleted()
	
	}
	
	function setOwner($owner, $ownerBaseClass = null) {
		parent::setOwner($owner, $ownerBaseClass);

		// setting translatable fields by inspecting owner - this should really be done in the constructor
		if($this->owner && $this->translatableFields === null) {
			$this->translatableFields = array_merge(
				array_keys($this->owner->inheritedDatabaseFields()),
				array_keys($this->owner->has_many()),
				array_keys($this->owner->many_many())
			);
		}
	}
	
	function extraStatics() {
		return array(
			"db" => array(
				"Locale" => "DBLocale",
				//"TranslationMasterID" => "Int" // optional relation to a "translation master"
			),
			"defaults" => array(
				"Locale" => Translatable::default_locale() // as an overloaded getter as well: getLang()
			)
		);
	}

	/**
	 * Changes any SELECT query thats not filtering on an ID
	 * to limit by the current language defined in {@link get_current_locale()}.
	 * It falls back to "Locale='' OR Lang IS NULL" and assumes that
	 * this implies querying for the default language.
	 * 
	 * Use {@link disable_locale_filter()} to temporarily disable this "auto-filtering".
	 */
	function augmentSQL(SQLQuery &$query) {
		// If the record is saved (and not a singleton), and has a locale,
		// limit the current call to its locale. This fixes a lot of problems
		// with other extensions like Versioned
		$locale = ($this->owner->ID && $this->owner->Locale) ? $this->owner->Locale : Translatable::get_current_locale();
		$baseTable = ClassInfo::baseDataClass($this->owner->class);
		$where = $query->where;
		if(
			$locale
			// unless the filter has been temporarily disabled
			&& self::locale_filter_enabled()
			// DataObject::get_by_id() should work independently of language
			&& !$query->filtersOnID() 
			// the query contains this table
			// @todo Isn't this always the case?!
			&& array_search($baseTable, array_keys($query->from)) !== false 
			// or we're already filtering by Lang (either from an earlier augmentSQL() call or through custom SQL filters)
			&& !preg_match('/("|\'|`)Locale("|\'|`)/', $query->getFilter())
			//&& !$query->filtersOnFK()
		)  {
			$qry = sprintf('"%s"."Locale" = \'%s\'', $baseTable, Convert::raw2sql($locale));
			$query->where[] = $qry; 
		}
	}
	
	/**
	 * Create <table>_translation database table to enable
	 * tracking of "translation groups" in which each related
	 * translation of an object acts as a sibling, rather than
	 * a parent->child relation.
	 */
	function augmentDatabase() {
		$baseDataClass = ClassInfo::baseDataClass($this->owner->class);
		if($this->owner->class != $baseDataClass) return;
		
		$fields = array(
			'OriginalID' => 'Int', 
			'TranslationGroupID' => 'Int', 
		);
		$indexes = array(
			'OriginalID' => true,
			'TranslationGroupID' => true
		);

		// Add new tables if required
		DB::requireTable("{$baseDataClass}_translationgroups", $fields, $indexes);
		
		// Remove 2.2 style tables
		DB::dontRequireTable("{$baseDataClass}_lang");
		if($this->owner->hasExtension('Versioned')) {
			DB::dontRequireTable("{$baseDataClass}_lang_Live");
			DB::dontRequireTable("{$baseDataClass}_lang_versions");
		}
	}
	
	/**
	 * @todo Find more appropriate place to hook into database building
	 */
	function requireDefaultRecords() {
		// @todo This relies on the Locale attribute being on the base data class, and not any subclasses
		if($this->owner->class != ClassInfo::baseDataClass($this->owner->class)) return false;
		
		// Permissions: If a group doesn't have any specific TRANSLATE_<locale> edit rights,
		// but has CMS_ACCESS_CMSMain (general CMS access), then assign TRANSLATE_ALL permissions as a default.
		// Auto-setting permissions based on these intransparent criteria is a bit hacky,
		// but unavoidable until we can determine when a certain permission code was made available first 
		// (see http://open.silverstripe.org/ticket/4940)
		$groups = Permission::get_groups_by_permission(array('CMS_ACCESS_CMSMain','CMS_ACCESS_LeftAndMain','ADMIN'));
		if($groups) foreach($groups as $group) {
			$codes = $group->Permissions()->column('Code');
			$hasTranslationCode = false;
			foreach($codes as $code) {
				if(preg_match('/^TRANSLATE_/', $code)) $hasTranslationCode = true;
			}
			// Only add the code if no more restrictive code exists 
			if(!$hasTranslationCode) Permission::grant($group->ID, 'TRANSLATE_ALL');
		}
		
		// If the Translatable extension was added after the first records were already
		// created in the database, make sure to update the Locale property if
		// if wasn't set before
		$idsWithoutLocale = DB::query(sprintf(
			'SELECT "ID" FROM "%s" WHERE "Locale" IS NULL OR "Locale" = \'\'',
			ClassInfo::baseDataClass($this->owner->class)
		))->column();
		if(!$idsWithoutLocale) return;
		
			if($this->owner->class == 'SiteTree') {
			foreach(array('Stage', 'Live') as $stage) {
				foreach($idsWithoutLocale as $id) {
					$obj = Versioned::get_one_by_stage(
						$this->owner->class, 
						$stage, 
						sprintf('"SiteTree"."ID" = %d', $id)
					);
					if(!$obj) continue;

					$obj->Locale = Translatable::default_locale();
					$obj->writeToStage($stage);
					$obj->addTranslationGroup($obj->ID);
					$obj->destroy();
					unset($obj);
				}
			}
		} else {
			foreach($idsWithoutLocale as $id) {
				$obj = DataObject::get_by_id($this->owner->class, $id);
				if(!$obj) continue;

				$obj->Locale = Translatable::default_locale();
				$obj->write();
				$obj->addTranslationGroup($obj->ID);
				$obj->destroy();
				unset($obj);
			}
		}
		DB::alteration_message(sprintf(
			"Added default locale '%s' to table %s","changed",
			Translatable::default_locale(),
			$this->owner->class
		));
	}
	
	/**
	 * Add a record to a "translation group",
	 * so its relationship to other translations
	 * based off the same object can be determined later on.
	 * See class header for further comments.
	 * 
	 * @param int $originalID Either the primary key of the record this new translation is based on,
	 *  or the primary key of this record, to create a new translation group
	 * @param boolean $overwrite
	 */
	public function addTranslationGroup($originalID, $overwrite = false) {
		if(!$this->owner->exists()) return false;
		
		$baseDataClass = ClassInfo::baseDataClass($this->owner->class);
		$existingGroupID = $this->getTranslationGroup($originalID);
		
		// Remove any existing groups if overwrite flag is set
		if($existingGroupID && $overwrite) {
			$sql = sprintf(
				'DELETE FROM "%s_translationgroups" WHERE "TranslationGroupID" = %d AND "OriginalID" = %d', 
				$baseDataClass, 
				$existingGroupID,
				$this->owner->ID
			);
			DB::query($sql);
			$existingGroupID = null;
		}
		
		// Add to group (only if not in existing group or $overwrite flag is set)
		if(!$existingGroupID) {
			$sql = sprintf(
				'INSERT INTO "%s_translationgroups" ("TranslationGroupID","OriginalID") VALUES (%d,%d)', 
				$baseDataClass, 
				$originalID, 
				$this->owner->ID
			);
			DB::query($sql);
		}
	}
	
	/**
	 * Gets the translation group for the current record.
	 * This ID might equal the record ID, but doesn't have to -
	 * it just points to one "original" record in the list.
	 * 
	 * @return int Numeric ID of the translationgroup in the <classname>_translationgroup table
	 */
	public function getTranslationGroup() {
		if(!$this->owner->exists()) return false;
		
		$baseDataClass = ClassInfo::baseDataClass($this->owner->class);
		return DB::query(
			sprintf('SELECT "TranslationGroupID" FROM "%s_translationgroups" WHERE "OriginalID" = %d', $baseDataClass, $this->owner->ID)
		)->value();
	}
	
	/**
	 * Removes a record from the translation group lookup table.
	 * Makes no assumptions on other records in the group - meaning
	 * if this happens to be the last record assigned to the group,
	 * this group ceases to exist.
	 */
	public function removeTranslationGroup() {
		$baseDataClass = ClassInfo::baseDataClass($this->owner->class);
		DB::query(
			sprintf('DELETE FROM "%s_translationgroups" WHERE "OriginalID" = %d', $baseDataClass, $this->owner->ID)
		);
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

	/**
	 * Note: The bulk of logic is in ModelAsController->getNestedController()
	 * and ContentController->handleRequest()
	 */
	function contentcontrollerInit($controller) {
		$controller->Locale = Translatable::choose_site_locale();
	}
	
	function modelascontrollerInit($controller) {
		//$this->contentcontrollerInit($controller);
	}
	
	function initgetEditForm($controller) {
		$this->contentcontrollerInit($controller);
	}

	/**
	 * Recursively creates translations for parent pages in this language
	 * if they aren't existing already. This is a necessity to make
	 * nested pages accessible in a translated CMS page tree.
	 * It would be more userfriendly to grey out untranslated pages,
	 * but this involves complicated special cases in AllChildrenIncludingDeleted().
	 * 
	 * {@link SiteTree->onBeforeWrite()} will ensure that each translation will get
	 * a unique URL across languages, by means of {@link SiteTree::get_by_link()}
	 * and {@link Translatable->alternateGetByURL()}.
	 */
	function onBeforeWrite() {
		// If language is not set explicitly, set it to current_locale.
		// This might be a bit overzealous in assuming the language
		// of the content, as a "single language" website might be expanded
		// later on. See {@link requireDefaultRecords()} for batch setting
		// of empty Locale columns on each dev/build call.
		if(!$this->owner->Locale) {
			$this->owner->Locale = Translatable::get_current_locale();
		}

		// Specific logic for SiteTree subclasses.
		// If page has untranslated parents, create (unpublished) translations
		// of those as well to avoid having inaccessible children in the sitetree.
		// Caution: This logic is very sensitve to infinite loops when translation status isn't determined properly
		// If a parent for the newly written translation was existing before this
		// onBeforeWrite() call, it will already have been linked correctly through createTranslation()
		if($this->owner->hasField('ParentID') && $this->owner instanceof SiteTree) {
			if(
				!$this->owner->ID 
				&& $this->owner->ParentID 
				&& !$this->owner->Parent()->hasTranslation($this->owner->Locale)
			) {
				$parentTranslation = $this->owner->Parent()->createTranslation($this->owner->Locale);
				$this->owner->ParentID = $parentTranslation->ID;
			}
		}
		
		// Has to be limited to the default locale, the assumption is that the "page type"
		// dropdown is readonly on all translations.
		if($this->owner->ID && $this->owner->Locale == Translatable::default_locale()) {
			$changedFields = $this->owner->getChangedFields();
			if(isset($changedFields['ClassName'])) {
				$this->owner->ClassName = $changedFields['ClassName']['before'];
				$translations = $this->owner->getTranslations();
				$this->owner->ClassName = $changedFields['ClassName']['after'];
				if($translations) foreach($translations as $translation) {
					$translation->setClassName($this->owner->ClassName);
					$translation = $translation->newClassInstance($translation->ClassName);
					$translation->populateDefaults();
					$translation->forceChange();
					$translation->write();
				}
			}
		}		
		
		// see onAfterWrite()
		if(!$this->owner->ID) {
			$this->owner->_TranslatableIsNewRecord = true;
		}
	}
	
	function onAfterWrite() {
		// hacky way to determine if the record was created in the database,
		// or just updated
		if($this->owner->_TranslatableIsNewRecord) {
			// this would kick in for all new records which are NOT
			// created through createTranslation(), meaning they don't
			// have the translation group automatically set.
			$translationGroupID = $this->getTranslationGroup();
			if(!$translationGroupID) $this->addTranslationGroup($this->owner->_TranslationGroupID ? $this->owner->_TranslationGroupID : $this->owner->ID);
			unset($this->owner->_TranslatableIsNewRecord);
			unset($this->owner->_TranslationGroupID);
		}
		
	}
	
	/**
	 * Remove the record from the translation group mapping.
	 */
	function onBeforeDelete() {
		// @todo Coupling to Versioned, we need to avoid removing
		// translation groups if records are just deleted from a stage
		// (="unpublished"). Ideally the translation group tables would
		// be specific to different Versioned changes, making this restriction unnecessary.
		// This will produce orphaned translation group records for SiteTree subclasses.
		if(!$this->owner->hasExtension('Versioned')) {
			$this->removeTranslationGroup();
		}

		parent::onBeforeDelete();
	}
	
	/**
	 * Attempt to get the page for a link in the default language that has been translated.
	 *
	 * @param string $URLSegment
	 * @param int|null $parentID
	 * @return SiteTree
	 */
	public function alternateGetByLink($URLSegment, $parentID) {
		// If the parentID value has come from a translated page, then we need to find the corresponding parentID value
		// in the default Locale.
		if (
			is_int($parentID)
			&& $parentID > 0
			&& ($parent = DataObject::get_by_id('SiteTree', $parentID))
			&& ($parent->isTranslation())
		) {
			$parentID = $parent->getTranslationGroup();
		}
		
		// Find the locale language-independent of the page
		self::disable_locale_filter();
		$default = DataObject::get_one (
			'SiteTree',
			sprintf (
				'"URLSegment" = \'%s\'%s',
				Convert::raw2sql($URLSegment),
				(is_int($parentID) ? " AND \"ParentID\" = $parentID" : null)
			),
			false
		);
		self::enable_locale_filter();
		
		return $default;
	}
	
	//-----------------------------------------------------------------------------------------------//
	
	/**
	 * If the record is not shown in the default language, this method
	 * will try to autoselect a master language which is shown alongside
	 * the normal formfields as a readonly representation.
	 * This gives translators a powerful tool for their translation workflow
	 * without leaving the translated page interface.
	 * Translatable also adds a new tab "Translation" which shows existing
	 * translations, as well as a formaction to create new translations based
	 * on a dropdown with available languages.
	 * 
	 * @todo This is specific to SiteTree and CMSMain
	 * @todo Implement a special "translation mode" which triggers display of the
	 * readonly fields, so you can translation INTO the "default language" while
	 * seeing readonly fields as well.
	 */
	function updateCMSFields(FieldSet &$fields) {
		// Don't apply these modifications for normal DataObjects - they rely on CMSMain logic
		if(!($this->owner instanceof SiteTree)) return;
		
		// used in CMSMain->init() to set language state when reading/writing record
		$fields->push(new HiddenField("Locale", "Locale", $this->owner->Locale) );
		
		// Don't allow translation of virtual pages because of data inconsistencies (see #5000)
		$excludedPageTypes = array('VirtualPage');
		foreach($excludedPageTypes as $excludedPageType) {
			if(is_a($this->owner, $excludedPageType)) return;
		}
		
		$excludeFields = array(
			'ViewerGroups',
			'EditorGroups',
			'CanViewType',
			'CanEditType'
		);

		// if a language other than default language is used, we're in "translation mode",
		// hence have to modify the original fields
		$creating = false;
		$baseClass = $this->owner->class;
		$allFields = $fields->toArray();
		while( ($p = get_parent_class($baseClass)) != "DataObject") $baseClass = $p;

		// try to get the record in "default language"
		$originalRecord = $this->owner->getTranslation(Translatable::default_locale());
		// if no translation in "default language", fall back to first translation
		if(!$originalRecord) {
			$translations = $this->owner->getTranslations();
			$originalRecord = ($translations) ? $translations->First() : null;
		}
		
		$isTranslationMode = $this->owner->Locale != Translatable::default_locale();
		
		// Show a dropdown to create a new translation.
		// This action is possible both when showing the "default language"
		// and a translation. Include the current locale (record might not be saved yet).
		$alreadyTranslatedLocales = $this->getTranslatedLocales();
		$alreadyTranslatedLocales[$this->owner->Locale] = $this->owner->Locale;

		if($originalRecord && $isTranslationMode) {
			$originalLangID = Session::get($this->owner->ID . '_originalLangID');
			
			// Remove parent page dropdown
			$fields->removeByName("ParentType");
			$fields->removeByName("ParentID");
			
			$translatableFieldNames = $this->getTranslatableFields();
			$allDataFields = $fields->dataFields();
			
			$transformation = new Translatable_Transformation($originalRecord);
			
			// iterate through sequential list of all datafields in fieldset
			// (fields are object references, so we can replace them with the translatable CompositeField)
			foreach($allDataFields as $dataField) {
				if($dataField instanceof HiddenField) continue;
				if(in_array($dataField->Name(), $excludeFields)) continue;
				
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
		} 
		
		$fields->addFieldsToTab(
			'Root',
			new Tab('Translations', _t('Translatable.TRANSLATIONS', 'Translations'),
				new HeaderField('CreateTransHeader', _t('Translatable.CREATE', 'Create new translation'), 2),
				$langDropdown = new LanguageDropdownField(
					"NewTransLang", 
					_t('Translatable.NEWLANGUAGE', 'New language'), 
					$alreadyTranslatedLocales,
					'SiteTree',
					'Locale-English',
					$this->owner
				),
				$createButton = new InlineFormAction('createtranslation',_t('Translatable.CREATEBUTTON', 'Create'))
			)
		);
		$createButton->includeDefaultJS(false);

		if($alreadyTranslatedLocales) {
			$fields->addFieldToTab(
				'Root.Translations',
				new HeaderField('ExistingTransHeader', _t('Translatable.EXISTING', 'Existing translations:'), 3)
			);
			$existingTransHTML = '<ul>';
			foreach($alreadyTranslatedLocales as $i => $langCode) {		
				$existingTranslation = $this->owner->getTranslation($langCode);
				if($existingTranslation) {
					$existingTransHTML .= sprintf('<li><a href="%s">%s</a></li>',
						sprintf('admin/show/%d/?locale=%s', $existingTranslation->ID, $langCode),
						i18n::get_locale_name($langCode)
					);
				}
			}
			$existingTransHTML .= '</ul>';
			$fields->addFieldToTab(
				'Root.Translations',
				new LiteralField('existingtrans',$existingTransHTML)
			);
		}
	
		$langDropdown->addExtraClass('languageDropdown');
		$createButton->addExtraClass('createTranslationButton');
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
	 * Gets all related translations for the current object,
	 * excluding itself. See {@link getTranslation()} to retrieve
	 * a single translated object.
	 * 
	 * Getter with $stage parameter is specific to {@link Versioned} extension,
	 * mostly used for {@link SiteTree} subclasses.
	 * 
	 * @param string $locale
	 * @param string $stage 
	 * @return DataObjectSet
	 */
	function getTranslations($locale = null, $stage = null) {
		if($locale && !i18n::validate_locale($locale)) throw new InvalidArgumentException(sprintf('Invalid locale "%s"', $locale));
		
		if($this->owner->exists()) {
			// HACK need to disable language filtering in augmentSQL(), 
			// as we purposely want to get different language
			self::disable_locale_filter();
			
			$translationGroupID = $this->getTranslationGroup();
			
			$baseDataClass = ClassInfo::baseDataClass($this->owner->class);
			$filter = sprintf('"%s_translationgroups"."TranslationGroupID" = %d', $baseDataClass, $translationGroupID);
			if($locale) {
				$filter .= sprintf(' AND "%s"."Locale" = \'%s\'', $baseDataClass, Convert::raw2sql($locale));
			} else {
				// exclude the language of the current owner
				$filter .= sprintf(' AND "%s"."Locale" != \'%s\'', $baseDataClass, $this->owner->Locale);
			}
			$join = sprintf('LEFT JOIN "%s_translationgroups" ON "%s_translationgroups"."OriginalID" = "%s"."ID"',
				$baseDataClass,
				$baseDataClass,
				$baseDataClass
			);
			$currentStage = Versioned::current_stage();
			if($this->owner->hasExtension("Versioned")) {
				if($stage) Versioned::reading_stage($stage);
				$translations = Versioned::get_by_stage(
					$this->owner->class, 
					Versioned::current_stage(), 
					$filter, 
					null, 
					$join
				);
				if($stage) Versioned::reading_stage($currentStage);
			} else {
				$translations = DataObject::get($this->owner->class, $filter, null, $join);
			}

			self::enable_locale_filter();

			return $translations;
		}
	}
	
	/**
	 * Gets an existing translation based on the language code.
	 * Use {@link hasTranslation()} as a quicker alternative to check
	 * for an existing translation without getting the actual object.
	 * 
	 * @param String $locale
	 * @return DataObject Translated object
	 */
	function getTranslation($locale, $stage = null) {
		if($locale && !i18n::validate_locale($locale)) throw new InvalidArgumentException(sprintf('Invalid locale "%s"', $locale));
		
		$translations = $this->getTranslations($locale, $stage);
		return ($translations) ? $translations->First() : null;
	}
	
	/**
	 * Creates a new translation for the owner object of this decorator.
	 * Checks {@link getTranslation()} to return an existing translation
	 * instead of creating a duplicate. Writes the record to the database before
	 * returning it. Use this method if you want the "translation group"
	 * mechanism to work, meaning that an object knows which group of translations
	 * it belongs to. For "original records" which are not created through this
	 * method, the "translation group" is set in {@link onAfterWrite()}.
	 * 
	 * @param string $locale
	 * @return DataObject The translated object
	 */
	function createTranslation($locale) {
		if($locale && !i18n::validate_locale($locale)) throw new InvalidArgumentException(sprintf('Invalid locale "%s"', $locale));
		
		if(!$this->owner->exists()) {
			user_error('Translatable::createTranslation(): Please save your record before creating a translation', E_USER_ERROR);
		}
		
		// permission check
		if(!$this->owner->canTranslate(null, $locale)) {
			throw new Exception(sprintf(
				'Creating a new translation in locale "%s" is not allowed for this user',
				$locale
			));
			return;
		}
		
		$existingTranslation = $this->getTranslation($locale);
		if($existingTranslation) return $existingTranslation;
		
		$class = $this->owner->class;
		$newTranslation = new $class;
		
		// copy all fields from owner (apart from ID)
		$newTranslation->update($this->owner->toMap());
		
		// If the object has Hierarchy extension,
		// check for existing translated parents and assign
		// their ParentID (and overwrite any existing ParentID relations
		// to parents in other language). If no parent translations exist,
		// they are automatically created in onBeforeWrite()
		if($newTranslation->hasField('ParentID')) {
			$origParent = $this->owner->Parent();
			$newTranslationParent = $origParent->getTranslation($locale);
			if($newTranslationParent) $newTranslation->ParentID = $newTranslationParent->ID;
		}
		
		$newTranslation->ID = 0;
		$newTranslation->Locale = $locale;
		
		$originalPage = $this->getTranslation(self::default_locale());
		if ($originalPage) {
			$urlSegment = $originalPage->URLSegment;
		} else {
			$urlSegment = $newTranslation->URLSegment;
		}
		$newTranslation->URLSegment = $urlSegment . '-' . i18n::convert_rfc1766($locale);
		// hacky way to set an existing translation group in onAfterWrite()
		$translationGroupID = $this->getTranslationGroup();
		$newTranslation->_TranslationGroupID = $translationGroupID ? $translationGroupID : $this->owner->ID;
		$newTranslation->write();
		
		return $newTranslation;
	}
	
	/**
	 * Caution: Does not consider the {@link canEdit()} permissions.
	 * 
	 * @param DataObject|int $member
	 * @param string $locale
	 * @return boolean
	 */
	function canTranslate($member = null, $locale) {
		if($locale && !i18n::validate_locale($locale)) throw new InvalidArgumentException(sprintf('Invalid locale "%s"', $locale));
		
		if(!$member || !(is_a($member, 'Member')) || is_numeric($member)) $member = Member::currentUser();

		// check for locale
		$allowedLocale = (
			!is_array(self::get_allowed_locales()) 
			|| in_array($locale, self::get_allowed_locales())
		);

		if(!$allowedLocale) return false;
		
		// By default, anyone who can edit a page can edit the default locale
		if($locale == self::default_locale()) return true;
		
		// check for generic translation permission
		if(Permission::checkMember($member, 'TRANSLATE_ALL')) return true;
		
		// check for locale specific translate permission
		if(!Permission::checkMember($member, 'TRANSLATE_' . $locale)) return false;
		
		return true;
	}
	
	/**
	 * @return boolean
	 */
	function canEdit($member) {
		if(!$this->owner->Locale) return null;
		return $this->owner->canTranslate($member, $this->owner->Locale) ? null : false;
	}
	
	/**
	 * Returns TRUE if the current record has a translation in this language.
	 * Use {@link getTranslation()} to get the actual translated record from
	 * the database.
	 * 
	 * @param string $locale
	 * @return boolean
	 */
	function hasTranslation($locale) {
		if($locale && !i18n::validate_locale($locale)) throw new InvalidArgumentException(sprintf('Invalid locale "%s"', $locale));
		
		return (
			$this->owner->Locale == $locale
			|| array_search($locale, $this->getTranslatedLocales()) !== false
		);
	}
	
	function AllChildrenIncludingDeleted($context = null) {
		$children = $this->owner->doAllChildrenIncludingDeleted($context);
		
		return $children;
	}
	
	/**
	 * Returns <link rel="alternate"> markup for insertion into
	 * a HTML4/XHTML compliant <head> section, listing all available translations
	 * of a page.
	 * 
	 * @see http://www.w3.org/TR/html4/struct/links.html#edef-LINK
	 * @see http://www.w3.org/International/articles/language-tags/
	 * 
	 * @return string HTML
	 */
	function MetaTags(&$tags) {
		$template = '<link rel="alternate" type="text/html" title="%s" hreflang="%s" href="%s" />' . "\n";
		$translations = $this->owner->getTranslations();
		if($translations) foreach($translations as $translation) {
			$tags .= sprintf($template,
				$translation->Title,
				i18n::convert_rfc1766($translation->Locale),
				$translation->Link()
			);
		}
	}
	
	function providePermissions() {
		if(!Object::has_extension('SiteTree', 'Translatable')) return false;
		
		$locales = self::get_allowed_locales();
		
		// Fall back to any locales used in existing translations (see #4939)
		if(!$locales) {
			$locales = DB::query('SELECT "Locale" FROM "SiteTree" GROUP BY "Locale"')->column();
		}
		
		$permissions = array();
		if($locales) foreach($locales as $locale) {
			$localeName = i18n::get_locale_name($locale);
			$permissions['TRANSLATE_' . $locale] = sprintf(
				_t(
					'Translatable.TRANSLATEPERMISSION', 
					'Translate %s', 
					PR_MEDIUM, 
					'Translate pages into a language'
				),
				$localeName
			);
		}
		
		$permissions['TRANSLATE_ALL'] = _t(
			'Translatable.TRANSLATEALLPERMISSION', 
			'Translate into all available languages'
		);
		
		return $permissions;
	}
	
	/**
	 * Get a list of languages with at least one element translated in (including the default language)
	 *
	 * @param string $className Look for languages in elements of this class
	 * @param string $where Optional SQL WHERE statement
	 * @return array Map of languages in the form locale => langName
	 */
	static function get_existing_content_languages($className = 'SiteTree', $where = '') {
		$baseTable = ClassInfo::baseDataClass($className);
		$query = new SQLQuery("Distinct \"Locale\"","\"$baseTable\"",$where, '', "\"Locale\"");
		$dbLangs = $query->execute()->column();
		$langlist = array_merge((array)Translatable::default_locale(), (array)$dbLangs);
		$returnMap = array();
		$allCodes = array_merge(i18n::$all_locales, i18n::$common_locales);
		foreach ($langlist as $langCode) {
			if($langCode && isset($allCodes[$langCode])) {
				$returnMap[$langCode] = (is_array($allCodes[$langCode])) ? $allCodes[$langCode][0] : $allCodes[$langCode];
			}
		}
		return $returnMap;
	}
	
	/**
	 * Get the RelativeLink value for a home page in another locale. This is found by searching for the default home
	 * page in the default language, then returning the link to the translated version (if one exists).
	 *
	 * @return string
	 */
	public static function get_homepage_link_by_locale($locale) {
		$originalLocale = self::get_current_locale();
		
		self::set_current_locale(self::default_locale());
		$original = SiteTree::get_by_link(RootURLController::get_default_homepage_link());
		self::set_current_locale($originalLocale);
		
		if($original) {
			if($translation = $original->getTranslation($locale)) return trim($translation->RelativeLink(true), '/');
		}
	}
	
	/**
	 * @deprecated 2.4 Use {@link Translatable::get_homepage_link_by_locale()}
	 */
	static function get_homepage_urlsegment_by_locale($locale) {
		user_error (
			'Translatable::get_homepage_urlsegment_by_locale() is deprecated, please use get_homepage_link_by_locale()',
			E_USER_NOTICE
		);
		
		return self::get_homepage_link_by_locale($locale);
	}
	
	/**
	 * Define all locales which in which a new translation is allowed.
	 * Checked in {@link canTranslate()}.
	 *
	 * @param array List of allowed locale codes (see {@link i18n::$all_locales}).
	 *  Example: array('de_DE','ja_JP')
	 */
	static function set_allowed_locales($locales) {
		self::$allowed_locales = $locales;
	}
	
	/**
	 * Get all locales which are generally permitted to be translated.
	 * Use {@link canTranslate()} to check if a specific member has permission
	 * to translate a record.
	 * 
	 * @return array
	 */
	static function get_allowed_locales() {
		return self::$allowed_locales;
	}
	
	/**
	 * @deprecated 2.4 Use get_homepage_urlsegment_by_locale()
	 */
	static function get_homepage_urlsegment_by_language($locale) {
		return self::get_homepage_urlsegment_by_locale($locale);
	}
	
	/**
	 * @deprecated 2.4 Use custom check: self::$default_locale == self::get_current_locale()
	 */
	static function is_default_lang() {
		return (self::$default_locale == self::get_current_locale());
	}
	
	/**
	 * @deprecated 2.4 Use set_default_locale()
	 */
	static function set_default_lang($lang) {
		self::set_default_locale(i18n::get_locale_from_lang($lang));
	}
	
	/**
	 * @deprecated 2.4 Use get_default_locale()
	 */
	static function get_default_lang() {
		return i18n::get_lang_from_locale(self::default_locale());
	}
	
	/**
	 * @deprecated 2.4 Use get_current_locale()
	 */
	static function current_lang() {
		return i18n::get_lang_from_locale(self::get_current_locale());
	}
	
	/**
	 * @deprecated 2.4 Use set_current_locale()
	 */
	static function set_reading_lang($lang) {
		self::set_current_locale(i18n::get_locale_from_lang($lang));
	}
	
	/**
	 * @deprecated 2.4 Use get_reading_locale()
	 */
	static function get_reading_lang() {
		return i18n::get_lang_from_locale(self::get_reading_locale());
	}
	
	/**
	 * @deprecated 2.4 Use default_locale()
	 */
	static function default_lang() {
		return i18n::get_lang_from_locale(self::default_locale());
	}
	
	/**
	 * @deprecated 2.4 Use get_by_locale()
	 */
	static function get_by_lang($class, $lang, $filter = '', $sort = '', $join = "", $limit = "", $containerClass = "DataObjectSet", $having = "") {
		return self::get_by_locale($class, i18n::get_locale_from_lang($lang), $filter, $sort, $join, $limit, $containerClass, $having);
	}
	
	/**
	 * @deprecated 2.4 Use get_one_by_locale()
	 */
	static function get_one_by_lang($class, $lang, $filter = '', $cache = false, $orderby = "") {
		return self::get_one_by_locale($class, i18n::get_locale_from_lang($lang), $filter, $cache, $orderby);
	}
	
	/**
	 * Determines if the record has a locale,
	 * and if this locale is different from the "default locale"
	 * set in {@link Translatable::default_locale()}.
	 * Does not look at translation groups to see if the record
	 * is based on another record.
	 * 
	 * @return boolean
	 * @deprecated 2.4
	 */
	function isTranslation() { 
		return ($this->owner->Locale && ($this->owner->Locale != Translatable::default_locale())); 
	}
	
	/**
	 * @deprecated 2.4 Use choose_site_locale()
	 */
	static function choose_site_lang($langsAvail=null) {
		return self::choose_site_locale($langsAvail);
	}
	
	/**
	 * @deprecated 2.4 Use getTranslatedLocales()
	 */
	function getTranslatedLangs() {
		return $this->getTranslatedLocales();
	}

	/**
	 * Return a piece of text to keep DataObject cache keys appropriately specific
	 */
	function cacheKeyComponent() {
		return 'locale-'.self::get_current_locale();
	}
	
	/**
	 * Extends the SiteTree::validURLSegment() method, to do checks appropriate
	 * to Translatable
	 * 
	 * @return bool
     */
	public function augmentValidURLSegment() {
		if (self::locale_filter_enabled()) {
			self::disable_locale_filter();
			$reEnableFilter = true;
		}
		$IDFilter     = ($this->owner->ID) ? "AND \"SiteTree\".\"ID\" <> {$this->owner->ID}" :  null;
		$parentFilter = null;

		if(SiteTree::nested_urls()) {
			if($this->owner->ParentID) {
				$parentFilter = " AND \"SiteTree\".\"ParentID\" = {$this->owner->ParentID}";
			} else {
				$parentFilter = ' AND "SiteTree"."ParentID" = 0';
			}
		}

		$existingPage = DataObject::get_one(
			'SiteTree',
			"\"URLSegment\" = '{$this->owner->URLSegment}' $IDFilter $parentFilter",
			false // disable get_one cache, as this otherwise may pick up results from when locale_filter was on
		);
		if ($reEnableFilter) {
			self::enable_locale_filter();
		}
		return !$existingPage;
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
