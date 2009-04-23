<?php
/**
 * The member class which represents the users of the system
 * @package sapphire
 * @subpackage security
 */
class Member extends DataObject {

	static $db = array(
		'FirstName' => 'Varchar',
		'Surname' => 'Varchar',
		'Email' => 'Varchar',
		'Password' => 'Varchar(64)', // support for up to SHA256!
		'RememberLoginToken' => 'Varchar(50)',
		'NumVisit' => 'Int',
		'LastVisited' => 'SSDatetime',
		'Bounced' => 'Boolean', // Note: This does not seem to be used anywhere.
		'AutoLoginHash' => 'Varchar(30)',
		'AutoLoginExpired' => 'SSDatetime',
		'PasswordEncryption' => "Enum('none', 'none')",
		'Salt' => 'Varchar(50)',
		'PasswordExpiry' => 'Date',
		'LockedOutUntil' => 'SSDatetime',
		'Locale' => 'Varchar(6)',
	);

	static $belongs_many_many = array(
		'Groups' => 'Group',
	);

	static $has_one = array();
	
	static $has_many = array();
	
	static $many_many = array();
	
	static $many_many_extraFields = array();

	static $default_sort = 'Surname, FirstName';

	static $indexes = array(
		'Email' => true,
		'AutoLoginHash' => 'unique (AutoLoginHash)'
	);

	static $notify_password_change = false;
	
	/**
	 * All searchable database columns
	 * in this object, currently queried
	 * with a "column LIKE '%keywords%'
	 * statement.
	 *
	 * @var array
	 * @todo Generic implementation of $searchable_fields on DataObject,
	 * with definition for different searching algorithms
	 * (LIKE, FULLTEXT) and default FormFields to construct a searchform.
	 */
	static $searchable_fields = array(
		'FirstName',
		'Surname',
		'Email',
	);
	
	static $summary_fields = array(
		'FirstName' => 'First Name',
		'Surname' => 'Last Name',
		'Email' => 'Email',
	);	
	
	/**
	 * The unique field used to identify this member.
	 * By default, it's "Email", but another common
	 * field could be Username.
	 * 
	 * @var string
	 */
	protected static $unique_identifier_field = 'Email';
	
	/**
	 * {@link PasswordValidator} object for validating user's password
	 */
	protected static $password_validator = null;
	
	/**
	 * The number of days that a password should be valid for.
	 * By default, this is null, which means that passwords never expire
	 */
	protected static $password_expiry_days = null;

	protected static $lock_out_after_incorrect_logins = null;
	
	/**
	 * This method is used to initialize the static database members
	 *
	 * Since PHP doesn't support any expressions for the initialization of
	 * static member variables we need a method that does that.
	 *
	 * This method adds all supported encryption algorithms to the
	 * PasswordEncryption Enum field.
	 *
	 * @todo Maybe it would be useful to define this in DataObject and call
	 *       it automatically?
	 */
	public static function init_db_fields() {
		self::$db['PasswordEncryption'] = "Enum(array('none', '" .
			implode("', '", array_map("addslashes", Security::get_encryption_algorithms())) .
			"'), 'none')";
	}

	/**
	 * Check if the passed password matches the stored one
	 *
	 * @param string $password The clear text password to check
	 * @return bool Returns TRUE if the passed password is valid, otherwise FALSE.
	 */
	public function checkPassword($password) {
		// Only confirm that the password matches if the user isn't locked out
		if(!$this->isLockedOut()) {
			$encryption_details = Security::encrypt_password($password, $this->Salt, $this->PasswordEncryption);
			return ($this->Password === $encryption_details['password']);
		}
	}
	
	/**
	 * Returns true if this user is locked out
	 */
	public function isLockedOut() {
		return $this->LockedOutUntil && time() < strtotime($this->LockedOutUntil);
	}

	/**
	 * Regenerate the session_id.
	 * This wrapper is here to make it easier to disable calls to session_regenerate_id(), should you need to.  
	 * They have caused problems in certain
	 * quirky problems (such as using the Windmill 0.3.6 proxy).
	 */
	static function session_regenerate_id() {
		// This can be called via CLI during testing.
		if(Director::is_cli()) return;
		
		$file = '';
		$line = '';
		
		if(!headers_sent($file, $line)) session_regenerate_id(true);
	}
	
	/**
	 * Get the field used for uniquely identifying a member
	 * in the database. {@see Member::$unique_identifier_field}
	 * 
	 * @return string
	 */
	static function get_unique_identifier_field() {
		return self::$unique_identifier_field;
	}
	
	/**
	 * Set the field used for uniquely identifying a member
	 * in the database. {@see Member::$unique_identifier_field}
	 * 
	 * @param $field The field name to set as the unique field
	 */
	static function set_unique_identifier_field($field) {
		self::$unique_identifier_field = $field;
	}
	
	/**
	 * Set a {@link PasswordValidator} object to use to validate member's passwords.
	 */
	static function set_password_validator($pv) {
		self::$password_validator = $pv;
	}
	
	/**
	 * Returns the current {@link PasswordValidator}
	 */
	static function password_validator() {
		return self::$password_validator;
	}

	/**
	 * Set the number of days that a password should be valid for.
	 * Set to null (the default) to have passwords never expire.
	 */
	static function set_password_expiry($days) {
		self::$password_expiry_days = $days;
	}
	
	/**
	 * Configure the security system to lock users out after this many incorrect logins
	 */
	static function lock_out_after_incorrect_logins($numLogins) {
		self::$lock_out_after_incorrect_logins = $numLogins;
	}
	
	
	function isPasswordExpired() {
		if(!$this->PasswordExpiry) return false;
		return strtotime(date('Y-m-d')) >= strtotime($this->PasswordExpiry);
	}

