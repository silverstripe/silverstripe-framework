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
		'Password' => 'Varchar(160)',
		'RememberLoginToken' => 'Varchar(160)', // Note: this currently holds a hash, not a token.
		'NumVisit' => 'Int',
		'LastVisited' => 'SS_Datetime',
		'Bounced' => 'Boolean', // Note: This does not seem to be used anywhere.
		'AutoLoginHash' => 'Varchar(160)',
		'AutoLoginExpired' => 'SS_Datetime',
		// This is an arbitrary code pointing to a PasswordEncryptor instance,
		// not an actual encryption algorithm.
		// Warning: Never change this field after its the first password hashing without
		// providing a new cleartext password as well.
		'PasswordEncryption' => "Varchar(50)",
		'Salt' => 'Varchar(50)',
		'PasswordExpiry' => 'Date',
		'LockedOutUntil' => 'SS_Datetime',
		'Locale' => 'Varchar(6)',
		// handled in registerFailedLogin(), only used if $lock_out_after_incorrect_logins is set
		'FailedLoginCount' => 'Int',
		// In ISO format
		'DateFormat' => 'Varchar(30)',
		'TimeFormat' => 'Varchar(30)',
	);

	static $belongs_many_many = array(
		'Groups' => 'Group',
	);

	static $has_one = array();
	
	static $has_many = array();
	
	static $many_many = array();
	
	static $many_many_extraFields = array();

	static $default_sort = '"Surname", "FirstName"';

	static $indexes = array(
		'Email' => true,
		//'AutoLoginHash' => Array('type'=>'unique', 'value'=>'AutoLoginHash', 'ignoreNulls'=>true) //Removed due to duplicate null values causing MSSQL problems
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
	 * @var Array See {@link set_title_columns()}
	 */
	static $title_format = null;
	
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
	 * If this is set, then a session cookie with the given name will be set on log-in,
	 * and cleared on logout.
	 */
	protected static $login_marker_cookie = null;

	/**
	 * Indicates that when a {@link Member} logs in, Member:session_regenerate_id()
	 * should be called as a security precaution.
	 * 
	 * This doesn't always work, especially if you're trying to set session cookies
	 * across an entire site using the domain parameter to session_set_cookie_params()
	 * 
	 * @var boolean
	 */
	protected static $session_regenerate_id = true;

	public static function set_session_regenerate_id($bool) {
		self::$session_regenerate_id = $bool;
	}

	/**
	 * Ensure the locale is set to something sensible by default.
	 */
	public function populateDefaults() {
		parent::populateDefaults();
		$this->Locale = i18n::get_locale();
	}
	
	function requireDefaultRecords() {
		// Default groups should've been built by Group->requireDefaultRecords() already
		
		// Find or create ADMIN group
		$adminGroups = Permission::get_groups_by_permission('ADMIN');
		if(!$adminGroups) {
			singleton('Group')->requireDefaultRecords();
			$adminGroups = Permission::get_groups_by_permission('ADMIN');
		}
		$adminGroup = $adminGroups->First();
		
		// Add a default administrator to the first ADMIN group found (most likely the default
		// group created through Group->requireDefaultRecords()).
		$admins = Permission::get_members_by_permission('ADMIN');
		if(!$admins) {
			// Leave 'Email' and 'Password' are not set to avoid creating
			// persistent logins in the database. See Security::setDefaultAdmin().
			$admin = Object::create('Member');
			$admin->FirstName = _t('Member.DefaultAdminFirstname', 'Default Admin');
			$admin->write();
			$admin->Groups()->add($adminGroup);
		}		
	}

	/**
	 * If this is called, then a session cookie will be set to "1" whenever a user
	 * logs in.  This lets 3rd party tools, such as apache's mod_rewrite, detect
	 * whether a user is logged in or not and alter behaviour accordingly.
	 * 
	 * One known use of this is to bypass static caching for logged in users.  This is
	 * done by putting this into _config.php
	 * <pre>
	 * Member::set_login_marker_cookie("SS_LOGGED_IN");
	 * </pre>
	 * 
	 * And then adding this condition to each of the rewrite rules that make use of
	 * the static cache.
	 * <pre>
	 * RewriteCond %{HTTP_COOKIE} !SS_LOGGED_IN=1
	 * </pre>
	 * 
	 * @param $cookieName string The name of the cookie to set.
	 */
	static function set_login_marker_cookie($cookieName) {
		self::$login_marker_cookie = $cookieName;
	} 

	/**
	 * Check if the passed password matches the stored one (if the member is not locked out).
	 *
	 * @param  string $password
	 * @return ValidationResult
	 */
	public function checkPassword($password) {
		$result = $this->canLogIn();

		$spec = Security::encrypt_password(
			$password, 
			$this->Salt, 
			$this->PasswordEncryption,
			$this
		);
		$e = $spec['encryptor'];

		if(!$e->compare($this->Password, $spec['password'])) {
			$result->error(_t (
				'Member.ERRORWRONGCRED',
				'That doesn\'t seem to be the right e-mail address or password. Please try again.'
			));
		}

		return $result;
	}

	/**
	 * Returns a valid {@link ValidationResult} if this member can currently log in, or an invalid
	 * one with error messages to display if the member is locked out.
	 *
	 * You can hook into this with a "canLogIn" method on an attached extension.
	 *
	 * @return ValidationResult
	 */
	public function canLogIn() {
		$result = new ValidationResult();

		if($this->isLockedOut()) {
			$result->error(_t (
				'Member.ERRORLOCKEDOUT',
				'Your account has been temporarily disabled because of too many failed attempts at ' .
				'logging in. Please try again in 20 minutes.'
			));
		}

		$this->extend('canLogIn', $result);
		return $result;
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
		if(!self::$session_regenerate_id) return;

		// This can be called via CLI during testing.
		if(Director::is_cli()) return;
		
		$file = '';
		$line = '';
		
		// @ is to supress win32 warnings/notices when session wasn't cleaned up properly
		// There's nothing we can do about this, because it's an operating system function!
		if(!headers_sent($file, $line)) @session_regenerate_id(true);
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
		// This lets apache rules detect whether the user has logged in
		if(self::$login_marker_cookie) Cookie::set(self::$login_marker_cookie, 1, 0);

		$this->NumVisit++;

		if($remember) {
			// Store the hash and give the client the cookie with the token.
			$generator = new RandomGenerator();
			$token = $generator->randomToken('sha1');
			$hash = $this->encryptWithUserSettings($token);
			$this->RememberLoginToken = $hash;
			Cookie::set('alc_enc', $this->ID . ':' . $token, 90, null, null, null, true);
		} else {
			$this->RememberLoginToken = null;
			Cookie::set('alc_enc', null);
			Cookie::forceExpiry('alc_enc');
		}
		
		// Clear the incorrect log-in count
		if(self::$lock_out_after_incorrect_logins) {
			$this->FailedLoginCount = 0;
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
		// Don't bother trying this multiple times
		self::$_already_tried_to_auto_log_in = true;
		
		if(strpos(Cookie::get('alc_enc'), ':') && !Session::get("loggedInAs")) {
			list($uid, $token) = explode(':', Cookie::get('alc_enc'), 2);
			$SQL_uid = Convert::raw2sql($uid);

			$member = DataObject::get_one("Member", "\"Member\".\"ID\" = '$SQL_uid'");

			// check if autologin token matches
			$hash = $member->encryptWithUserSettings($token);
			if($member && (!$member->RememberLoginToken || $member->RememberLoginToken != $hash)) {
				$member = null;
			}

			if($member) {
				self::session_regenerate_id();
				Session::set("loggedInAs", $member->ID);
				// This lets apache rules detect whether the user has logged in
				if(self::$login_marker_cookie) Cookie::set(self::$login_marker_cookie, 1, 0, null, null, false, true);
				
				$generator = new RandomGenerator();
				$token = $generator->randomToken('sha1');
				$hash = $member->encryptWithUserSettings($token);
				$member->RememberLoginToken = $hash;
				Cookie::set('alc_enc', $member->ID . ':' . $token, 90, null, null, false, true);

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
		if(self::$login_marker_cookie) Cookie::set(self::$login_marker_cookie, null, 0);
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
	 * Utility for generating secure password hashes for this member.
	 */
	public function encryptWithUserSettings($string) {
		if (!$string) return null;

		// If the algorithm or salt is not available, it means we are operating
		// on legacy account with unhashed password. Do not hash the string.
		if (!$this->PasswordEncryption) {
			return $string;
		}

		// We assume we have PasswordEncryption and Salt available here.
		$e = PasswordEncryptor::create_for_algorithm($this->PasswordEncryption);
		return $e->encrypt($string, $this->Salt);

	}

	/**
	 * Generate an auto login token which can be used to reset the password,
	 * at the same time hashing it and storing in the database.
	 *
	 * @param int $lifetime The lifetime of the auto login hash in days (by default 2 days)
	 *
	 * @returns string Token that should be passed to the client (but NOT persisted).
	 *
	 * @todo Make it possible to handle database errors such as a "duplicate key" error
	 */
	public function generateAutologinTokenAndStoreHash($lifetime = 2) {
		do {
			$generator = new RandomGenerator();
			$token = $generator->randomToken();
			$hash = $this->encryptWithUserSettings($token);
		} while(DataObject::get_one('Member', "\"AutoLoginHash\" = '$hash'"));

		$this->AutoLoginHash = $hash;
		$this->AutoLoginExpired = date('Y-m-d', time() + (86400 * $lifetime));

		$this->write();

		return $token;
	}

	/**
	 * @deprecated 2.4
	 */
	public function generateAutologinHash($lifetime = 2) {
		user_error(
			'Member::generateAutologinHash is deprecated - tokens are no longer saved directly into the database '.
			'in plaintext. Use the return value of the Member::generateAutologinTokenAndHash to get the token '.
			'instead.',
			E_USER_ERROR);
	}

	/**
	 * Check the token against the member.
	 *
	 * @param string $autologinToken
	 *
	 * @returns bool Is token valid?
	 */
	public function validateAutoLoginToken($autologinToken) {
		$hash = $this->encryptWithUserSettings($autologinToken);

		$member = DataObject::get_one(
			'Member',
			"\"AutoLoginHash\"='" . $hash . "' AND \"AutoLoginExpired\" > " . DB::getConn()->now()
		);

		return (bool)$member;
	}

	/**
	 * Return the member for the auto login hash
	 *
	 * @param bool $login Should the member be logged in?
	 */
	static function member_from_autologinhash($RAW_hash, $login = false) {
		$SQL_hash = Convert::raw2sql($RAW_hash);

		$member = DataObject::get_one('Member',"\"AutoLoginHash\"='" . $SQL_hash . "' AND \"AutoLoginExpired\" > " . DB::getConn()->now());

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
		$e->setTo($this->Email);
		$e->send();
	}

	/**
	 * Returns the fields for the member form - used in the registration/profile module.
	 * It should return fields that are editable by the admin and the logged-in user. 
	 *
	 * @return FieldSet Returns a {@link FieldSet} containing the fields for
	 *                  the member form.
	 */
	public function getMemberFormFields() {
		$fields = parent::getFrontendFields();

		$fields->replaceField('Password', $password = new ConfirmedPasswordField (
			'Password',
			$this->fieldLabel('Password'),
			null,
			null,
			(bool) $this->ID
		));
		$password->setCanBeEmpty(true);

		$fields->replaceField('Locale', new DropdownField (
			'Locale',
			$this->fieldLabel('Locale'),
			i18n::get_existing_translations()
		));

		$fields->removeByName('RememberLoginToken');
		$fields->removeByName('NumVisit');
		$fields->removeByName('LastVisited');
		$fields->removeByName('Bounced');
		$fields->removeByName('AutoLoginHash');
		$fields->removeByName('AutoLoginExpired');
		$fields->removeByName('PasswordEncryption');
		$fields->removeByName('Salt');
		$fields->removeByName('PasswordExpiry');
		$fields->removeByName('FailedLoginCount');
		$fields->removeByName('LastViewed');
		$fields->removeByName('LockedOutUntil');

		$this->extend('updateMemberFormFields', $fields);
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
		$id = Member::currentUserID();
		if($id) {
			return DataObject::get_one("Member", "\"Member\".\"ID\" = $id");
		}
	}


	/**
	 * Get the ID of the current logged in user
	 *
	 * @return int Returns the ID of the current logged in user or 0.
	 */
	static function currentUserID() {
		$id = Session::get("loggedInAs");
		if(!$id && !self::$_already_tried_to_auto_log_in) {
			self::autoLogin();
			$id = Session::get("loggedInAs");
		}

		return is_numeric($id) ? $id : 0;
	}
	private static $_already_tried_to_auto_log_in = false;


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

		// If a member with the same "unique identifier" already exists with a different ID, don't allow merging.
		// Note: This does not a full replacement for safeguards in the controller layer (e.g. in a registration form), 
		// but rather a last line of defense against data inconsistencies.
		$identifierField = self::$unique_identifier_field;
		if($this->$identifierField) {
			// Note: Same logic as Member_Validator class
			$idClause = ($this->ID) ? sprintf(" AND \"Member\".\"ID\" <> %d", (int)$this->ID) : '';
			$existingRecord = DataObject::get_one(
				'Member', 
				sprintf(
					"\"%s\" = '%s' %s",
					$identifierField,
					Convert::raw2sql($this->$identifierField),
					$idClause
				)
			);
			if($existingRecord) {
				throw new ValidationException(new ValidationResult(false, sprintf(
					_t(
						'Member.ValidationIdentifierFailed', 
						'Can\'t overwrite existing member #%d with identical identifier (%s = %s))', 
						PR_MEDIUM,
						'The values in brackets show a fieldname mapped to a value, usually denoting an existing email address'
					),
					$existingRecord->ID,
					$identifierField,
					$this->$identifierField
				)));
			}
		}

		// We don't send emails out on dev/tests sites to prevent accidentally spamming users.
		// However, if TestMailer is in use this isn't a risk.
		if(
			(Director::isLive() || Email::mailer() instanceof TestMailer) 
			&& $this->isChanged('Password')
			&& $this->record['Password'] 
			&& Member::$notify_password_change
		) {
			$this->sendInfo('changePassword');
		}

		// The test on $this->ID is used for when records are initially created.
		// Note that this only works with cleartext passwords, as we can't rehash
		// existing passwords.
		if((!$this->ID && $this->Password) || $this->isChanged('Password')) {
			// Password was changed: encrypt the password according the settings
			$encryption_details = Security::encrypt_password(
				$this->Password, // this is assumed to be cleartext
				$this->Salt,
				$this->PasswordEncryption,
				$this
			);

			// Overwrite the Password property with the hashed value
			$this->Password = $encryption_details['password'];
			$this->Salt = $encryption_details['salt'];
			$this->PasswordEncryption = $encryption_details['algorithm'];

			// If we haven't manually set a password expiry
			if(!$this->isChanged('PasswordExpiry')) {
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

		if($this->isChanged('Password')) {
			MemberPassword::log($this);
		}
	}
	
	/**
	 * If any admin groups are requested, deny the whole save operation.
	 * 
	 * @param Array $ids Database IDs of Group records
	 * @return boolean
	 */
	function onChangeGroups($ids) {
		// Filter out admin groups to avoid privilege escalation, 
		// unless the current user is an admin already
		if(!Permission::checkMember($this, 'ADMIN')) {
			$adminGroups = Permission::get_groups_by_permission('ADMIN');
			$adminGroupIDs = ($adminGroups) ? $adminGroups->column('ID') : array();
			return count(array_intersect($ids, $adminGroupIDs)) == 0;
		} else {
			return true;
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
			$groupCheckObj = DataObject::get_one('Group', "\"Code\" = '{$SQL_group}'");
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
	 * Adds the member to a group. This will create the group if the given 
	 * group code does not return a valid group object. 
	 *
	 * @param string $groupcode
	 * @param string Title of the group
	 */
	public function addToGroupByCode($groupcode, $title = "") {
		$group = DataObject::get_one('Group', "\"Code\" = '" . Convert::raw2sql($groupcode). "'");
		
		if($group) {
			$this->Groups()->add($group);
		}
		else {
			if(!$title) $title = $groupcode;
			
			$group = new Group();
			$group->Code = $groupcode;
			$group->Title = $title;
			$group->write();
			
			$this->Groups()->add($group);
		}
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
	
	/**
	 * @param Array $columns Column names on the Member record to show in {@link getTitle()}.
	 * @param String $sep Separator
	 */
	static function set_title_columns($columns, $sep = ' ') {
		if (!is_array($columns)) $columns = array($columns);
		self::$title_format = array('columns' => $columns, 'sep' => $sep);
	}

	//------------------- HELPER METHODS -----------------------------------//

	/**
	 * Get the complete name of the member, by default in the format "<Surname>, <FirstName>".
	 * Falls back to showing either field on its own.
	 * 
	 * You can overload this getter with {@link set_title_format()}
	 * and {@link set_title_sql()}.
	 *
	 * @return string Returns the first- and surname of the member. If the ID
	 *  of the member is equal 0, only the surname is returned.
	 */
	public function getTitle() {
		if (self::$title_format) {
			$values = array();
			foreach(self::$title_format['columns'] as $col) {
				$values[] = $this->getField($col);
			}
			return join(self::$title_format['sep'], $values);
		}
		if($this->getField('ID') === 0)
			return $this->getField('Surname');
		else{
			if($this->getField('Surname') && $this->getField('FirstName')){
				return $this->getField('Surname') . ', ' . $this->getField('FirstName');
			}elseif($this->getField('Surname')){
				return $this->getField('Surname');
			}elseif($this->getField('FirstName')){
				return $this->getField('FirstName');
			}else{
				return null;
			}
		}
	}

	/**
	 * Return a SQL CONCAT() fragment suitable for a SELECT statement.
	 * Useful for custom queries which assume a certain member title format.
	 * 
	 * @param String $tableName
	 * @return String SQL
	 */
	static function get_title_sql($tableName = 'Member') {
		// This should be abstracted to SSDatabase concatOperator or similar.
		$op = (DB::getConn() instanceof MSSQLDatabase) ? " + " : " || ";

		if (self::$title_format) {
			$columnsWithTablename = array();
			foreach(self::$title_format['columns'] as $column) {
				$columnsWithTablename[] = "\"$tableName\".\"$column\"";
			}
		
			return "(".join(" $op '".self::$title_format['sep']."' $op ", $columnsWithTablename).")";
		} else {
			return "(\"$tableName\".\"Surname\" $op ' ' $op \"$tableName\".\"FirstName\")";
		}
	}


	/**
	 * Get the complete name of the member
	 *
	 * @return string Returns the first- and surname of the member.
	 */
	public function getName() {
		return ($this->Surname) ? trim($this->FirstName . ' ' . $this->Surname) : $this->FirstName;
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

	/**
	 * Override the default getter for DateFormat so the
	 * default format for the user's locale is used
	 * if the user has not defined their own.
	 * 
	 * @return string ISO date format
	 */
	public function getDateFormat() {
		if($this->getField('DateFormat')) {
			return $this->getField('DateFormat');
		} elseif($this->getField('Locale')) {
			require_once 'Zend/Date.php';
			return Zend_Locale_Format::getDateFormat($this->Locale);
		} else {
			return i18n::get_date_format();
		}
	}

	/**
	 * Override the default getter for TimeFormat so the
	 * default format for the user's locale is used
	 * if the user has not defined their own.
	 * 
	 * @return string ISO date format
	 */
	public function getTimeFormat() {
		if($this->getField('TimeFormat')) {
			return $this->getField('TimeFormat');
		} elseif($this->getField('Locale')) {
			require_once 'Zend/Date.php';
			return Zend_Locale_Format::getTimeFormat($this->Locale);
		} else {
			return i18n::get_time_format();
		}
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

			$unfilteredGroups = singleton('Group')->instance_get("\"Group\".\"ID\" IN ($collatedGroups)", "\"Group\".\"ID\"", "", "", "Member_GroupSet");
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

			$ret->getItems()->unshift($blankMember);
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
			"\"GroupID\" IN (" . implode( ',', $groupIDList ) .
			")", "Surname, FirstName", "", "INNER JOIN \"Group_Members\" ON \"MemberID\"=\"Member\".\"ID\""));
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
				"INNER JOIN \"Permission\" ON \"Permission\".\"GroupID\" = \"Group\".\"ID\" AND \"Permission\".\"Code\" IN ($SQL_perms)");
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
			? "\"GroupID\" IN (" . implode( ',', $groupIDList ) . ")"
			: "";

		return new SQLMap(singleton('Member')->extendedSQL($filterClause,
			"Surname, FirstName", "",
			"INNER JOIN \"Group_Members\" ON \"MemberID\"=\"Member\".\"ID\" INNER JOIN \"Group\" ON \"Group\".\"ID\"=\"GroupID\""));
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
		require_once('Zend/Date.php');
		
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
		
		$mainFields->replaceField('Locale', new DropdownField(
			"Locale", 
			_t('Member.INTERFACELANG', "Interface Language", PR_MEDIUM, 'Language of the CMS'), 
			i18n::get_existing_translations()
		));
		
		$mainFields->removeByName('Bounced');
		$mainFields->removeByName('RememberLoginToken');
		$mainFields->removeByName('AutoLoginHash');
		$mainFields->removeByName('AutoLoginExpired');
		$mainFields->removeByName('PasswordEncryption');
		$mainFields->removeByName('PasswordExpiry');
		$mainFields->removeByName('LockedOutUntil');
		
		if(!self::$lock_out_after_incorrect_logins) {
			$mainFields->removeByName('FailedLoginCount');
		}
		
		$mainFields->removeByName('Salt');
		$mainFields->removeByName('NumVisit');
		$mainFields->removeByName('LastVisited');
	
		$fields->removeByName('Subscriptions');

		// Groups relation will get us into logical conflicts because
		// Members are displayed within  group edit form in SecurityAdmin
		$fields->removeByName('Groups');
		
		if(Permission::check('EDIT_PERMISSIONS')) {
			$groupsField = new TreeMultiselectField('Groups', false, 'Group');
			$fields->findOrMakeTab('Root.Groups', singleton('Group')->i18n_plural_name());
			$fields->addFieldToTab('Root.Groups', $groupsField);
			
			// Add permission field (readonly to avoid complicated group assignment logic).
			// This should only be available for existing records, as new records start
			// with no permissions until they have a group assignment anyway.
			if($this->ID) {
				$permissionsField = new PermissionCheckboxSetField_Readonly(
					'Permissions',
					singleton('Permission')->i18n_plural_name(),
					'Permission',
					'GroupID',
					// we don't want parent relationships, they're automatically resolved in the field
					$this->getManyManyComponents('Groups')
				);
				$fields->findOrMakeTab('Root.Permissions', singleton('Permission')->i18n_plural_name());
				$fields->addFieldToTab('Root.Permissions', $permissionsField);
			}
		}
		
		$defaultDateFormat = Zend_Locale_Format::getDateFormat($this->Locale);
		$dateFormatMap = array(
			'MMM d, yyyy' => Zend_Date::now()->toString('MMM d, yyyy'),
			'yyyy/MM/dd' => Zend_Date::now()->toString('yyyy/MM/dd'),
			'MM/dd/yyyy' => Zend_Date::now()->toString('MM/dd/yyyy'),
			'dd/MM/yyyy' => Zend_Date::now()->toString('dd/MM/yyyy'),
		);
		$dateFormatMap[$defaultDateFormat] = Zend_Date::now()->toString($defaultDateFormat)
			. sprintf(' (%s)', _t('Member.DefaultDateTime', 'default'));
		$mainFields->push(
			$dateFormatField = new Member_DatetimeOptionsetField(
				'DateFormat',
				$this->fieldLabel('DateFormat'),
				$dateFormatMap
			)
		);
		$dateFormatField->setValue($this->DateFormat);
		
		$defaultTimeFormat = Zend_Locale_Format::getTimeFormat($this->Locale);
		$timeFormatMap = array(
			'h:mm a' => Zend_Date::now()->toString('h:mm a'),
			'H:mm' => Zend_Date::now()->toString('H:mm'),
		);
		$timeFormatMap[$defaultTimeFormat] = Zend_Date::now()->toString($defaultTimeFormat)
			. sprintf(' (%s)', _t('Member.DefaultDateTime', 'default'));
		$mainFields->push(
			$timeFormatField = new Member_DatetimeOptionsetField(
				'TimeFormat',
				$this->fieldLabel('TimeFormat'),
				$timeFormatMap
			)
		);
		$timeFormatField->setValue($this->TimeFormat);
		
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
		
		$labels['FirstName'] = _t('Member.FIRSTNAME', 'First Name');
		$labels['Surname'] = _t('Member.SURNAME', 'Surname');
		$labels['Email'] = _t('Member.EMAIL', 'Email');
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
	
	/**
	 * Users can view their own record.
	 * Otherwise they'll need ADMIN or CMS_ACCESS_SecurityAdmin permissions.
	 * This is likely to be customized for social sites etc. with a looser permission model.
	 */
	function canView($member = null) {
		if(!$member || !(is_a($member, 'Member')) || is_numeric($member)) $member = Member::currentUser();
		
		// decorated access checks
		$results = $this->extend('canView', $member);
		if($results && is_array($results)) {
			if(!min($results)) return false;
			else return true;
		}
		
		// members can usually edit their own record
		if($member && $this->ID == $member->ID) return true;
		
		if(
			Permission::checkMember($member, 'ADMIN')
			|| Permission::checkMember($member, 'CMS_ACCESS_SecurityAdmin')
		) {
			return true;
		}
		
		return false;
	}
	
	/**
	 * Users can edit their own record.
	 * Otherwise they'll need ADMIN or CMS_ACCESS_SecurityAdmin permissions
	 */
	function canEdit($member = null) {
		if(!$member || !(is_a($member, 'Member')) || is_numeric($member)) $member = Member::currentUser();
		
		// decorated access checks
		$results = $this->extend('canEdit', $member);
		if($results && is_array($results)) {
			if(!min($results)) return false;
			else return true;
		}
		
		// No member found
		if(!($member && $member->exists())) return false;
		
		// If the requesting member is not an admin, but has access to manage members,
		// he still can't edit other members with ADMIN permission.
		// This is a bit weak, strictly speaking he shouldn't be allowed to
		// perform any action that could change the password on a member
		// with "higher" permissions than himself, but thats hard to determine.		
		if(!Permission::checkMember($member, 'ADMIN') && Permission::checkMember($this, 'ADMIN')) return false;

		return $this->canView($member);
	}
	
	/**
	 * Users can edit their own record.
	 * Otherwise they'll need ADMIN or CMS_ACCESS_SecurityAdmin permissions
	 */
	function canDelete($member = null) {
		if(!$member || !(is_a($member, 'Member')) || is_numeric($member)) $member = Member::currentUser();
		
		// decorated access checks
		$results = $this->extend('canDelete', $member);
		if($results && is_array($results)) {
			if(!min($results)) return false;
			else return true;
		}
		
		// No member found
		if(!($member && $member->exists())) return false;
		
		return $this->canEdit($member);
	}


	/**
	 * Validate this member object.
	 */
	function validate() {
		$valid = parent::validate();
		
		if(!$this->ID || $this->isChanged('Password')) {
			if($this->Password && self::$password_validator) {
				$valid->combineAnd(self::$password_validator->validate($this->Password, $this));
			}
		}

		if((!$this->ID && $this->SetPassword) || $this->isChanged('SetPassword')) {
			if($this->SetPassword && self::$password_validator) {
				$valid->combineAnd(self::$password_validator->validate($this->SetPassword, $this));
			}
		}

		return $valid;
	}	
	
	/**
	 * Change password. This will cause rehashing according to
	 * the `PasswordEncryption` property.
	 * 
	 * @param String $password Cleartext password
	 */
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
			$this->FailedLoginCount = $this->FailedLoginCount + 1;
			$this->write();
	
			if($this->FailedLoginCount >= self::$lock_out_after_incorrect_logins) {
				$this->LockedOutUntil = date('Y-m-d H:i:s', time() + 15*60);
				$this->write();
			}
		}
	}
	
	/**
	 * Get the HtmlEditorConfig for this user to be used in the CMS.
	 * This is set by the group. If multiple configurations are set,
	 * the one with the highest priority wins.
	 * 
	 * @return string
	 */
	function getHtmlEditorConfigForCMS() {
		$currentName = '';
		$currentPriority = 0;
		
		foreach($this->Groups() as $group) {
			$configName = $group->HtmlEditorConfig;
			if($configName) {
				$config = HtmlEditorConfig::get($group->HtmlEditorConfig);
				if($config && $config->getOption('priority') > $currentPriority) {
					$currentName = $configName;
				}
			}
		}
		
		// If can't find a suitable editor, just default to cms
		return $currentName ? $currentName : 'cms';
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
			return DataObject::get("Group", "\"ID\" IN (" . implode(",", $ids) . ")");
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
		$output = DataObject::get("Group", "\"Code\" IN ($list)");

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
		Requirements::javascript(SAPPHIRE_DIR . "/thirdparty/prototype/prototype.js");
		Requirements::javascript(SAPPHIRE_DIR . "/thirdparty/behaviour/behaviour.js");
		Requirements::javascript(SAPPHIRE_DIR . "/javascript/prototype_improvements.js");
		Requirements::javascript(THIRDPARTY_DIR . "/scriptaculous/scriptaculous.js");
		Requirements::javascript(THIRDPARTY_DIR . "/scriptaculous/controls.js");
		Requirements::javascript(SAPPHIRE_DIR . "/javascript/layout_helpers.js");
		Requirements::css(SAPPHIRE_DIR . "/css/Form.css");
		
		Requirements::css(SAPPHIRE_DIR . "/css/MemberProfileForm.css");
		
		
		$fields = $member->getCMSFields();
		$fields->push(new HiddenField('ID','ID',$member->ID));

		$actions = new FieldSet(
			new FormAction('dosave',_t('CMSMain.SAVE', 'Save'))
		);
		
		$validator = new Member_Validator();
		
		parent::__construct($controller, $name, $fields, $actions, $validator);
		
		$this->loadDataFrom($member);
	}
	
	function dosave($data, $form) {
		// don't allow ommitting or changing the ID
		if(!isset($data['ID']) || $data['ID'] != Member::currentUserID()) {
			return Director::redirectBack();
		}
		
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
	protected $subject = '';
	protected $body = '';

	function __construct() {
		parent::__construct();
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
    
    function __construct() {
		parent::__construct();
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
    
    function __construct() {
		parent::__construct();
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
		$member = DataObject::get_one('Member', "\"$identifierField\" = '{$SQL_identifierField}'");

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
				if(method_exists($extension, 'hasMethod') && $extension->hasMethod('updatePHP')) {
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
				if(method_exists($extension, 'hasMethod') && $extension->hasMethod('updateJavascript')) {
					$extension->updateJavascript($js, $this->form);
				}
			}
		}

		return $js;
	}

}
/**
 * @package sapphire
 * @subpackage security
 */
class Member_DatetimeOptionsetField extends OptionsetField {

	function Field() {
		Requirements::css(SAPPHIRE_DIR . '/css/MemberDatetimeOptionsetField.css');
		Requirements::javascript(THIRDPARTY_DIR . '/thirdparty/jquery/jquery.js');
		Requirements::javascript(SAPPHIRE_DIR . '/javascript/MemberDatetimeOptionsetField.js');

		$options = '';
		$odd = 0;
		$source = $this->getSource();

		foreach($source as $key => $value) {
			// convert the ID to an HTML safe value (dots are not replaced, as they are valid in an ID attribute)
			$itemID = $this->id() . '_' . preg_replace('/[^\.a-zA-Z0-9\-\_]/', '_', $key);
			if($key == $this->value) {
				$useValue = false;
				$checked = " checked=\"checked\"";
			} else {
				$checked = "";
			}

			$odd = ($odd + 1) % 2;
			$extraClass = $odd ? "odd" : "even";
			$extraClass .= " val" . preg_replace('/[^a-zA-Z0-9\-\_]/', '_', $key);
			$disabled = ($this->disabled || in_array($key, $this->disabledItems)) ? "disabled=\"disabled\"" : "";
			$ATT_key = Convert::raw2att($key);

			$options .= "<li class=\"".$extraClass."\"><input id=\"$itemID\" name=\"$this->name\" type=\"radio\" value=\"$key\"$checked $disabled class=\"radio\" /> <label title=\"$ATT_key\" for=\"$itemID\">$value</label></li>\n"; 
		}

		// Add "custom" input field
		$value = ($this->value && !array_key_exists($this->value, $this->source)) ? $this->value : null;
		$checked = ($value) ? " checked=\"checked\"" : '';
		$options .= "<li class=\"valCustom\">"
			. sprintf("<input id=\"%s_custom\" name=\"%s\" type=\"radio\" value=\"__custom__\" class=\"radio\" %s />", $itemID, $this->name, $checked)
			. sprintf('<label for="%s_custom">%s:</label>', $itemID, _t('MemberDatetimeOptionsetField.Custom', 'Custom'))
			. sprintf("<input class=\"customFormat\" name=\"%s_custom\" value=\"%s\" />\n", $this->name, $value)
			. sprintf("<input type=\"hidden\" class=\"formatValidationURL\" value=\"%s\" />", $this->Link() . '/validate');
		$options .= ($value) ? sprintf(
			'<span class="preview">(%s: "%s")</span>',
			_t('MemberDatetimeOptionsetField.Preview', 'Preview'),
			Zend_Date::now()->toString($value)
		) : '';
		$options .= "<a class=\"formattingHelpToggle\" href=\"#\">" . _t('MemberDatetimeOptionsetField.TOGGLEHELP', 'Toggle formatting help') . "</a>";
		$options .= "<div class=\"formattingHelpText\">";
		$options .= $this->getFormattingHelpText();
		$options .= "</div>";
		$options .= "</li>\n";

		$id = $this->id();
		return "<ul id=\"$id\" class=\"optionset {$this->extraClass()}\">\n$options</ul>\n";
	}

	/**
	 * @todo Put this text into a template?
	 */
	function getFormattingHelpText() {
		$output = '<ul>';
		$output .= '<li>YYYY = ' . _t('MemberDatetimeOptionsetField.FOURDIGITYEAR', 'Four-digit year', 40, 'Help text describing what "YYYY" means in ISO date formatting') . '</li>';
		$output .= '<li>YY = ' . _t('MemberDatetimeOptionsetField.TWODIGITYEAR', 'Two-digit year', 40, 'Help text describing what "YY" means in ISO date formatting') . '</li>';
		$output .= '<li>MMMM = ' . _t('MemberDatetimeOptionsetField.FULLNAMEMONTH', 'Full name of month (e.g. June)', 40, 'Help text describing what "MMMM" means in ISO date formatting') . '</li>';
		$output .= '<li>MMM = ' . _t('MemberDatetimeOptionsetField.SHORTMONTH', 'Short name of month (e.g. Jun)', 40, 'Help text letting describing what "MMM" means in ISO date formatting') . '</li>';
		$output .= '<li>MM = ' . _t('MemberDatetimeOptionsetField.TWODIGITMONTH', 'Two-digit month (01=January, etc.)', 40, 'Help text describing what "MM" means in ISO date formatting') . '</li>';
		$output .= '<li>M = ' . _t('MemberDatetimeOptionsetField.MONTHNOLEADING', 'Month digit without leading zero', 40, 'Help text describing what "M" means in ISO date formatting') . '</li>';
		$output .= '<li>dd = ' . _t('MemberDatetimeOptionsetField.TWODIGITDAY', 'Two-digit day of month', 40, 'Help text describing what "dd" means in ISO date formatting') . '</li>';
		$output .= '<li>d = ' . _t('MemberDatetimeOptionsetField.DAYNOLEADING', 'Day of month without leading zero', 40, 'Help text describing what "d" means in ISO date formatting') . '</li>';
		$output .= '<li>hh = ' . _t('MemberDatetimeOptionsetField.TWODIGITHOUR', 'Two digits of hour (00 through 23)', 40, 'Help text describing what "hh" means in ISO date formatting') . '</li>';
		$output .= '<li>h = ' . _t('MemberDatetimeOptionsetField.HOURNOLEADING', 'Hour without leading zero', 40, 'Help text describing what "h" means in ISO date formatting') . '</li>';
		$output .= '<li>mm = ' . _t('MemberDatetimeOptionsetField.TWODIGITMINUTE', 'Two digits of minute (00 through 59)', 40, 'Help text describing what "mm" means in ISO date formatting') . '</li>';
		$output .= '<li>m = ' . _t('MemberDatetimeOptionsetField.MINUTENOLEADING', 'Minute without leading zero', 40, 'Help text describing what "m" means in ISO date formatting') . '</li>';
		$output .= '<li>ss = ' . _t('MemberDatetimeOptionsetField.TWODIGITSECOND', 'Two digits of second (00 through 59)', 40, 'Help text describing what "ss" means in ISO date formatting') . '</li>';
		$output .= '<li>s = ' . _t('MemberDatetimeOptionsetField.DIGITSDECFRACTIONSECOND', 'One or more digits representing a decimal fraction of a second', 40, 'Help text describing what "s" means in ISO date formatting') . '</li>';
		$output .= '<li>a = ' . _t('MemberDatetimeOptionsetField.AMORPM', 'AM (Ante meridiem) or PM (Post meridiem)', 40, 'Help text describing what "a" means in ISO date formatting') . '</li>';
		$output .= '</ul>';
		return $output;
	}

	function setValue($value) {
		if($value == '__custom__') {
			$value = isset($_REQUEST[$this->name . '_custom']) ? $_REQUEST[$this->name . '_custom'] : null;
		}
		if($value) {
			parent::setValue($value);
		}
	}

	function validate() {
		$value = isset($_POST[$this->name . '_custom']) ? $_POST[$this->name . '_custom'] : null;
		if(!$value) return true; // no custom value, don't validate

		// Check that the current date with the date format is valid or not
		$validator = $this->form ? $this->form->getValidator() : null;
		require_once 'Zend/Date.php';
		$date = Zend_Date::now()->toString($value);
		$valid = Zend_Date::isDate($date, $value);
		if($valid) {
			return true;
		} else {
			if($validator) {
				$validator->validationError($this->name, _t('MemberDatetimeOptionsetField.DATEFORMATBAD',"Date format is invalid"), "validation", false);
			}
			return false;
		}
	}

}
