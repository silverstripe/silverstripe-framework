<?php

/**
 * Sitewide configuration
 *
 * @author Tom Rix
 * @package cms
 */
class SiteConfig extends DataObject {
	static $db = array(
		"Title" => "Varchar(255)",
		"Tagline" => "Varchar(255)",
		"CanViewType" => "Enum('Anyone, LoggedInUsers, OnlyTheseUsers', 'Anyone')",
		"CanEditType" => "Enum('LoggedInUsers, OnlyTheseUsers', 'LoggedInUsers')",
		"CanCreateTopLevelType" => "Enum('LoggedInUsers, OnlyTheseUsers', 'LoggedInUsers')",
	);
	
	static $many_many = array(
		"ViewerGroups" => "Group",
		"EditorGroups" => "Group",
		"CreateTopLevelGroups" => "Group"
	);
	
	/**
	 * Get the fields that are sent to the CMS. In
	 * your decorators: updateEditFormFields(&$fields)
	 *
	 * @return Fieldset
	 */
	function getFormFields() {
		$fields = new FieldSet(
			new TabSet("Root",
				new Tab('Main',
					$titleField = new TextField("Title", _t('SiteConfig.SITETITLE', "Site title")),
					$taglineField = new TextField("Tagline", _t('SiteConfig.SITETAGLINE', "Site Tagline/Slogan"))
				),
				new Tab('Access',
					new HeaderField('WhoCanViewHeader', _t('SiteConfig.VIEWHEADER', "Who can view pages on this site?"), 2),
					$viewersOptionsField = new OptionsetField("CanViewType"),
					$viewerGroupsField = new TreeMultiselectField("ViewerGroups", _t('SiteTree.VIEWERGROUPS', "Viewer Groups")),
					new HeaderField('WhoCanEditHeader', _t('SiteConfig.EDITHEADER', "Who can edit pages on this site?"), 2),
					$editorsOptionsField = new OptionsetField("CanEditType"),
					$editorGroupsField = new TreeMultiselectField("EditorGroups", _t('SiteTree.EDITORGROUPS', "Editor Groups")),
					new HeaderField('WhoCanCreateTopLevelHeader', _t('SiteConfig.TOPLEVELCREATE', "Who can create pages in the root of the site?"), 2),
					$topLevelCreatorsOptionsField = new OptionsetField("CanCreateTopLevelType"),
					$topLevelCreatorsGroupsField = new TreeMultiselectField("CreateTopLevelGroups", _t('SiteTree.TOPLEVELCREATORGROUPS', "Top level creators"))
				)
			)
		);
		
		$viewersOptionsSource = array();
		$viewersOptionsSource["Anyone"] = _t('SiteTree.ACCESSANYONE', "Anyone");
		$viewersOptionsSource["LoggedInUsers"] = _t('SiteTree.ACCESSLOGGEDIN', "Logged-in users");
		$viewersOptionsSource["OnlyTheseUsers"] = _t('SiteTree.ACCESSONLYTHESE', "Only these people (choose from list)");
		$viewersOptionsField->setSource($viewersOptionsSource);
		
		$editorsOptionsSource = array();
		$editorsOptionsSource["LoggedInUsers"] = _t('SiteTree.EDITANYONE', "Anyone who can log-in to the CMS");
		$editorsOptionsSource["OnlyTheseUsers"] = _t('SiteTree.EDITONLYTHESE', "Only these people (choose from list)");
		$editorsOptionsField->setSource($editorsOptionsSource);
		
		$topLevelCreatorsOptionsField->setSource($editorsOptionsSource);

		if (!Permission::check('EDIT_SITECONFIG')) {
			$fields->makeFieldReadonly($viewersOptionsField);
			$fields->makeFieldReadonly($viewerGroupsField);
			$fields->makeFieldReadonly($editorsOptionsField);
			$fields->makeFieldReadonly($editorGroupsField);
			$fields->makeFieldReadonly($topLevelCreatorsOptionsField);
			$fields->makeFieldReadonly($topLevelCreatorsGroupsField);
			$fields->makeFieldReadonly($taglineField);
			$fields->makeFieldReadonly($titleField);
		}

		$this->extend('updateEditFormFields', $fields);
		return $fields;
	}
	