	/**
	 * Logs this member in
	 *
	 * @param bool $remember If set to TRUE, the member will be logged in automatically the next time.
	 */
	function logIn($remember = false) {
		self::session_regenerate_id();

		Session::set("loggedInAs", $this->ID);

		$this->NumVisit++;

		if($remember) {
			$token = substr(md5(uniqid(rand(), true)), 0, 49 - strlen($this->ID));
			$this->RememberLoginToken = $token;
			Cookie::set('alc_enc', $this->ID . ':' . $token);
		} else {
			$this->RememberLoginToken = null;
			Cookie::set('alc_enc', null);
			Cookie::forceExpiry('alc_enc');
		}
		
		// Clear the incorrect log-in count
		if(self::$lock_out_after_incorrect_logins) {
			$failedLogins = Session::get('Member.FailedLogins');
			$failedLogins[$this->Email] = 0;
			Session::set('Member.FailedLogins', $failedLogins);
		}
		
		// Don't set column if its not built yet (the login might be precursor to a /dev/build...)
		if(array_key_exists('LockedOutUntil', DB::fieldList('Member'))) {
			$this->LockedOutUntil = null;
		}

		$this->write();
		
		// Audit logging hook
		$this->extend('memberLoggedIn');
	}

	/**
	 * Check if the member ID logged in session actually
	 * has a database record of the same ID. If there is
	 * no logged in user, FALSE is returned anyway.
	 * 
	 * @return boolean TRUE record found FALSE no record found
	 */
	static function logged_in_session_exists() {
		if($id = Member::currentUserID()) {
			if($member = DataObject::get_by_id('Member', $id)) {
				if($member->exists()) return true;
			}
		}
		
		return false;
	}
	
	/**
	 * Log the user in if the "remember login" cookie is set
	 *
	 * The <i>remember login token</i> will be changed on every successful
	 * auto-login.
	 */
	static function autoLogin() {
		if(strpos(Cookie::get('alc_enc'), ':') && !Session::get("loggedInAs")) {
			list($uid, $token) = explode(':', Cookie::get('alc_enc'), 2);
			$SQL_uid = Convert::raw2sql($uid);

			$member = DataObject::get_one("Member", "Member.ID = '$SQL_uid'");

			// check if autologin token matches
			if($member && (!$member->RememberLoginToken || $member->RememberLoginToken != $token)) {
				$member = null;
			}

			if($member) {
				self::session_regenerate_id();
				Session::set("loggedInAs", $member->ID);

				$token = substr(md5(uniqid(rand(), true)), 0, 49 - strlen($member->ID));
				$member->RememberLoginToken = $token;
				Cookie::set('alc_enc', $member->ID . ':' . $token);

				$member->NumVisit++;
				$member->write();
				
				// Audit logging hook
				$member->extend('memberAutoLoggedIn');
			}
		}
	}

	/**
	 * Logs this member out.
	 */
	function logOut() {
		Session::clear("loggedInAs");
		self::session_regenerate_id();

		$this->extend('memberLoggedOut');

		$this->RememberLoginToken = null;
		Cookie::set('alc_enc', null);
		Cookie::forceExpiry('alc_enc');

		$this->write();
		
		// Audit logging hook
		$this->extend('memberLoggedOut');
	}


	/**
	 * Generate an auto login hash
	 *
	 * This creates an auto login hash that can be used to reset the password.
	 *
	 * @param int $lifetime The lifetime of the auto login hash in days (by default 2 days)
	 *
	 * @todo Make it possible to handle database errors such as a "duplicate key" error
	 */
	function generateAutologinHash($lifetime = 2) {

		do {
			$hash = substr(base_convert(md5(uniqid(mt_rand(), true)), 16, 36),
										 0, 30);
		} while(DataObject::get_one('Member', "`AutoLoginHash` = '$hash'"));

		$this->AutoLoginHash = $hash;
		$this->AutoLoginExpired = date('Y-m-d', time() + (86400 * $lifetime));

		$this->write();
	}

	/**
	 * Return the member for the auto login hash
	 *
	 * @param bool $login Should the member be logged in?
	 */
	static function member_from_autologinhash($RAW_hash, $login = false) {
		$SQL_hash = Convert::raw2sql($RAW_hash);

		$member = DataObject::get_one('Member',"`AutoLoginHash`='" . $SQL_hash .
																	"' AND `AutoLoginExpired` > NOW()");

		if($login && $member)
			$member->logIn();

		return $member;
	}


	/**
	 * Send signup, change password or forgot password informations to an user
	 *
	 * @param string $type Information type to send ("signup", "changePassword" or "forgotPassword")
	 * @param array $data Additional data to pass to the email (can be used in the template)
	 */
	function sendInfo($type = 'signup', $data = null) {
		switch($type) {
			case "signup":
				$e = Object::create('Member_SignupEmail');
				break;
			case "changePassword":
				$e = Object::create('Member_ChangePasswordEmail');
				break;
			case "forgotPassword":
				$e = Object::create('Member_ForgotPasswordEmail');
				break;
		}

		if(is_array($data)) {
			foreach($data as $key => $value)
				$e->$key = $value;
		}

		$e->populateTemplate($this);
		$e->send();
	}


