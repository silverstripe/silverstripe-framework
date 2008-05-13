<?php

/**
 * @package sapphire
 * @subpackage misc
 */

/**
 * The {Translatable} decorator allows your DataObjects to have versions in different languages,
 * defining which fields are can be translated.
 * 
 * Common language names (e.g. 'en') are used in {Translatable} for
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
 * Caution: Further decorations of DataObject might conflict with this implementation,
 * e.g. when overriding the get_one()-calls (which are already extended by {Translatable}).
 * 
 * @author Bernat Foj Capell <bernat@silverstripe.com>
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
	 * Otherwise it checks the session for a possible stored language. The final option is the member preference.
	 * 
	 * @param $langsAvailable array A numerical array of languages which are valid choices (optional)
	 * @return string Selected language (also saved in $reading_lang).
	 */
	static function choose_site_lang($langsAvailable = null) {
		if(is_array($langsAvailable)) {
			if(isset($_GET['lang']) && in_array($_GET['lang'], $langsAvailable)) {
				self::set_reading_lang($_GET['lang']);
			} elseif(isset($_COOKIE['lang']) && in_array($_COOKIE['lang'], $langsAvailable)) {
				self::set_reading_lang($_COOKIE['lang']);
			} else if(Session::get('currentLang') && in_array(Session::get('currentLang'), $langsAvailable)) {
				self::set_reading_lang(Session::get('currentLang'));
			} else {
				self::set_reading_lang(self::default_lang());		
			}
		} else {
			if(isset($_GET['lang'])) {
				self::set_reading_lang($_GET['lang']);
			} elseif(isset($_COOKIE['lang'])) {
				self::set_reading_lang($_COOKIE['lang']);
			} else if(Session::get('currentLang')) {
				self::set_reading_lang(Session::get('currentLang'));
			} else {
				self::set_reading_lang(self::default_lang());
			}
		}
		
		return self::$reading_lang; 
	}
		
	/**
	 * Get the current reading language.
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
	 * Set the reading language.
	 * @param string $lang New reading language.
	 */
	static function set_reading_lang($lang) {
		Session::set('currentLang',$lang);
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
		$oldLang = self::current_lang();
		self::set_reading_lang($lang);
		$result = DataObject::get_one($class, $filter, $cache, $orderby);
		self::set_reading_lang($oldLang);
		return $result;
	}
	
	/**
	 * Get a singleton instance of a class in the most convenient language (@see choose_site_lang())
	 *
	 * @param string $callerClass The name of the class
	 * @param string $filter A filter to be inserted into the WHERE clause.
	 * @param boolean $cache Use caching (default: false)
	 * @param string $orderby A sort expression to be inserted into the ORDER BY clause.
	 * @return DataObject
	 */
	static function get_one($callerClass, $filter = "", $cache = false, $orderby = "") {
		self::$language_decided = true;
		self::$reading_lang = self::default_lang();
		$record = DataObject::get_one($callerClass, $filter);
		if (!$record) {
			self::$bypass = true;
			$record = DataObject::get_one($callerClass, $filter, $cache, $orderby);
			self::$bypass = false;
			if ($record) self::set_reading_lang($record->Lang);
		} else {
			$langsAvailable = (array)self::get_langs_by_id($callerClass, $record->ID);
			$langsAvailable[] = self::default_lang();
			$lang = self::choose_site_lang($langsAvailable);
			if (isset($lang)) {
				$transrecord = self::get_one_by_lang($callerClass, $lang, "`$callerClass`.ID = $record->ID");
				if ($transrecord) {
					self::set_reading_lang($lang);
					$record = $transrecord;
				}
			}
		}
		return $record;
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
		return self::get_one_by_lang($class,self::default_lang(),"`$baseClass`.ID = $originalLangID");
	}

	/**
	 * Get a list of languages in which a given element has been translated
	 *
	 * @param string $class Name of the class of the element
	 * @param int $id ID of the element
	 * @return array List of languages
	 */
	static function get_langs_by_id($class, $id) {
		$query = new SQLQuery('Lang',"{$class}_lang","(`{$class}_lang`.OriginalLangID =$id)");
		$langs = $query->execute()->column();
		return ($langs) ? array_values($langs) : false;
	}
		
	/**
	 * Writes an object in a certain language. Use this instead of $object->write() if you want to write
	 * an instance in a determinated language independently of the currently set working language
	 *
	 * @param DataObject $object Object to be written
	 * @param string $lang The name of the language
	 */
	static function write(DataObject $object, $lang) {
		$oldLang = self::current_lang();
		self::set_reading_lang($lang);
		$result = $object->write();
		self::set_reading_lang($oldLang);
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

		if(!is_array($translatableFields)) {
			$translatableFields = func_get_args();
		}

		$this->translatableFields = $translatableFields;
	}

	function augmentSQL(SQLQuery &$query) {
		if (! $this->stat('enabled')) return false;
		if((($lang = self::current_lang()) && !self::is_default_lang()) || self::$bypass) {
			foreach($query->from as $table => $dummy) {
				if(!isset($baseTable)) {
					$baseTable = $table;
				}
				
				if (self::table_exists("{$table}_lang")) {
					$query->renameTable($table, $table . '_lang');
					if (stripos($query->sql(),'.ID')) {
						// Every reference to ID is now OriginalLangID
						$query->replaceText(".ID",".OriginalLangID");
						$query->where = str_replace("`ID`", "`OriginalLangID`",$query->where);
						$query->select[] = "`{$baseTable}_lang`.OriginalLangID AS ID";
					}
					if ($query->where) foreach ($query->where as $i => $wherecl) {
						if (substr($wherecl,0,4) == 'ID =')
							// Another reference to ID to be changed 
							$query->where[$i] = str_replace('ID =','OriginalLangID =',$wherecl);
						else {
							$parts = explode(' AND ',$wherecl);
							foreach ($parts as $j => $part) {
								// Divide this clause between the left ($innerparts[1]) and right($innerparts[2]) part of the condition
								ereg('(`?[[:alnum:]_-]*`?\.?`?[[:alnum:]_-]*`?)(.*)', $part, $innerparts);
								if (strpos($innerparts[1],'.') === false)
									//it may be ambiguous, so sometimes we will need to add the table
									$parts[$j] = ($this->isInAugmentedTable($innerparts[1], $table) ? "`{$table}_lang`." : "")."$part";
								else {
									/* if the table has been specified we have to determine if the original (without _lang) name has to be used
									 * because we don't have the queried field in the augmented table (which usually means
									 * that is not a translatable field)
									 */
									$clauseparts = explode('.',$innerparts[1]);
									$originalTable = str_replace('`','',str_replace('_lang','',$clauseparts[0]));
									$parts[$j] = ($this->isInAugmentedTable($clauseparts[1], $originalTable) ? "`{$originalTable}_lang`" : "`$originalTable`") 
												  . ".{$clauseparts[1]}{$innerparts[2]}";
								}
							}
							$query->where[$i] = implode(' AND ',$parts);
						}
					}
					
					if($table != $baseTable) {
						$query->from["{$table}_lang"] = $query->from[$table];
					} else {
						// _lang is now the base table (the first one)
						$query->from = array("{$table}_lang" => $query->from[$table]) + $query->from;
					}
					
					// unless we are bypassing this query, add the language filter
					if (!self::$bypass) $query->where[] = "`{$table}_lang`.Lang = '$lang'";
					
					// unless this is a deletion, the query is applied to the joined table
					if (!$query->delete) {
						$query->from[$table] = "INNER JOIN `$table`".
													" ON `{$table}_lang`.OriginalLangID = `$table`.ID";
						/* if we are selecting fields (not doing counts for example) we need to select everything from
						 * the original table (was renamed to _lang) since some fields that we require may be there
						 */
						if ($query->select[0][0] == '`') $query->select = array_merge(array("`$table`.*"),$query->select);
					} else unset($query->from[$table]);
				} else {
					$query->from[$table] = str_replace("`{$table}`.OriginalLangID","`{$table}`.ID",$query->from[$table]);
				}
			}
		}
	}
		
	/**
	 * Check whether a WHERE clause should be applied to the augmented table
	 *
	 * @param string $clause Where clause that need to know if can be applied to the augmented (suffixed) table
	 * @param string $table Name of the non-augmented table
	 * @return boolean True if the clause can be applied to the augmented table
	 */
	function isInAugmentedTable($clause, $table) {
		$clause = str_replace('`','',$clause);
		$table = str_replace('_lang','',$table);
		if (strpos($table,'_') !== false) return false;
		$field = ereg_replace('[[:blank:]]*([[:alnum:]]*).*','\\1',$clause);
		$field = trim($field);
		$allFields = $this->allFieldsInTable($table);
		return (array_search($field,$allFields) !== false);
	}
	
	
	/**
	 * Determine if the DataObject has any own translatable field (not inherited).
	 * @return boolean
	 */
	function hasOwnTranslatableFields() {
		$ownFields = $this->owner->stat('db');
		if ($ownFields == singleton($this->owner->parentClass())->stat('db'))return false;
		foreach ((array)$this->translatableFields as $translatableField) {
			if (isset($ownFields[$translatableField])) return true;
		}
		return false;
	}
	
	/**
	 * Determine if a table needs Versioned support
	 * This is called at db/build time
	 *
	 * @param string $table Table name
	 * @return boolean
	 */
	function isVersionedTable($table) {
		// Every _lang table wants Versioned support
		return ($this->owner->databaseFields() && $this->hasOwnTranslatableFields());
	}

	function augmentDatabase() {
		if (! $this->stat('enabled')) return false;
		self::set_reading_lang(self::default_lang());
		$table = $this->owner->class;

		if(($fields = $this->owner->databaseFields()) && $this->hasOwnTranslatableFields()) {
			//Calculate the required fields
			foreach ($fields as $field => $type) {
				if (array_search($field,$this->translatableFields) === false) unset($fields[$field]);
			}
			$metaFields = array_diff((array)$this->owner->databaseFields(), (array)$this->owner->customDatabaseFields());
			$indexes = $this->owner->databaseIndexes();
						
			$langFields = array_merge(
				array(
					"Lang" => "Varchar(12)",
					"OriginalLangID" => "Int"
				),
				$fields,
				$metaFields
			);
			
			foreach ($indexes as $index => $type) {
				if (true === $type && array_search($index,$langFields) === false) unset($indexes[$index]);
			}

			$langIndexes = array_merge(
				array(
					'OriginalLangID_Lang' => '(OriginalLangID, Lang)',
					'OriginalLangID' => true,
					'Lang' => true,
				),
				(array)$indexes
			);
			
			// Create table for translated instances			
			DB::requireTable("{$table}_lang", $langFields, $langIndexes);
			
		} else {
			DB::dontRequireTable("{$table}_lang");
		}
	}
	

	/**
	 * Augment a write-record request.
	 * @param SQLQuery $manipulation Query to augment.
	 */
	function augmentWrite(&$manipulation) { 
		if (! $this->stat('enabled')) return false;
		if(($lang = self::current_lang()) && !self::is_default_lang()) {
			$tables = array_keys($manipulation);
			foreach($tables as $table) {
				if (self::table_exists("{$table}_lang")) {
					$manipulation["{$table}_lang"] = $manipulation[$table];
					if ($manipulation[$table]['command'] == 'insert') {
						$fakeID = $this->owner->ID;
						// In an insert we've to populate our fields and generate a new id (since the passed one it's relative to $table)
						$SessionOrigID = Session::get($this->owner->ID.'_originalLangID');
						$manipulation["{$table}_lang"]['fields']['OriginalLangID'] = $this->owner->ID = 
							( $SessionOrigID ? $SessionOrigID : self::$creatingFromID);
						$manipulation["{$table}_lang"]['RecordID'] = $manipulation["{$table}_lang"]['fields']['OriginalLangID'];
						// populate lang field
						$manipulation["{$table}_lang"]['fields']['Lang'] = "'$lang'" ;
						// get a valid id, pre-inserting
						DB::query("INSERT INTO {$table}_lang SET Created = NOW(), Lang = '$lang'");
						$manipulation["{$table}_lang"]['id'] = $manipulation["{$table}_lang"]['fields']['ID'] = DB::getGeneratedID("{$table}_lang");
						$manipulation["{$table}_lang"]['command'] = 'update';
						// we don't have to insert anything in $table if we are inserting in $table_lang
						unset($manipulation[$table]);
						// now dataobjects may create a record before the real write in the base table, so we have to delete it - 20/08/2007
						if (is_numeric($fakeID)) DB::query("DELETE FROM $table WHERE ID=$fakeID");
					}
					else {
						if (!isset($manipulation[$table]['fields']['OriginalLangID'])) {
							// for those updates that may become inserts populate these fields
							$manipulation["{$table}_lang"]['fields']['OriginalLangID'] = $this->owner->ID;
							$manipulation["{$table}_lang"]['fields']['Lang'] = "'$lang'";
						}
						$id = $manipulation["{$table}_lang"]['id'];
						if(!$id) user_error("Couldn't find ID in manipulation", E_USER_ERROR);
						if (isset($manipulation["{$table}_lang"]['where'])) {
							$manipulation["{$table}_lang"]['where'] .= "AND (Lang = '$lang') AND (OriginalLangID = $id)";
						} else {
							$manipulation["{$table}_lang"]['where'] = "(Lang = '$lang') AND (OriginalLangID = $id)";
						}
						$realID = DB::query("SELECT ID FROM {$table}_lang WHERE (OriginalLangID = $id) AND (Lang = '$lang') LIMIT 1")->value();
						$manipulation["{$table}_lang"]['id'] = $realID;
						$manipulation["{$table}_lang"]['RecordID'] = $manipulation["{$table}_lang"]['fields']['OriginalLangID'];
						// we could be updating non-translatable fields at the same time, so these will remain
						foreach ($manipulation[$table]['fields'] as $field => $dummy) {
							if ($this->isInAugmentedTable($field, $table) ) unset($manipulation[$table]['fields'][$field]);
						}
						if (count($manipulation[$table]['fields']) == 0) unset($manipulation[$table]);
					}
					foreach ($manipulation["{$table}_lang"]['fields'] as $field => $dummy) {
						if (! $this->isInAugmentedTable($field, $table) ) unset($manipulation["{$table}_lang"]['fields'][$field]);
					}
				}
			}
		}
 	}

	//-----------------------------------------------------------------------------------------------//
	
	/**
	 * Change the member dialog in the CMS
	 *
	 * This method updates the forms in the cms to allow the translations for 
	 * the defined translatable fields.
	 */
	function updateCMSFields(FieldSet &$fields) {
		if (! $this->stat('enabled')) return false;
		$creating = false;
		$baseClass = $this->owner->class;
		while( ($p = get_parent_class($baseClass)) != "DataObject") $baseClass = $p;
		$allFields = $this->owner->getAllFields();
		if(!self::is_default_lang()) {
			// Get the original version record, to show the original values
			if (!is_numeric($allFields['ID'])) {
				$originalLangID = Session::get($this->owner->ID . '_originalLangID');
				$creating = true;
			} else {
				$originalLangID = $allFields['ID'];
			}
			$originalRecord = self::get_one_by_lang(
					$this->owner->class, 
					self::$default_lang, 
					"`$baseClass`.ID = ".$originalLangID
			);
			$this->original_values = $originalRecord->getAllFields();
			$alltasks = array( 'dup' => array());
			foreach($fields as $field) {
				if ($field->isComposite()) {
					$innertasks = $this->duplicateOrReplaceFields($field->FieldSet());
					// more efficient and safe than array_merge_recursive
					$alltasks['dup'] = array_merge($alltasks['dup'],$innertasks['dup']);
				} 
			}
			foreach ($alltasks['dup'] as $fieldname => $newfield) {
				// Duplicate the field
				$fields->replaceField($fieldname,$newfield);
			}
		} else {
			$alreadyTranslatedLangs = null;
			if (is_numeric($allFields['ID'])) {
				$alreadyTranslatedLangs = self::get_langs_by_id($baseClass,$allFields['ID']);
			}
			if (!$alreadyTranslatedLangs) $alreadyTranslatedLangs = array();
			foreach ($alreadyTranslatedLangs as $i => $langCode) {
				$alreadyTranslatedLangs[$i] = i18n::get_language_name($langCode);
			}
			$fields->addFieldsToTab(
				'Root',
				new Tab(_t('Translatable.TRANSLATIONS', 'Translations'),
					new HeaderField(_t('Translatable.CREATE', 'Create new translation'), 2),
					$langDropdown = new LanguageDropdownField("NewTransLang", _t('Translatable.NEWLANGUAGE', 'New language'), $alreadyTranslatedLangs),
					$createButton = new InlineFormAction('createtranslation',_t('Translatable.CREATEBUTTON', 'Create'))
				)
			);
			if (count($alreadyTranslatedLangs)) {
				$fields->addFieldsToTab(
					'Root.Translations',
					new FieldSet(
						new HeaderField(_t('Translatable.EXISTING', 'Existing translations:'), 3),
						new LiteralField('existingtrans',implode(', ',$alreadyTranslatedLangs))
					)
				);
			}
			$langDropdown->addExtraClass('languageDropdown');
			$createButton->addExtraClass('createTranslationButton');
			$createButton->includeDefaultJS(false);
		}
	}

	protected function duplicateOrReplaceFields(&$fields) {
		$tasks = array(
			'dup' => array(),
		);
		foreach($fields as $field) {
			if ($field->isComposite()) {
				$innertasks = $this->duplicateOrReplaceFields($field->FieldSet());
				$tasks['dup'] = array_merge($tasks['dup'],$innertasks['dup']);
			}
			else if(($fieldname = $field->Name()) && array_key_exists($fieldname,$this->original_values)) {
				// Get a copy of the original field to show the untranslated value
				if(is_subclass_of($field->class,'TextareaField')) {
					$nonEditableField = new ToggleField($fieldname,$field->Title(),'','+','-');
					$nonEditableField->labelMore = '+';
					$nonEditableField->labelLess = '-';
				} else {
					$nonEditableField = $field->performDisabledTransformation();
				} 

				$nonEditableField_holder = new CompositeField($nonEditableField);
				$nonEditableField_holder->setName($fieldname.'_holder');
				$nonEditableField_holder->addExtraClass('originallang_holder');
				$nonEditableField->setValue($this->original_values[$fieldname]);
				$nonEditableField->setName($fieldname.'_original');
				$nonEditableField->addExtraClass('originallang');
				if (array_search($fieldname,$this->translatableFields) !== false) {
					// Duplicate the field
					if ($field->Title()) $nonEditableField->setTitle('Original');
					$nonEditableField_holder->insertBeforeRecursive($field, $fieldname.'_original');				
					$tasks['dup'][$fieldname] = $nonEditableField_holder;
				}
			}
		}
		return $tasks;
	}
	
	/**
	 * Get a list of fields from the tables created by this extension
	 *
	 * @param string $table Name of the table
	 * @return array Map where the keys are db, indexes and the values are the table fields
	 */
	function fieldsInExtraTables($table){

		if(($fields = $this->owner->databaseFields()) && $this->hasOwnTranslatableFields()) {
			//Calculate the required fields
			foreach ($fields as $field => $type) {
				if (array_search($field,$this->translatableFields) === false) unset($fields[$field]);
			}
			$metaFields = array_diff((array)$this->owner->databaseFields(), (array)$this->owner->customDatabaseFields());
			$indexes = $this->owner->databaseIndexes();
						
			$langFields = array_merge(
				array(
					"Lang" => "Varchar(12)",
					"OriginalLangID" => "Int"
				),
				$fields,
				$metaFields
			);
			
			foreach ($indexes as $index => $type) {
				if (true === $type && array_search($index,$langFields) === false) unset($indexes[$index]);
			}
			
			return array('db' => $langFields, 'indexes' => $indexes);
		}
	}
			
	/**
	 * Get a list of fields in the {$table}_lang table
	 *
	 * @param string $table Table name
	 * @return array
	 */
	function allFieldsInTable($table){

		$fields = singleton($table)->databaseFields();
		//Calculate the required fields
		foreach ($fields as $field => $type) {
			if (array_search($field,$this->translatableFields) === false) unset($fields[$field]);
		}
		$metaFields = array_diff((array)singleton('DataObject')->databaseFields(), (array)$this->owner->customDatabaseFields());
					
		$langFields = array_merge(
			array(
				"ID",
				"LastEdited",
				"Created",
				"ClassName",
				"Version",
				"WasPublished",
				"Lang",
				"OriginalLangID"
			),
			$this->translatableFields,
			array_keys($fields),
			array_keys($metaFields)
		);
		return $langFields;
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
	
	/**
	 * Extends $table with a suffix if required
	 *
	 * @param string $table Name of the table
	 * @return string Extended table name
	 */
	function extendWithSuffix($table) {
		if((($lang = self::current_lang()) && !self::is_default_lang())) {
			if (self::table_exists("{$table}_lang")) return $table.'_lang';
		}
		return $table;
	}
		
}
?>