	/**
	 * Get the actions that are sent to the CMS. In
	 * your decorators: updateEditFormActions(&$actions)
	 *
	 * @return Fieldset
	 */
	function getFormActions() {
		if (Permission::check('ADMIN') || Permission::check('EDIT_SITECONFIG')) {
			$actions = new FieldSet(
				new FormAction('save_siteconfig', 'Save')
			);
		} else {
			$actions = new FieldSet();
		}
		
		$this->extend('updateEditFormActions', $actions);
		return $actions;
	}
	
	/**
	 * Get the current sites SiteConfig
	 *
	 * @return SiteConfig
	 */
	static function current_site_config() {
		$siteConfig = DataObject::get_one('SiteConfig');
		if (!$siteConfig) {
			self::make_site_config();
			$siteConfig = DataObject::get_one('SiteConfig');
		}
		return $siteConfig;
	}
	
	/**
	 * Setup a default SiteConfig record if none exists
	 */
	function requireDefaultRecords() {
		parent::requireDefaultRecords();
		$siteConfig = DataObject::get_one('SiteConfig');
		if(!$siteConfig) {
			self::make_site_config();
			DB::alteration_message("Added default site config","created");
		}
	}
	
	/**
	 * Make a default site config if there isn't on already
	 *
	 * @return void
	 */
	static function make_site_config() {
		if(!DataObject::get_one('SiteConfig')){
			$siteConfig = new SiteConfig();
			$siteConfig->Title = 'Your Site Name';
			$siteConfig->Tagline = 'your tagline here';
			$siteConfig->write();
		}
	}
	
	/**
	 * Can a user view pages on this site? This method is only
	 * called if a page is set to Inherit, but there is nothing
	 * to inherit from.
	 *
	 * @param mixed $member 
	 * @return boolean
	 */
	public function canView($member = null) {
		if ($this->CanViewType == 'Anyone') return true;
		
		if(!$member || !(is_a($member, 'Member')) || is_numeric($member)) {
			$member = Member::currentUserID();
		}
				
		// check for any logged-in users
		if($this->CanViewType == 'LoggedInUsers' && $member) return true;
		
		// check for specific groups
		if($member && is_numeric($member)) $member = DataObject::get_by_id('Member', $member);
		if($this->CanViewType == 'OnlyTheseUsers' && $member && $member->inGroups($this->ViewerGroups())) return true;
		
		return false;
	}
	
	/**
	 * Can a user edit pages on this site? This method is only
	 * called if a page is set to Inherit, but there is nothing
	 * to inherit from.
	 *
	 * @param mixed $member 
	 * @return boolean
	 */
	public function canEdit($member = null) {
		if(!$member || !(is_a($member, 'Member')) || is_numeric($member)) {
			$member = Member::currentUserID();
		}

		// check for any logged-in users
		if($this->CanEditType == 'LoggedInUsers' && $member) return true;
		
		// check for specific groups
		if($member && is_numeric($member)) $member = DataObject::get_by_id('Member', $member);
		if($this->CanEditType == 'OnlyTheseUsers' && $member && $member->inGroups($this->EditorGroups())) return true;
		

		return false;
	}
	
	function providePermissions() {
		return array(
			'EDIT_SITECONFIG' => array(
				'name' => _t('SiteConfig.EDIT_PERMISSION', 'Manage site configuration'),
				'category' => _t('Permissions.PERMISSIONS_CATEGORY', 'Roles and access permissions'),
				'help' => _t('SiteConfig.EDIT_PERMISSION_HELP', 'Ability to edit global access settings/top-level page permissions.'),
				'sort' => 400
			)
		);
	}
	
	/**
	 * Can a user create pages in the root of this site?
	 *
	 * @param mixed $member 
	 * @return boolean
	 */
	public function canCreateTopLevel($member = null) {
		if(!$member || !(is_a($member, 'Member')) || is_numeric($member)) {
			$member = Member::currentUserID();
		}

		// check for any logged-in users
		if($this->CanCreateTopLevelType == 'LoggedInUsers' && $member) return true;
		
		// check for specific groups
		if($member && is_numeric($member)) $member = DataObject::get_by_id('Member', $member);
		if($this->CanCreateTopLevelType == 'OnlyTheseUsers' && $member && $member->inGroups($this->CreateTopLevelGroups())) return true;
		

		return false;
	}
}