	/**
	 * Returns the fields for the member form - used in the registration/profile module.
	 * It should return fields that are editable by the admin and the logged-in user. 
	 *
	 * @return FieldSet Returns a {@link FieldSet} containing the fields for
	 *                  the member form.
	 */
	function getMemberFormFields() {
		$fields = new FieldSet(
			new TextField("FirstName", _t('Member.FIRSTNAME', 'First Name')),
			new TextField("Surname", _t('Member.SURNAME', "Surname")),
			new TextField("Email", _t('Member.EMAIL', "Email", PR_MEDIUM, 'Noun')),
			new TextField("Password", _t('Member.PASSWORD', 'Password'))
		);
		
		$this->extend('augmentMemberFormFields', $fields);
		return $fields;
	}

	function getValidator() {
		return new Member_Validator();
	}


	/**
	 * Returns the current logged in user
	 *
	 * @return bool|Member Returns the member object of the current logged in
	 *                     user or FALSE.
	 */
	static function currentUser() {
		$id = Session::get("loggedInAs");
		if(!$id) {
			self::autoLogin();
			$id = Session::get("loggedInAs");
		}

		if($id) {
			return DataObject::get_one("Member", "Member.ID = $id");
		}
	}


	/**
	 * Get the ID of the current logged in user
	 *
	 * @return int Returns the ID of the current logged in user or 0.
	 */
	static function currentUserID() {
		$id = Session::get("loggedInAs");
		if(!$id) {
			self::autoLogin();
			$id = Session::get("loggedInAs");
		}

		return is_numeric($id) ? $id : 0;
	}


	/*
	 * Generate a random password, with randomiser to kick in if there's no words file on the
	 * filesystem.
	 *
	 * @return string Returns a random password.
	 */
	static function create_new_password() {
		if(file_exists(Security::get_word_list())) {
			$words = file(Security::get_word_list());

			list($usec, $sec) = explode(' ', microtime());
			srand($sec + ((float) $usec * 100000));

			$word = trim($words[rand(0,sizeof($words)-1)]);
			$number = rand(10,999);

			return $word . $number;
		} else {
	    	$random = rand();
		    $string = md5($random);
    		$output = substr($string, 0, 6);
	    	return $output;
		}
	}

	/**
	 * Event handler called before writing to the database.
	 */
	function onBeforeWrite() {
		if($this->SetPassword) $this->Password = $this->SetPassword;
		$identifierField = self::$unique_identifier_field;
		if($this->$identifierField) {
			$idClause = ($this->ID) ? " AND `Member`.ID <> $this->ID" : '';
			$SQL_identifierField = Convert::raw2sql($this->$identifierField);
			
			$existingRecord = DataObject::get_one('Member', "$identifierField = '{$SQL_identifierField}'{$idClause}");
			if($existingRecord) {
				$newID = $existingRecord->ID;
				if($this->ID) {
					DB::query("UPDATE Group_Members SET MemberID = $newID WHERE MemberID = $this->ID");
				}
				$this->ID = $newID;
				// Merge existing data into the local record

				foreach($existingRecord->getAllFields() as $k => $v) {
					if(!isset($this->changed[$k]) || !$this->changed[$k]) $this->record[$k] = $v;
				}
				$existingRecord->destroy();
			}
		}
		
		// We don't send emails out on dev/tests sites to prevent accidentally spamming users.
		// However, if TestMailer is in use this isn't a risk.
		if(
			(Director::isLive() || Email::mailer() instanceof TestMailer) 
			&& isset($this->changed['Password']) 
			&& $this->changed['Password'] 
			&& $this->record['Password'] 
			&& Member::$notify_password_change
		) {
			$this->sendInfo('changePassword');
		}
		
		// The test on $this->ID is used for when records are initially created
		if(!$this->ID || (isset($this->changed['Password']) && $this->changed['Password'])) {
			// Password was changed: encrypt the password according the settings
			$encryption_details = Security::encrypt_password($this->Password);
			$this->Password = $encryption_details['password'];
			$this->Salt = $encryption_details['salt'];
			$this->PasswordEncryption = $encryption_details['algorithm'];

			$this->changed['Salt'] = true;
			$this->changed['PasswordEncryption'] = true;
			
			// If we haven't manually set a password expiry
			if(!isset($this->changed['PasswordExpiry']) || !$this->changed['PasswordExpiry']) {
				// then set it for us
				if(self::$password_expiry_days) {
					$this->PasswordExpiry = date('Y-m-d', time() + 86400 * self::$password_expiry_days);
				} else {
					$this->PasswordExpiry = null;
				}
			}
		}
		
		// save locale
		if(!$this->Locale) {
			$this->Locale = i18n::get_locale();
		}

		parent::onBeforeWrite();
	}
	
	function onAfterWrite() {
		parent::onAfterWrite();

		if(isset($this->changed['Password']) && $this->changed['Password']) {
			MemberPassword::log($this);
		}
	}


	/**
	 * Check if the member is in one of the given groups.
	 *
	 * @param array|DataObjectSet $groups Collection of {@link Group} DataObjects to check
	 * @param boolean $strict Only determine direct group membership if set to true (Default: false)
	 * @return bool Returns TRUE if the member is in one of the given groups, otherwise FALSE.
	 */
	public function inGroups($groups, $strict = false) {
		if($groups) foreach($groups as $group) {
			if($this->inGroup($group, $strict)) return true;
		}
		
		return false;
	}


