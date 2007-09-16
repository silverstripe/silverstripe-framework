<?php

/**
 * The Translatable decorator allows your DataObjects to have versions in different languages,
 * defining which fields are can be translated.
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
		if (!Translatable::$tableList) Translatable::$tableList = DB::tableList();
		return isset(Translatable::$tableList[strtolower($table)]);
	}
	
	/**
	 * Choose the language the site is currently on.
	 * If $_GET['lang'] is set, then it will use that language, and store it in the session.
	 * Otherwise it checks the session for a possible stored language. The final option is the member preference.
	 */
	static function choose_site_lang() {
		if(isset($_GET['lang'])) {
			$_GET['lang'] = ucfirst(strtolower($_GET['lang']));
			Translatable::set_reading_lang($_GET['lang']);
		}
		else if($lang = Session::get('currentLang')) {
			Translatable::set_reading_lang($lang);
		}
		else if (($member = Member::currentUser()) && ($lang = $member->Lang)) {
			Translatable::set_reading_lang($lang);			
		}
		Translatable::$language_decided = true; 
	}
		
	/**
	 * Get the current reading language.
	 * @return string
	 */
	static function default_lang() {
		return Translatable::$default_lang;
	}

	/**
	 * Check whether the default and current reading language are the same.
	 * @return boolean Return true if both default and reading language are the same.
	 */
	static function is_default_lang() {
		return (!Translatable::current_lang() || Translatable::$default_lang == Translatable::current_lang());
	}

	/**
	 * Get the current reading language.
	 * @return string
	 */
	static function current_lang() {
		if (!Translatable::$language_decided) Translatable::choose_site_lang();
		return Translatable::$reading_lang;
	}
		
	/**
	 * Set the reading language.
	 * @param string $lang New reading language.
	 */
	static function set_reading_lang($lang) {
		Session::set('currentLang',$lang);
		Translatable::$reading_lang = $lang;
	}	
	
	/**
	 * Get a singleton instance of a class in the given language.
	 * @param string $class The name of the class.
	 * @param string $lang  The name of the language.
	 * @param string $filter A filter to be inserted into the WHERE clause.
	 * @return DataObject
	 */
	static function get_one_by_lang($class, $lang, $filter = '') {
		$oldLang = Translatable::current_lang();
		Translatable::set_reading_lang($lang);
		$result = DataObject::get_one($class, $filter, false);
		Translatable::set_reading_lang($oldLang);
		return $result;
	}
	
	/**
	 * Get a singleton instance of a class in the most convenient language (@see choose_site_lang())
	 *
	 * @param string $callerClass The name of the class
	 * @param string $filter A filter to be inserted into the WHERE clause.
	 * @return DataObject
	 */
	static function get_one($callerClass, $filter = "") {
		Translatable::$language_decided = true;Translatable::$reading_lang = Translatable::default_lang();
		$record = DataObject::get_one($callerClass, $filter);
		if (!$record) {
			Translatable::$bypass = true;
			$record = DataObject::get_one($callerClass, $filter, false);
			Translatable::$bypass = false;
			if ($record) Translatable::set_reading_lang($record->Lang);
		} else {
			$langsAvailable = (array)Translatable::get_langs_by_id($callerClass, $record->ID);
			$langsAvailable[] = Translatable::default_lang();
			if(isset($_GET['lang']) && array_search(ucfirst(strtolower($_GET['lang'])),$langsAvailable) !== false) {
				$lang = ucfirst(strtolower($_GET['lang']));
			} else if(($possible = Session::get('currentLang')) && array_search($possible,$langsAvailable)) {
				$lang = $possible;
			} else if (($member = Member::currentUser()) && ($possible = $member->PreferredLang)) {
				$lang = $possible;
			}
			if (isset($lang)) {
				$transrecord = Translatable::get_one_by_lang($callerClass, $lang, "`$callerClass`.ID = $record->ID");
				if ($transrecord) {
					Translatable::set_reading_lang($lang);
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
	 * @return mixed The objects matching the conditions.
	 */
	static function get_by_lang($class, $lang, $filter = '', $sort = '') {
		$oldLang = Translatable::current_lang();
		Translatable::set_reading_lang($lang);
		$result = DataObject::get($class, $filter, $sort);
		Translatable::set_reading_lang($oldLang);
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
		return Translatable::get_one_by_lang($class,Translatable::default_lang(),"`$baseClass`.ID = $originalLangID");
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
		return $query->execute()->column();
	}
		
	/**
	 * Writes an object in a certain language. Use this instead of $object->write() if you want to write
	 * an instance in a determinated language independently of the currently set working language
	 *
	 * @param DataObject $object Object to be written
	 * @param string $lang The name of the language
	 */
	static function write(DataObject $object, $lang) {
		$oldLang = Translatable::current_lang();
		Translatable::set_reading_lang($lang);
		$result = $object->write();
		Translatable::set_reading_lang($oldLang);
	}

	/**
	 * Enables the multilingual feature
	 *
	 */
	static function enable() {
		Translatable::$enabled = true;
	}
	
	/**
	 * Check whether multilingual support has been enabled
	 *
	 * @return boolean True if enabled
	 */
	static function is_enabled() {
		return Translatable::$enabled;
	}
	
	/**
	 * When creating, set the original ID value
	 *
	 * @param int $id
	 */
	static function creating_from($id) {
		Translatable::$creatingFromID = $id;
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
		if((($lang = Translatable::current_lang()) && !Translatable::is_default_lang()) || Translatable::$bypass) {
			foreach($query->from as $table => $dummy) {
				if(!isset($baseTable)) {
					$baseTable = $table;
				}
				
				if (Translatable::table_exists("{$table}_lang")) {
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
								if (strpos($part,'.') === false)
									//it may be ambiguous, so sometimes we will need to add the table
									$parts[$j] = ($this->isInAugmentedTable($part, $table) ? "`{$table}_lang`." : "")."$part";
								else {
									/* if the table has been specified we have to determine if the original (without _lang) name has to be used
									 * because we don't have the queried field in the augmented table (which usually means
									 * that is not a translatable field)
									 */
									$clauseparts = explode('.',$part);
									$originalTable = str_replace('`','',str_replace('_lang','',$clauseparts[0]));
									$parts[$j] = ($this->isInAugmentedTable($clauseparts[1], $originalTable) ? "`{$originalTable}_lang`" : "`$originalTable`") 
												  . ".{$clauseparts[1]}";
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
					if (!Translatable::$bypass) $query->where[] = "`{$table}_lang`.Lang = '$lang'";
					
					// unless this is a deletion, the query is applied to the joined table
					if (!$query->delete) {
						$query->from[$table] = "INNER JOIN `$table`".
													" ON `{$table}_lang`.OriginalLangID = `$table`.ID";
						/* if we are selecting fields (not doing counts for example) we need to select everything from
						 * the original table (was renamed to _lang) since some fields that we require may be there
						 */
						if ($query->select[0][0] == '`') $query->select = array_merge(array("`$table`.*"),$query->select);
					} else unset($query->from[$table]);//var_dump($query);echo'<br><br>';
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
	function hasOwnFields() {
		$ownFields = $this->owner->stat('db');
		if ($ownFields == singleton($this->owner->parentClass())->stat('db'))return false;
		foreach ((array)$this->translatableFields as $translatableField) {
			if (isset($ownFields[$translatableField])) return true;
		}
		return false;
	}
	
	function augmentDatabase() {
		if (! $this->stat('enabled')) return false;
		Translatable::set_reading_lang(Translatable::default_lang());
		$table = $this->owner->class;

		if(($fields = $this->owner->databaseFields()) && $this->hasOwnFields()) {
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
			
			// Create table for translated instances			
			DB::requireTable("{$table}_lang", $langFields, $indexes);
			
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
		if(($lang = Translatable::current_lang()) && !Translatable::is_default_lang()) {
			$tables = array_keys($manipulation);
			foreach($tables as $table) {
				if (Translatable::table_exists("{$table}_lang")) {
					$manipulation["{$table}_lang"] = $manipulation[$table];
					if ($manipulation[$table]['command'] == 'insert') {
						$fakeID = $this->owner->ID;
						// In an insert we've to populate our fields and generate a new id (since the passed one it's relative to $table)
						$SessionOrigID = Session::get($this->owner->ID.'_originalLangID');
						$manipulation["{$table}_lang"]['fields']['OriginalLangID'] = $this->owner->ID = 
							( $SessionOrigID ? $SessionOrigID : Translatable::$creatingFromID);
						$manipulation["{$table}_lang"]['fields']['Lang'] = "'$lang'" ;
						//$manipulation["{$table}_lang"]['id'] = $manipulation["{$table}_lang"]['fields']['ID'] = DB::getNextID("{$table}_lang");
						$manipulation["{$table}_lang"]['RecordID'] = $manipulation["{$table}_lang"]['fields']['OriginalLangID'];
						// we don't have to insert anything in $table if we are inserting in $table_lang
						unset($manipulation[$table]);
						// now dataobjects create a record before the real write in the base table, so we have to delete it - 20/08/2007
						DB::query("DELETE FROM $table WHERE ID=$fakeID");
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
		if(!Translatable::is_default_lang()) {
			// Get the original version record, to show the original values
			if (!is_numeric($allFields['ID'])) {
				$originalLangID = Session::get($this->owner->ID . '_originalLangID');
				$creating = true;
			} else {
				$originalLangID = $allFields['ID'];
			}
			$originalRecord = Translatable::get_one_by_lang(
					$this->owner->class, 
					Translatable::$default_lang, 
					"`$baseClass`.ID = ".$originalLangID
			);
			$this->original_values = $originalRecord->getAllFields();
			$alltasks = array( 'dup' => array());
			$field = $fields->current();
			do {
				if ($field->isComposite()) {
					$innertasks = $this->duplicateOrReplaceFields($field->FieldSet());
					// more efficient and safe than array_merge_recursive
					$alltasks['dup'] = array_merge($alltasks['dup'],$innertasks['dup']);
				} 
			} while ($field = $fields->next());
			foreach ($alltasks['dup'] as $fieldname => $newfield) {
				// Duplicate the field
				$fields->replaceField($fieldname,$newfield);
			}
		} else {
			$alreadyTranslatedLangs = null;
			if (is_numeric($allFields['ID'])) {
				$alreadyTranslatedLangs = Translatable::get_langs_by_id($baseClass,$allFields['ID']);
			}
			if (!$alreadyTranslatedLangs) $alreadyTranslatedLangs = array();
			foreach ($alreadyTranslatedLangs as $i => $langCode) {
				$alreadyTranslatedLangs[$i] = i18n::get_language_name($langCode);
			}
			$fields->addFieldsToTab(
				'Root',
				new Tab("Translations",
					new HeaderField("Create new translation", 2),
					$langDropdown = new LanguageDropdownField("NewTransLang", "New language", $alreadyTranslatedLangs),
					$createButton = new InlineFormAction('createtranslation',"Create")
				)
			);
			if (count($alreadyTranslatedLangs)) {
				$fields->addFieldsToTab(
					'Root.Translations',
					new FieldSet(
						new HeaderField("Existing translations:", 3),
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
		foreach ($fields as $field) {
			if ($field->isComposite()) {
				$innertasks = $this->duplicateOrReplaceFields($field->FieldSet());
				$tasks['dup'] = array_merge($tasks['dup'],$innertasks['dup']);
			}
			else if (($fieldname = $field->Name()) && array_key_exists($fieldname,$this->original_values)) {
				// Get a copy of the original field to show the untranslated value
				if (is_subclass_of($field->class,'TextareaField')) $nonEditableField = new MoreLessField($fieldname,$field->Title(),'','+','-');
				else $nonEditableField = $field->performDisabledTransformation();

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

		if(($fields = $this->owner->databaseFields()) && $this->hasOwnFields()) {
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
		if((($lang = Translatable::current_lang()) && !Translatable::is_default_lang())) {
			if (Translatable::table_exists("{$table}_lang")) return $table.'_lang';
		}
		return $table;
	}
		
}