	/**
	 * Check if the member is in the given group or any parent groups.
	 *
	 * @param int|Group|string $group Group instance, Group Code or ID
	 * @param boolean $strict Only determine direct group membership if set to TRUE (Default: FALSE)
	 * @return bool Returns TRUE if the member is in the given group, otherwise FALSE.
	 */
	public function inGroup($group, $strict = false) {
		if(is_numeric($group)) {
			$groupCheckObj = DataObject::get_by_id('Group', $group);
		} elseif(is_string($group)) {
			$SQL_group = Convert::raw2sql($group);
			$groupCheckObj = DataObject::get_one('Group', "Code = '{$SQL_group}'");
		} elseif($group instanceof Group) {
			$groupCheckObj = $group;
		} else {
			user_error('Member::inGroup(): Wrong format for $group parameter', E_USER_ERROR);
		}
		
		if(!$groupCheckObj) return false;
		
		$groupCandidateObjs = ($strict) ? $this->getManyManyComponents("Groups") : $this->Groups();
		if($groupCandidateObjs) foreach($groupCandidateObjs as $groupCandidateObj) {
			if($groupCandidateObj->ID == $groupCheckObj->ID) return true;
		}

		return false;
	}
	
	/**
	 * Returns true if this user is an administrator.
	 * Administrators have access to everything.
	 * 
	 * @deprecated Use Permission::check('ADMIN') instead
	 * @return Returns TRUE if this user is an administrator.
	 */
	function isAdmin() {
		return Permission::checkMember($this, 'ADMIN');
	}

	//------------------- HELPER METHODS -----------------------------------//

	/**
	 * Get the complete name of the member
	 *
	 * @return string Returns the first- and surname of the member. If the ID
	 *                of the member is equal 0, only the surname is returned.
	 */
	public function getTitle() {
		if($this->getField('ID') === 0)
			return $this->getField('Surname');
		return $this->getField('Surname') . ', ' . $this->getField('FirstName');
	}


	/**
	 * Get the complete name of the member
	 *
	 * @return string Returns the first- and surname of the member.
	 */
	public function getName() {
		return $this->FirstName . ' ' . $this->Surname;
	}


	/**
	 * Set first- and surname
	 *
	 * This method assumes that the last part of the name is the surname, e.g.
	 * <i>A B C</i> will result in firstname <i>A B</i> and surname <i>C</i>
	 *
	 * @param string $name The name
	 */
	public function setName($name) {
		$nameParts = explode(' ', $name);
		$this->Surname = array_pop($nameParts);
		$this->FirstName = join(' ', $nameParts);
	}


	/**
	 * Alias for {@link setName}
	 *
	 * @param string $name The name
	 * @see setName()
	 */
	public function splitName($name) {
		return $this->setName($name);
	}

	//---------------------------------------------------------------------//


	/**
	 * Get a "many-to-many" map that holds for all members their group
	 * memberships
	 *
	 * @return Member_GroupSet Returns a map holding for all members their
	 *                         group memberships.
	 */
	public function Groups() {
		$groups = $this->getManyManyComponents("Groups");
		$groupIDs = $groups->column();
		$collatedGroups = array();

		if($groups) {
			foreach($groups as $group) {
				$collatedGroups = array_merge((array)$collatedGroups, $group->collateAncestorIDs());
			}
		}

		$table = "Group_Members";

		if(count($collatedGroups) > 0) {
			$collatedGroups = implode(", ", array_unique($collatedGroups));

			$unfilteredGroups = singleton('Group')->instance_get("`ID` IN ($collatedGroups)", "ID", "", "", "Member_GroupSet");
			$result = new ComponentSet();
			
			// Only include groups where allowedIPAddress() returns true
			$ip = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : null;
			foreach($unfilteredGroups as $group) {
				if($group->allowedIPAddress($ip)) $result->push($group);
			}
		} else {
			$result = new Member_GroupSet();
		}

		$result->setComponentInfo("many-to-many", $this, "Member", $table, "Group");

		return $result;
	}


	/**
	 * Get member SQLMap
	 *
	 * @param string $filter Filter for the SQL statement (WHERE clause)
	 * @param string $sort Sorting function (ORDER clause)
	 * @param string $blank Shift a blank member in the items
	 * @return SQLMap Returns an SQLMap that returns all Member data.
	 *
	 * @todo Improve documentation of this function! (Markus)
	 */
	public function map($filter = "", $sort = "", $blank="") {
		$ret = new SQLMap(singleton('Member')->extendedSQL($filter, $sort));
		if($blank) {
			$blankMember = Object::create('Member');
			$blankMember->Surname = $blank;
			$blankMember->ID = 0;

			$ret->getItems()->shift($blankMember);
		}

		return $ret;
	}


	/**
	 * Get a member SQLMap of members in specific groups
	 *
	 * @param mixed $groups Optional groups to include in the map. If NULL is
	 *                      passed, all groups are returned, i.e.
	 *                      {@link map()} will be called.
	 * @return SQLMap Returns an SQLMap that returns all Member data.
	 * @see map()
	 *
	 * @todo Improve documentation of this function! (Markus)
	 */
	public static function mapInGroups($groups = null) {
		if(!$groups)
			return Member::map();

		$groupIDList = array();

		if(is_a($groups, 'DataObjectSet')) {
			foreach( $groups as $group )
				$groupIDList[] = $group->ID;
		} elseif(is_array($groups)) {
			$groupIDList = $groups;
		} else {
			$groupIDList[] = $groups;
		}

		if(empty($groupIDList))
			return Member::map();

		return new SQLMap(singleton('Member')->extendedSQL(
			"`GroupID` IN (" . implode( ',', $groupIDList ) .
			")", "Surname, FirstName", "", "INNER JOIN `Group_Members` ON `MemberID`=`Member`.`ID`"));
	}


	/**
	 * Get a map of all members in the groups given that have CMS permissions
	 *
	 * If no groups are passed, all groups with CMS permissions will be used.
	 *
	 * @param array $groups Groups to consider or NULL to use all groups with
	 *                      CMS permissions.
	 * @return SQLMap Returns a map of all members in the groups given that
	 *                have CMS permissions.
	 */
	public static function mapInCMSGroups($groups = null) {
		if(!$groups || $groups->Count() == 0) {
			$perms = array('ADMIN', 'CMS_ACCESS_AssetAdmin');
			
			$cmsPerms = singleton('CMSMain')->providePermissions();
			
			if(!empty($cmsPerms)) {
				$perms = array_unique(array_merge($perms, array_keys($cmsPerms)));
			}
			
			$SQL_perms = "'" . implode("', '", Convert::raw2sql($perms)) . "'";
			
			$groups = DataObject::get('Group', "", "",
				"INNER JOIN `Permission` ON `Permission`.GroupID = `Group`.ID AND `Permission`.Code IN ($SQL_perms)");
		}

		$groupIDList = array();

		if(is_a($groups, 'DataObjectSet')) {
			foreach($groups as $group) {
				$groupIDList[] = $group->ID;
			}
		} elseif(is_array($groups)) {
			$groupIDList = $groups;
		}

		$filterClause = ($groupIDList)
			? "`GroupID` IN (" . implode( ',', $groupIDList ) . ")"
			: "";

		return new SQLMap(singleton('Member')->extendedSQL($filterClause,
			"Surname, FirstName", "",
			"INNER JOIN `Group_Members` ON `MemberID`=`Member`.`ID` INNER JOIN `Group` ON `Group`.`ID`=`GroupID`"));
	}


	/**
	 * Get the groups in which the member is NOT in
	 *
	 * When passed an array of groups, and a component set of groups, this
	 * function will return the array of groups the member is NOT in.
	 *
	 * @param array $groupList An array of group code names.
	 * @param array $memberGroups A component set of groups (if set to NULL,
	 * 														$this->groups() will be used)
	 * @return array Groups in which the member is NOT in.
	 */
	public function memberNotInGroups($groupList, $memberGroups = null){
		if(!$memberGroups) $memberGroups = $this->Groups();

		foreach($memberGroups as $group) {
			if(in_array($group->Code, $groupList)) {
				$index = array_search($group->Code, $groupList);
				unset($groupList[$index]);
			}
		}
		
		return $groupList;
	}


	/**
	 * Return a {@link FieldSet} of fields that would appropriate for editing
	 * this member.
	 *
	 * @return FieldSet Return a FieldSet of fields that would appropriate for
	 *                  editing this member.
	 */
	public function getCMSFields() {
		$fields = parent::getCMSFields();
		
		$mainFields = $fields->fieldByName("Root")->fieldByName("Main")->Children;
		
		$password = new ConfirmedPasswordField(
			'Password', 
			null, 
			null, 
			null, 
			true // showOnClick
		);
		$password->setCanBeEmpty(true);
		if(!$this->ID) $password->showOnClick = false;
		$mainFields->replaceField('Password', $password);
		
		$mainFields->insertBefore(
			new HeaderField('MemberDetailsHeader',_t('Member.PERSONALDETAILS', "Personal Details", PR_MEDIUM, 'Headline for formfields')),
			'FirstName'
		);
		
		$mainFields->insertBefore(
			new HeaderField('MemberUserDetailsHeader',_t('Member.USERDETAILS', "User Details", PR_MEDIUM, 'Headline for formfields')),
			'Email'
		);
		
		$locale = ($this->Locale) ? $this->Locale : i18n::get_locale();
		$mainFields->replaceField('Locale', new DropdownField(
			"Locale", 
			_t('Member.INTERFACELANG', "Interface Language", PR_MEDIUM, 'Language of the CMS'), 
			i18n::get_existing_translations(), 
			$locale
		));
		
		$mainFields->removeByName('Bounced');
		$mainFields->removeByName('RememberLoginToken');
		$mainFields->removeByName('AutoLoginHash');
		$mainFields->removeByName('AutoLoginExpired');
		$mainFields->removeByName('PasswordEncryption');
		$mainFields->removeByName('PasswordExpiry');
		$mainFields->removeByName('LockedOutUntil');
		$mainFields->removeByName('Salt');
		$mainFields->removeByName('NumVisit');
		$mainFields->removeByName('LastVisited');
	
		$fields->removeByName('Subscriptions');
		// Groups relation will get us into logical conflicts because
		// Members are displayed within  group edit form in SecurityAdmin
		$fields->removeByName('Groups');

		$this->extend('updateCMSFields', $fields);
		
		return $fields;
	}
	
	/**
	 *
	 * @param boolean $includerelations a boolean value to indicate if the labels returned include relation fields
	 * 
	 */
	function fieldLabels($includerelations = true) {
		$labels = parent::fieldLabels($includerelations);
		
		$labels['FirstName'] = _t('Member.FIRSTNAME');
		$labels['Surname'] = _t('Member.SURNAME');
		$labels['Email'] = _t('Member.EMAIL');
		$labels['Password'] = _t('Member.db_Password', 'Password');
		$labels['NumVisit'] = _t('Member.db_NumVisit', 'Number of Visits');
		$labels['LastVisited'] = _t('Member.db_LastVisited', 'Last Visited Date');
		$labels['PasswordExpiry'] = _t('Member.db_PasswordExpiry', 'Password Expiry Date', PR_MEDIUM, 'Password expiry date');
		$labels['LockedOutUntil'] = _t('Member.db_LockedOutUntil', 'Locked out until', PR_MEDIUM, 'Security related date');
		$labels['Locale'] = _t('Member.db_Locale', 'Interface Locale');
		if($includerelations){
			$labels['Groups'] = _t('Member.belongs_many_many_Groups', 'Groups', PR_MEDIUM, 'Security Groups this member belongs to');
		}
		return $labels;
	}

	function requireDefaultRecords() {
		parent::requireDefaultRecords();
		
		if(!DB::query("SELECT * FROM Member")->value() && isset($_REQUEST['username']) && isset($_REQUEST['password'])) {
			Security::findAnAdministrator($_REQUEST['username'], $_REQUEST['password']);
			Database::alteration_message("Added admin account","created");
		}
	}
	
	function canEdit() {
		if( $this->ID == Member::currentUserID() ) return true;
		
		return Permission::check( 'ADMIN' );
	}


	/**
	 * Validate this member object.
	 */
	function validate() {
		$valid = parent::validate();
		
		if(!$this->ID || (isset($this->changed['Password']) && $this->changed['Password'])) {
			if($this->Password && self::$password_validator) {
				$valid->combineAnd(self::$password_validator->validate($this->Password, $this));
			}
		}

		if((!$this->ID && $this->SetPassword) || (isset($this->changed['SetPassword']) && $this->changed['SetPassword'])) {
			if($this->SetPassword && self::$password_validator) {
				$valid->combineAnd(self::$password_validator->validate($this->SetPassword, $this));
			}
		}

		return $valid;
	}	
	
	function changePassword($password) {
		$this->Password = $password;
		$valid = $this->validate();
		
		if($valid->valid()) {
			$this->AutoLoginHash = null;
			$this->write();
		}
		
		return $valid;
	}
	
	/**
	 * Tell this member that someone made a failed attempt at logging in as them.
	 * This can be used to lock the user out temporarily if too many failed attempts are made.
	 */
	function registerFailedLogin() {
		if(self::$lock_out_after_incorrect_logins) {
			// Keep a tally of the number of failed log-ins so that we can lock people out
			$failedLogins = Session::get('Member.FailedLogins');
			if(!isset($failedLogins[$this->Email])) $failedLogins[$this->Email] = 0;
			$failedLogins[$this->Email]++;
			Session::set('Member.FailedLogins', $failedLogins);
	
			if($failedLogins[$this->Email] >= self::$lock_out_after_incorrect_logins) {
				$this->LockedOutUntil = date('Y-m-d H:i:s', time() + 15*60);
				$this->write();
			}
		}
	}
	
	/**
	 * @deprecated 2.3 Use inGroup()
	 */
	public function isInGroup($groupID) {
		user_error('Member::isInGroup() is deprecated. Please use inGroup() instead.', E_USER_NOTICE);
		return $this->inGroup($groupID);
	}
}


/**
 * Special kind of {@link ComponentSet} that has special methods for
 * manipulating a user's membership
 * @package sapphire
 * @subpackage security
 */
class Member_GroupSet extends ComponentSet {
	/**
	 * Control group membership with a number of checkboxes.
	 *  - If the checkbox fields are present in $data, then the member will be
	 *    added to the group with the same codename.
	 *  - If the checkbox fields are *NOT* present in $data, then the member
	 *    will be removed from the group with the same codename.
	 *
	 * @param array $checkboxes An array list of the checkbox fieldnames (only
	 *               	          values are used). E.g. array(0, 1, 2)
	 * @param array $data The form data. Uually in the format array(0 => 2)
	 *                    (just pass the checkbox data from your form)
	 */
	function setByCheckboxes(array $checkboxes, array $data) {
		foreach($checkboxes as $checkbox) {
			if($data[$checkbox]) {
				$add[] = $checkbox;
			} else {
				$remove[] = $checkbox;
			}
		}

		if($add)
			$this->addManyByCodename($add);

		if($remove)
			$this->removeManyByCodename($remove);
	}


	/**
	 * Allows you to set groups based on a CheckboxSetField
	 *
	 * Pass the form element from your post data directly to this method, and
	 * it will update the groups and add and remove the member as appropriate.
	 *
	 * On the form setup:
	 *
	 * <code>
	 * $fields->push(
	 *   new CheckboxSetField(
	 *     "NewsletterSubscriptions",
	 *     "Receive email notification of events in ",
	 *     $sourceitems = DataObject::get("NewsletterType")->toDropDownMap("GroupID","Title"),
	 *     $selectedgroups = $member->Groups()->Map("ID","ID")
	 *   )
	 * );
	 * </code>
	 *
	 * On the form handler:
	 *
	 * <code>
	 * $groups = $member->Groups();
	 * $checkboxfield = $form->Fields()->fieldByName("NewsletterSubscriptions");
	 * $groups->setByCheckboxSetField($checkboxfield);
	 * </code>
	 *
	 * @param CheckboxSetField $checkboxsetfield The CheckboxSetField (with
	 *                                           data) from your form.
	 */
	function setByCheckboxSetField(CheckboxSetField $checkboxsetfield) {
		// Get the values from the formfield.
		$values = $checkboxsetfield->Value();
		$sourceItems = $checkboxsetfield->getSource();

		if($sourceItems) {
			// If (some) values are present, add and remove as necessary.
			if($values) {
				// update the groups based on the selections
				foreach($sourceItems as $k => $item) {
					if(in_array($k,$values)) {
						$add[] = $k;
					} else {
						$remove[] = $k;
					}
				}

			// else we should be removing all from the necessary groups.
			} else {
				$remove = array_keys($sourceItems);
			}

			if($add)
				$this->addManyByGroupID($add);

			if($remove)
				$this->RemoveManyByGroupID($remove);

		} else {
			USER_ERROR("Member::setByCheckboxSetField() - No source items could be found for checkboxsetfield " .
								 $checkboxsetfield->Name(), E_USER_WARNING);
		}
	}


	/**
	 * Adds this member to the groups based on the group IDs
	 *
	 * @param array $ids Group identifiers.
	 */
	function addManyByGroupID($groupIds){
		$groups = $this->getGroupsFromIDs($groupIds);
		if($groups) {
			foreach($groups as $group) {
				$this->add($group);
			}
		}
	}


	/**
	 * Removes the member from many groups based on the group IDs
	 *
	 * @param array $ids Group identifiers.
	 */
	function removeManyByGroupID($groupIds) {
	 	$groups = $this->getGroupsFromIDs($groupIds);
	 	if($groups) {
			foreach($groups as $group) {
				$this->remove($group);
			}
		}
	}


	/**
	 * Returns the groups from an array of group IDs
	 *
	 * @param array $ids Group identifiers.
	 * @return mixed Returns the groups from the array of Group IDs.
	 */
	function getGroupsFromIDs($ids){
		if($ids && count($ids) > 1) {
			return DataObject::get("Group", "ID IN (" . implode(",", $ids) . ")");
		} else {
			return DataObject::get_by_id("Group", $ids[0]);
		}
	}


	/**
	 * Adds this member to the groups based on the group codenames
	 *
	 * @param array $codenames Group codenames
	 */
	function addManyByCodename($codenames) {
		$groups = $this->codenamesToGroups($codenames);
		if($groups) {
			foreach($groups as $group){
				$this->add($group);
			}
		}
	}


	/**
	 * Removes this member from the groups based on the group codenames
	 *
	 * @param array $codenames Group codenames
	 */
	function removeManyByCodename($codenames) {
		$groups = $this->codenamesToGroups($codenames);
		if($groups) {
			foreach($groups as $group) {
				$this->remove($group);
			}
		}
	}


	/**
	 * Helper function to return the appropriate groups via a codenames
	 *
	 * @param array $codenames Group codenames
	 * @return array Returns the the appropriate groups.
	 */
	protected function codenamesToGroups($codenames) {
		$list = "'" . implode("', '", $codenames) . "'";
		$output = DataObject::get("Group", "Code IN ($list)");

		// Some are missing - throw warnings
		if(!$output || ($output->Count() != sizeof($list))) {
			foreach($codenames as $codename)
				$missing[$codename] = $codename;

			if($output) {
				foreach($output as $record)
					unset($missing[$record->Code]);
			}

			if($missing)
				user_error("The following group-codes aren't matched to any groups: " .
									 implode(", ", $missing) .
									 ".  You probably need to link up the correct group codes in phpMyAdmin",
									 E_USER_WARNING);
		}

		return $output;
	}
}



/**
 * Form for editing a member profile.
 * @package sapphire
 * @subpackage security
 */
class Member_ProfileForm extends Form {
	
	function __construct($controller, $name, $member) {
		Requirements::clear();
		Requirements::css(CMS_DIR . '/css/typography.css');
		Requirements::css(CMS_DIR . '/css/cms_right.css');
		Requirements::javascript(THIRDPARTY_DIR . "/prototype.js");
		Requirements::javascript(THIRDPARTY_DIR . "/behaviour.js");
		Requirements::javascript(THIRDPARTY_DIR . "/prototype_improvements.js");
		Requirements::javascript(THIRDPARTY_DIR . "/scriptaculous/scriptaculous.js");
		Requirements::javascript(THIRDPARTY_DIR . "/scriptaculous/controls.js");
		Requirements::javascript(THIRDPARTY_DIR . "/layout_helpers.js");
		Requirements::css(SAPPHIRE_DIR . "/css/Form.css");
		
		Requirements::css(SAPPHIRE_DIR . "/css/MemberProfileForm.css");
		
		
		$fields = singleton('Member')->getCMSFields();
		$fields->push(new HiddenField('ID','ID',$member->ID));

		$actions = new FieldSet(
			new FormAction('dosave',_t('CMSMain.SAVE'))
		);
		
		$validator = new RequiredFields(
		
		);
		
		parent::__construct($controller, $name, $fields, $actions, $validator);
		
		$this->loadDataFrom($member);
	}
	
	function dosave($data, $form) {
		$SQL_data = Convert::raw2sql($data);
		
		$member = DataObject::get_by_id("Member", $SQL_data['ID']);
		
		if($SQL_data['Locale'] != $member->Locale) {
			$form->addErrorMessage("Generic", _t('Member.REFRESHLANG'),"good");
		}
		
		$form->saveInto($member);
		$member->write();
		
		$closeLink = sprintf(
			'<small><a href="' . $_SERVER['HTTP_REFERER'] . '" onclick="javascript:window.top.GB_hide(); return false;">(%s)</a></small>',
			_t('ComplexTableField.CLOSEPOPUP', 'Close Popup')
		);
		$message = _t('Member.PROFILESAVESUCCESS', 'Successfully saved.') . ' ' . $closeLink;
		$form->sessionMessage($message, 'good');
		
		Director::redirectBack();
	}
}

/**
 * Class used as template to send an email to new members
 * @package sapphire
 * @subpackage security
 */
class Member_SignupEmail extends Email {
	protected $from = '';  // setting a blank from address uses the site's default administrator email
	protected $to = '$Email';
	protected $subject = '';
	protected $body = '';

	function __construct() {
		$this->subject = _t('Member.EMAILSIGNUPSUBJECT', "Thanks for signing up");
		$this->body = '
			<h1>' . _t('Member.GREETING','Welcome') . ', $FirstName.</h1>
			<p>' . _t('Member.EMAILSIGNUPINTRO1','Thanks for signing up to become a new member, your details are listed below for future reference.') . '</p>

			<p>' . _t('Member.EMAILSIGNUPINTRO2','You can login to the website using the credentials listed below')  . ':
				<ul>
					<li><strong>' . _t('Member.EMAIL') . '</strong>$Email</li>
					<li><strong>' . _t('Member.PASSWORD') . ':</strong>$Password</li>
				</ul>
			</p>

			<h3>' . _t('Member.CONTACTINFO','Contact Information') . '</h3>
			<ul>
				<li><strong>' . _t('Member.NAME','Name')  . ':</strong> $FirstName $Surname</li>
				<% if Phone %>
					<li><strong>' . _t('Member.PHONE','Phone') . ':</strong> $Phone</li>
				<% end_if %>

				<% if Mobile %>
					<li><strong>' . _t('Member.MOBILE','Mobile') . ':</strong> $Mobile</li>
				<% end_if %>

				<li><strong>' . _t('Member.ADDRESS','Address') . ':</strong>
				<br/>
				$Number $Street $StreetType<br/>
				$Suburb<br/>
				$City $Postcode
				</li>

			</ul>
		';
	}

	function MemberData() {
		return $this->template_data->listOfFields(
			"FirstName", "Surname", "Email",
			"Phone", "Mobile", "Street",
			"Suburb", "City", "Postcode"
		);
	}
}



/**
 * Class used as template to send an email saying that the password has been
 * changed
 * @package sapphire
 * @subpackage security
 */
class Member_ChangePasswordEmail extends Email {
    protected $from = '';   // setting a blank from address uses the site's default administrator email
    protected $subject = '';
    protected $ss_template = 'ChangePasswordEmail';
    protected $to = '$Email';
    
    function __construct() {
    	$this->subject = _t('Member.SUBJECTPASSWORDCHANGED', "Your password has been changed", PR_MEDIUM, 'Email subject');
    }
}



/**
 * Class used as template to send the forgot password email
 * @package sapphire
 * @subpackage security
 */
class Member_ForgotPasswordEmail extends Email {
    protected $from = '';  // setting a blank from address uses the site's default administrator email
    protected $subject = '';
    protected $ss_template = 'ForgotPasswordEmail';
    protected $to = '$Email';
    
    function __construct() {
    	$this->subject = _t('Member.SUBJECTPASSWORDRESET', "Your password reset link", PR_MEDIUM, 'Email subject');
    }
}

/**
 * Member Validator
 * @package sapphire
 * @subpackage security
 */
class Member_Validator extends RequiredFields {

	protected $customRequired = array('FirstName', 'Email'); //, 'Password');


	/**
	 * Constructor
	 */
	public function __construct() {
		$required = func_get_args();
		if(isset($required[0]) && is_array($required[0])) {
			$required = $required[0];
		}
		$required = array_merge($required, $this->customRequired);

		parent::__construct($required);
	}


	/**
	 * Check if the submitted member data is valid (server-side)
	 *
	 * Check if a member with that email doesn't already exist, or if it does
	 * that it is this member.
	 *
	 * @param array $data Submitted data
	 * @return bool Returns TRUE if the submitted data is valid, otherwise
	 *              FALSE.
	 */
	function php($data) {
		$valid = parent::php($data);
		
		$identifierField = Member::get_unique_identifier_field();
		
		$SQL_identifierField = Convert::raw2sql($data[$identifierField]);
		$member = DataObject::get_one('Member', "$identifierField = '{$SQL_identifierField}'");

		// if we are in a complex table field popup, use ctf[childID], else use ID
		if(isset($_REQUEST['ctf']['childID'])) {
			$id = $_REQUEST['ctf']['childID'];
		} elseif(isset($_REQUEST['ID'])) {
			$id = $_REQUEST['ID'];
		} else {
			$id = null;
		}

		if($id && is_object($member) && $member->ID != $id) {
			$uniqueField = $this->form->dataFieldByName($identifierField);
			$this->validationError(
				$uniqueField->id(),
				sprintf(
					_t(
						'Member.VALIDATIONMEMBEREXISTS',
						'A member already exists with the same %s'
					),
					strtolower($identifierField)
				),
				'required'
			);
			$valid = false;
		}

		// Execute the validators on the extensions
		if($this->extension_instances) {
			foreach($this->extension_instances as $extension) {
				if($extension->hasMethod('updatePHP')) {
					$valid &= $extension->updatePHP($data, $this->form);
				}
			}
		}

		return $valid;
	}


	/**
	 * Check if the submitted member data is valid (client-side)
	 *
	 * @param array $data Submitted data
	 * @return bool Returns TRUE if the submitted data is valid, otherwise
	 *              FALSE.
	 */
	function javascript() {
		$js = parent::javascript();

		// Execute the validators on the extensions
		if($this->extension_instances) {
			foreach($this->extension_instances as $extension) {
				if($extension->hasMethod('updateJavascript')) {
					$extension->updateJavascript($js, $this->form);
				}
			}
		}

		return $js;
	}
}
// Initialize the static DB variables to add the supported encryption
// algorithms to the PasswordEncryption Enum field
Member::init_db_fields();
?>