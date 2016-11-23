<?php
/**
 * The member class which represents the users of the system
 *
 * @package framework
 * @subpackage security
 *
 * @property string $FirstName
 * @property string $Surname
 * @property string $Email
 * @property string $Password
 * @property string $RememberLoginToken
 * @property string $TempIDHash
 * @property string $TempIDExpired
 * @property int $NumVisit @deprecated 4.0
 * @property string $LastVisited @deprecated 4.0
 * @property string $AutoLoginHash
 * @property string $AutoLoginExpired
 * @property string $PasswordEncryption
 * @property string $Salt
 * @property string $PasswordExpiry
 * @property string $LockedOutUntil
 * @property string $Locale
 * @property int $FailedLoginCount
 * @property string $DateFormat
 * @property string $TimeFormat
 */
class Member extends DataObject implements TemplateGlobalProvider {

	private static $db = array(
		'FirstName' => 'Varchar',
		'Surname' => 'Varchar',
		'Email' => 'Varchar(254)', // See RFC 5321, Section 4.5.3.1.3. (256 minus the < and > character)
		'TempIDHash' => 'Varchar(160)', // Temporary id used for cms re-authentication
		'TempIDExpired' => 'SS_Datetime', // Expiry of temp login
		'Password' => 'Varchar(160)',
		'RememberLoginToken' => 'Varchar(160)', // Note: this currently holds a hash, not a token.
		'NumVisit' => 'Int', // @deprecated 4.0
		'LastVisited' => 'SS_Datetime', // @deprecated 4.0
		'AutoLoginHash' => 'Varchar(160)', // Used to auto-login the user on password reset
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

	private static $belongs_many_many = array(
		'Groups' => 'Group',
	);

	private static $has_one = array();

	private static $has_many = array(
		'LoggedPasswords' => 'MemberPassword',
	);

	private static $many_many = array();

	private static $many_many_extraFields = array();

	private static $default_sort = '"Surname", "FirstName"';

	private static $indexes = array(
		'Email' => true,
		//Removed due to duplicate null values causing MSSQL problems
		//'AutoLoginHash' => Array('type'=>'unique', 'value'=>'AutoLoginHash', 'ignoreNulls'=>true)
	);

	/**
	 * @config
	 * @var boolean
	 */
	private static $notify_password_change = false;

	/**
	 * Flag whether or not member visits should be logged (count only)
	 *
	 * @deprecated 4.0
	 * @var bool
	 * @config
	 */
	private static $log_last_visited = true;

	/**
	 * Flag whether we should count number of visits
	 *
	 * @deprecated 4.0
	 * @var bool
	 * @config
	 */
	private static $log_num_visits = true;

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
	private static $searchable_fields = array(
		'FirstName',
		'Surname',
		'Email',
	);

	/**
	 * @config
	 * @var array
	 */
	private static $summary_fields = array(
		'FirstName',
		'Surname',
		'Email',
	);

	/**
	 * @config
	 * @var array
	 */
	private static $casting = array(
		'Name' => 'Varchar',
	);

	/**
	 * Internal-use only fields
	 *
	 * @config
	 * @var array
	 */
	private static $hidden_fields = array(
		'RememberLoginToken',
		'AutoLoginHash',
		'AutoLoginExpired',
		'PasswordEncryption',
		'PasswordExpiry',
		'LockedOutUntil',
		'TempIDHash',
		'TempIDExpired',
		'Salt',
		'NumVisit', // @deprecated 4.0
	);

	/**
	 * @config
	 * @var Array See {@link set_title_columns()}
	 */
	private static $title_format = null;

	/**
	 * The unique field used to identify this member.
	 * By default, it's "Email", but another common
	 * field could be Username.
	 *
	 * @config
	 * @var string
	 */
	private static $unique_identifier_field = 'Email';

	/**
	 * @config
	 * {@link PasswordValidator} object for validating user's password
	 */
	private static $password_validator = null;

	/**
	 * @config
	 * The number of days that a password should be valid for.
	 * By default, this is null, which means that passwords never expire
	 */
	private static $password_expiry_days = null;

	/**
	 * @config
	 * @var Int Number of incorrect logins after which
	 * the user is blocked from further attempts for the timespan
	 * defined in {@link $lock_out_delay_mins}.
	 */
	private static $lock_out_after_incorrect_logins = 10;

	/**
	 * @config
	 * @var integer Minutes of enforced lockout after incorrect password attempts.
	 * Only applies if {@link $lock_out_after_incorrect_logins} greater than 0.
	 */
	private static $lock_out_delay_mins = 15;

	/**
	 * @config
	 * @var String If this is set, then a session cookie with the given name will be set on log-in,
	 * and cleared on logout.
	 */
	private static $login_marker_cookie = null;

	/**
	 * Indicates that when a {@link Member} logs in, Member:session_regenerate_id()
	 * should be called as a security precaution.
	 *
	 * This doesn't always work, especially if you're trying to set session cookies
	 * across an entire site using the domain parameter to session_set_cookie_params()
	 *
	 * @config
	 * @var boolean
	 */
	private static $session_regenerate_id = true;


	/**
	 * Default lifetime of temporary ids.
	 *
	 * This is the period within which a user can be re-authenticated within the CMS by entering only their password
	 * and without losing their workspace.
	 *
	 * Any session expiration outside of this time will require them to login from the frontend using their full
	 * username and password.
	 *
	 * Defaults to 72 hours. Set to zero to disable expiration.
	 *
	 * @config
	 * @var int Lifetime in seconds
	 */
	private static $temp_id_lifetime = 259200;

	/**
	 * @deprecated 4.0 Use the "Member.session_regenerate_id" config setting instead
	 */
	public static function set_session_regenerate_id($bool) {
		Deprecation::notice('4.0', 'Use the "Member.session_regenerate_id" config setting instead');
		self::config()->session_regenerate_id = $bool;
	}

	/**
	 * Ensure the locale is set to something sensible by default.
	 */
	public function populateDefaults() {
		parent::populateDefaults();
		$this->Locale = i18n::get_closest_translation(i18n::get_locale());
	}

	public function requireDefaultRecords() {
		parent::requireDefaultRecords();
		// Default groups should've been built by Group->requireDefaultRecords() already
		static::default_admin();
	}

	/**
	 * Get the default admin record if it exists, or creates it otherwise if enabled
	 *
	 * @return Member
	 */
	public static function default_admin() {
		// Check if set
		if(!Security::has_default_admin()) return null;

		// Find or create ADMIN group
		singleton('Group')->requireDefaultRecords();
		$adminGroup = Permission::get_groups_by_permission('ADMIN')->First();

		// Find member
		$admin = Member::get()
			->filter('Email', Security::default_admin_username())
			->first();
		if(!$admin) {
			// 'Password' is not set to avoid creating
			// persistent logins in the database. See Security::setDefaultAdmin().
			// Set 'Email' to identify this as the default admin
			$admin = Member::create();
			$admin->FirstName = _t('Member.DefaultAdminFirstname', 'Default Admin');
			$admin->Email = Security::default_admin_username();
			$admin->write();
		}

		// Ensure this user is in the admin group
		if(!$admin->inGroup($adminGroup)) {
			// Add member to group instead of adding group to member
			// This bypasses the privilege escallation code in Member_GroupSet
			$adminGroup
				->DirectMembers()
				->add($admin);
		}

		return $admin;
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
	 * @deprecated 4.0 Use the "Member.login_marker_cookie" config setting instead
	 * @param $cookieName string The name of the cookie to set.
	 */
	public static function set_login_marker_cookie($cookieName) {
		Deprecation::notice('4.0', 'Use the "Member.login_marker_cookie" config setting instead');
		self::config()->login_marker_cookie = $cookieName;
	}

	/**
	 * Check if the passed password matches the stored one (if the member is not locked out).
	 *
	 * @param string $password
	 * @return ValidationResult
	 */
	public function checkPassword($password) {
		$result = $this->canLogIn();

		// Short-circuit the result upon failure, no further checks needed.
		if (!$result->valid()) {
			return $result;
		}

		// Allow default admin to login as self
		if($this->isDefaultAdmin() && Security::check_default_admin($this->Email, $password)) {
			return $result;
		}

		// Check a password is set on this member
		if(empty($this->Password) && $this->exists()) {
			$result->error(_t('Member.NoPassword','There is no password on this member.'));
			return $result;
		}

		$e = PasswordEncryptor::create_for_algorithm($this->PasswordEncryption);
		if(!$e->check($this->Password, $password, $this->Salt, $this)) {
			$result->error(_t (
				'Member.ERRORWRONGCRED',
				'The provided details don\'t seem to be correct. Please try again.'
			));
		}

		return $result;
	}

	/**
	 * Check if this user is the currently configured default admin
	 *
	 * @return bool
	 */
	public function isDefaultAdmin() {
		return Security::has_default_admin()
			&& $this->Email === Security::default_admin_username();
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
		$result = ValidationResult::create();

		if($this->isLockedOut()) {
			$result->error(
				_t(
					'Member.ERRORLOCKEDOUT2',
					'Your account has been temporarily disabled because of too many failed attempts at ' .
					'logging in. Please try again in {count} minutes.',
					null,
					array('count' => $this->config()->lock_out_delay_mins)
				)
			);
		}

		$this->extend('canLogIn', $result);
		return $result;
	}

	/**
	 * Returns true if this user is locked out
	 */
	public function isLockedOut() {
		return $this->LockedOutUntil && SS_Datetime::now()->Format('U') < strtotime($this->LockedOutUntil);
	}

	/**
	 * Regenerate the session_id.
	 * This wrapper is here to make it easier to disable calls to session_regenerate_id(), should you need to.
	 * They have caused problems in certain
	 * quirky problems (such as using the Windmill 0.3.6 proxy).
	 */
	public static function session_regenerate_id() {
		if(!self::config()->session_regenerate_id) return;

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
	 * @deprecated 4.0 Use the "Member.unique_identifier_field" config setting instead
	 * @return string
	 */
	public static function get_unique_identifier_field() {
		Deprecation::notice('4.0', 'Use the "Member.unique_identifier_field" config setting instead');
		return Member::config()->unique_identifier_field;
	}

	/**
	 * Set the field used for uniquely identifying a member
	 * in the database. {@see Member::$unique_identifier_field}
	 *
	 * @deprecated 4.0 Use the "Member.unique_identifier_field" config setting instead
	 * @param $field The field name to set as the unique field
	 */
	public static function set_unique_identifier_field($field) {
		Deprecation::notice('4.0', 'Use the "Member.unique_identifier_field" config setting instead');
		Member::config()->unique_identifier_field = $field;
	}

	/**
	 * Set a {@link PasswordValidator} object to use to validate member's passwords.
	 */
	public static function set_password_validator($pv) {
		self::$password_validator = $pv;
	}

	/**
	 * Returns the current {@link PasswordValidator}
	 */
	public static function password_validator() {
		return self::$password_validator;
	}

	/**
	 * Set the number of days that a password should be valid for.
	 * Set to null (the default) to have passwords never expire.
	 *
	 * @deprecated 4.0 Use the "Member.password_expiry_days" config setting instead
	 */
	public static function set_password_expiry($days) {
		Deprecation::notice('4.0', 'Use the "Member.password_expiry_days" config setting instead');
		self::config()->password_expiry_days = $days;
	}

	/**
	 * Configure the security system to lock users out after this many incorrect logins
	 *
	 * @deprecated 4.0 Use the "Member.lock_out_after_incorrect_logins" config setting instead
	 */
	public static function lock_out_after_incorrect_logins($numLogins) {
		Deprecation::notice('4.0', 'Use the "Member.lock_out_after_incorrect_logins" config setting instead');
		self::config()->lock_out_after_incorrect_logins = $numLogins;
	}


	public function isPasswordExpired() {
		if(!$this->PasswordExpiry) return false;
		return strtotime(date('Y-m-d')) >= strtotime($this->PasswordExpiry);
	}

	/**
	 * Logs this member in
	 *
	 * @param bool $remember If set to TRUE, the member will be logged in automatically the next time.
	 */
	public function logIn($remember = false) {
		$this->extend('beforeMemberLoggedIn');

		self::session_regenerate_id();

		Session::set("loggedInAs", $this->ID);
		// This lets apache rules detect whether the user has logged in
		if(Member::config()->login_marker_cookie) Cookie::set(Member::config()->login_marker_cookie, 1, 0);

		$this->addVisit();

		// Only set the cookie if autologin is enabled
		if($remember && Security::config()->autologin_enabled) {
			// Store the hash and give the client the cookie with the token.
			$generator = new RandomGenerator();
			$token = $generator->randomToken('sha1');
			$hash = $this->encryptWithUserSettings($token);
			$this->RememberLoginToken = $hash;
			Cookie::set('alc_enc', $this->ID . ':' . $token, 90, null, null, null, true);
		} else {
			$this->RememberLoginToken = null;
			Cookie::force_expiry('alc_enc');
		}

		// Clear the incorrect log-in count
		$this->registerSuccessfulLogin();

		// Don't set column if its not built yet (the login might be precursor to a /dev/build...)
		if(array_key_exists('LockedOutUntil', DB::field_list('Member'))) {
			$this->LockedOutUntil = null;
		}

		$this->regenerateTempID();

		$this->write();

		// Audit logging hook
		$this->extend('memberLoggedIn');
	}

	/**
	 * @deprecated 4.0
	 */
	public function addVisit() {
		if($this->config()->log_num_visits) {
			Deprecation::notice(
				'4.0',
				'Member::$NumVisit is deprecated. From 4.0 onwards you should implement this as a custom extension'
			);
			$this->NumVisit++;
		}
	}

	/**
	 * Trigger regeneration of TempID.
	 *
	 * This should be performed any time the user presents their normal identification (normally Email)
	 * and is successfully authenticated.
	 */
	public function regenerateTempID() {
		$generator = new RandomGenerator();
		$this->TempIDHash = $generator->randomToken('sha1');
		$this->TempIDExpired = self::config()->temp_id_lifetime
			? date('Y-m-d H:i:s', strtotime(SS_Datetime::now()->getValue()) + self::config()->temp_id_lifetime)
			: null;
		$this->write();
	}

	/**
	 * Check if the member ID logged in session actually
	 * has a database record of the same ID. If there is
	 * no logged in user, FALSE is returned anyway.
	 *
	 * @return boolean TRUE record found FALSE no record found
	 */
	public static function logged_in_session_exists() {
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
	public static function autoLogin() {
		// Don't bother trying this multiple times
		self::$_already_tried_to_auto_log_in = true;

		if(!Security::config()->autologin_enabled
			|| strpos(Cookie::get('alc_enc'), ':') === false
			|| Session::get("loggedInAs")
			|| !Security::database_is_ready()
		) {
			return;
		}

		list($uid, $token) = explode(':', Cookie::get('alc_enc'), 2);

		if (!$uid || !$token) {
			return;
		}

		$member = DataObject::get_by_id("Member", $uid);

		// check if autologin token matches
		if($member) {
			$hash = $member->encryptWithUserSettings($token);
			if(!$member->RememberLoginToken || $member->RememberLoginToken !== $hash) {
				$member = null;
			}
		}

		if($member) {
			self::session_regenerate_id();
			Session::set("loggedInAs", $member->ID);
			// This lets apache rules detect whether the user has logged in
			if(Member::config()->login_marker_cookie) {
				Cookie::set(Member::config()->login_marker_cookie, 1, 0, null, null, false, true);
			}

			$generator = new RandomGenerator();
			$token = $generator->randomToken('sha1');
			$hash = $member->encryptWithUserSettings($token);
			$member->RememberLoginToken = $hash;
			Cookie::set('alc_enc', $member->ID . ':' . $token, 90, null, null, false, true);

			$member->addVisit();
			$member->write();

			// Audit logging hook
			$member->extend('memberAutoLoggedIn');
		}
	}

	/**
	 * Logs this member out.
	 */
	public function logOut() {
		$this->extend('beforeMemberLoggedOut');

		Session::clear("loggedInAs");
		if(Member::config()->login_marker_cookie) Cookie::set(Member::config()->login_marker_cookie, null, 0);

		Session::destroy();

		$this->extend('memberLoggedOut');

		$this->RememberLoginToken = null;
		Cookie::force_expiry('alc_enc');

		// Switch back to live in order to avoid infinite loops when
		// redirecting to the login screen (if this login screen is versioned)
		Session::clear('readingMode');

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
		} while(DataObject::get_one('Member', array(
			'"Member"."AutoLoginHash"' => $hash
		)));

		$this->AutoLoginHash = $hash;
		$this->AutoLoginExpired = date('Y-m-d H:i:s', time() + (86400 * $lifetime));

		$this->write();

		return $token;
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
		$member = self::member_from_autologinhash($hash, false);
		return (bool)$member;
	}

	/**
	 * Return the member for the auto login hash
	 *
	 * @param string $hash The hash key
	 * @param bool $login Should the member be logged in?
	 *
	 * @return Member the matching member, if valid
	 * @return Member
	 */
	public static function member_from_autologinhash($hash, $login = false) {

		$nowExpression = DB::get_conn()->now();
		$member = DataObject::get_one('Member', array(
			"\"Member\".\"AutoLoginHash\"" => $hash,
			"\"Member\".\"AutoLoginExpired\" > $nowExpression" // NOW() can't be parameterised
		));

		if($login && $member) $member->logIn();

		return $member;
	}

	/**
	 * Find a member record with the given TempIDHash value
	 *
	 * @param string $tempid
	 * @return Member
	 */
	public static function member_from_tempid($tempid) {
		$members = Member::get()
			->filter('TempIDHash', $tempid);

		// Exclude expired
		if(static::config()->temp_id_lifetime) {
			$members = $members->filter('TempIDExpired:GreaterThan', SS_Datetime::now()->getValue());
		}

		return $members->first();
	}

	/**
	 * Returns the fields for the member form - used in the registration/profile module.
	 * It should return fields that are editable by the admin and the logged-in user.
	 *
	 * @return FieldList Returns a {@link FieldList} containing the fields for
	 *                   the member form.
	 */
	public function getMemberFormFields() {
		$fields = parent::getFrontendFields();

		$fields->replaceField('Password', $this->getMemberPasswordField());

		$fields->replaceField('Locale', new DropdownField (
			'Locale',
			$this->fieldLabel('Locale'),
			i18n::get_existing_translations()
		));

		$fields->removeByName(static::config()->hidden_fields);
		$fields->removeByName('LastVisited');
		$fields->removeByName('FailedLoginCount');


		$this->extend('updateMemberFormFields', $fields);
		return $fields;
	}

	/**
	 * Builds "Change / Create Password" field for this member
	 *
	 * @return ConfirmedPasswordField
	 */
	public function getMemberPasswordField() {
		$editingPassword = $this->isInDB();
		$label = $editingPassword
			? _t('Member.EDIT_PASSWORD', 'New Password')
			: $this->fieldLabel('Password');
		/** @var ConfirmedPasswordField $password */
		$password = ConfirmedPasswordField::create(
			'Password',
			$label,
			null,
			null,
			$editingPassword
		);

		// If editing own password, require confirmation of existing
		if($editingPassword && $this->ID == Member::currentUserID()) {
			$password->setRequireExistingPassword(true);
		}

		$password->setCanBeEmpty(true);
		$this->extend('updateMemberPasswordField', $password);
		return $password;
	}


	/**
	 * Returns the {@link RequiredFields} instance for the Member object. This
	 * Validator is used when saving a {@link CMSProfileController} or added to
	 * any form responsible for saving a users data.
	 *
	 * To customize the required fields, add a {@link DataExtension} to member
	 * calling the `updateValidator()` method.
	 *
	 * @return Member_Validator
	 */
	public function getValidator() {
		$validator = Injector::inst()->create('Member_Validator');
		$validator->setForMember($this);
		$this->extend('updateValidator', $validator);

		return $validator;
	}


	/**
	 * Returns the current logged in user
	 *
	 * @return Member|null
	 */
	public static function currentUser() {
		$id = Member::currentUserID();

		if($id) {
			return DataObject::get_by_id('Member', $id) ?: null;
		}
	}

	/**
	 * Get the ID of the current logged in user
	 *
	 * @return int Returns the ID of the current logged in user or 0.
	 */
	public static function currentUserID() {
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
	public static function create_new_password() {
		$words = Config::inst()->get('Security', 'word_list');

		if($words && file_exists($words)) {
			$words = file($words);

			list($usec, $sec) = explode(' ', microtime());
			srand($sec + ((float) $usec * 100000));

			$word = trim($words[rand(0,sizeof($words)-1)]);
			$number = rand(10,999);

			return $word . $number;
		} else {
			$random = rand();
			$string = md5($random);
			$output = substr($string, 0, 8);
			return $output;
		}
	}

	/**
	 * Event handler called before writing to the database.
	 */
	public function onBeforeWrite() {
		if($this->SetPassword) $this->Password = $this->SetPassword;

		// If a member with the same "unique identifier" already exists with a different ID, don't allow merging.
		// Note: This does not a full replacement for safeguards in the controller layer (e.g. in a registration form),
		// but rather a last line of defense against data inconsistencies.
		$identifierField = Member::config()->unique_identifier_field;
		if($this->$identifierField) {

			// Note: Same logic as Member_Validator class
			$filter = array("\"$identifierField\"" => $this->$identifierField);
			if($this->ID) {
				$filter[] = array('"Member"."ID" <> ?' => $this->ID);
			}
			$existingRecord = DataObject::get_one('Member', $filter);

			if($existingRecord) {
				throw new ValidationException(ValidationResult::create(false, _t(
					'Member.ValidationIdentifierFailed',
					'Can\'t overwrite existing member #{id} with identical identifier ({name} = {value}))',
					'Values in brackets show "fieldname = value", usually denoting an existing email address',
					array(
						'id' => $existingRecord->ID,
						'name' => $identifierField,
						'value' => $this->$identifierField
					)
				)));
			}
		}

		// We don't send emails out on dev/tests sites to prevent accidentally spamming users.
		// However, if TestMailer is in use this isn't a risk.
		if(
			(Director::isLive() || Email::mailer() instanceof TestMailer)
			&& $this->isChanged('Password')
			&& $this->record['Password']
			&& $this->config()->notify_password_change
		) {
			$e = Member_ChangePasswordEmail::create();
			$e->populateTemplate($this);
			$e->setTo($this->Email);
			$e->send();
		}

		// The test on $this->ID is used for when records are initially created.
		// Note that this only works with cleartext passwords, as we can't rehash
		// existing passwords.
		if((!$this->ID && $this->Password) || $this->isChanged('Password')) {
			//reset salt so that it gets regenerated - this will invalidate any persistant login cookies
			// or other information encrypted with this Member's settings (see self::encryptWithUserSettings)
			$this->Salt = '';
			// Password was changed: encrypt the password according the settings
			$encryption_details = Security::encrypt_password(
				$this->Password, // this is assumed to be cleartext
				$this->Salt,
				($this->PasswordEncryption) ?
					$this->PasswordEncryption : Security::config()->password_encryption_algorithm,
				$this
			);

			// Overwrite the Password property with the hashed value
			$this->Password = $encryption_details['password'];
			$this->Salt = $encryption_details['salt'];
			$this->PasswordEncryption = $encryption_details['algorithm'];

			// If we haven't manually set a password expiry
			if(!$this->isChanged('PasswordExpiry')) {
				// then set it for us
				if(self::config()->password_expiry_days) {
					$this->PasswordExpiry = date('Y-m-d', time() + 86400 * self::config()->password_expiry_days);
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

	public function onAfterWrite() {
		parent::onAfterWrite();

		Permission::flush_permission_cache();

		if($this->isChanged('Password')) {
			MemberPassword::log($this);
		}
	}

	public function onAfterDelete() {
		parent::onAfterDelete();

		//prevent orphaned records remaining in the DB
		$this->deletePasswordLogs();
	}

	/**
	 * Delete the MemberPassword objects that are associated to this user
	 *
	 * @return self
	 */
	protected function deletePasswordLogs() {
		foreach ($this->LoggedPasswords() as $password) {
			$password->delete();
			$password->destroy();
		}
		return $this;
	}

	/**
	 * Filter out admin groups to avoid privilege escalation,
	 * If any admin groups are requested, deny the whole save operation.
	 *
	 * @param Array $ids Database IDs of Group records
	 * @return boolean True if the change can be accepted
	 */
	public function onChangeGroups($ids) {
		// unless the current user is an admin already OR the logged in user is an admin
		if(Permission::check('ADMIN') || Permission::checkMember($this, 'ADMIN')) {
			return true;
		}

		// If there are no admin groups in this set then it's ok
		$adminGroups = Permission::get_groups_by_permission('ADMIN');
		$adminGroupIDs = ($adminGroups) ? $adminGroups->column('ID') : array();
		return count(array_intersect($ids, $adminGroupIDs)) == 0;
	}


	/**
	 * Check if the member is in one of the given groups.
	 *
	 * @param array|SS_List $groups Collection of {@link Group} DataObjects to check
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
			$groupCheckObj = DataObject::get_one('Group', array(
				'"Group"."Code"' => $group
			));
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
		$group = DataObject::get_one('Group', array(
			'"Group"."Code"' => $groupcode
		));

		if($group) {
			$this->Groups()->add($group);
		} else {
			if(!$title) $title = $groupcode;

			$group = new Group();
			$group->Code = $groupcode;
			$group->Title = $title;
			$group->write();

			$this->Groups()->add($group);
		}
	}

	/**
	 * Removes a member from a group.
	 *
	 * @param string $groupcode
	 */
	public function removeFromGroupByCode($groupcode) {
		$group = Group::get()->filter(array('Code' => $groupcode))->first();

		if($group) {
			$this->Groups()->remove($group);
		}
	}

	/**
	 * @param Array $columns Column names on the Member record to show in {@link getTitle()}.
	 * @param String $sep Separator
	 */
	public static function set_title_columns($columns, $sep = ' ') {
		if (!is_array($columns)) $columns = array($columns);
		self::config()->title_format = array('columns' => $columns, 'sep' => $sep);
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
		$format = $this->config()->title_format;
		if ($format) {
			$values = array();
			foreach($format['columns'] as $col) {
				$values[] = $this->getField($col);
			}
			return join($format['sep'], $values);
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
	public static function get_title_sql($tableName = 'Member') {
		// This should be abstracted to SSDatabase concatOperator or similar.
		$op = (DB::get_conn() instanceof MSSQLDatabase) ? " + " : " || ";

		$format = self::config()->title_format;
		if ($format) {
			$columnsWithTablename = array();
			foreach($format['columns'] as $column) {
				$columnsWithTablename[] = "\"$tableName\".\"$column\"";
			}

			return "(".join(" $op '".$format['sep']."' $op ", $columnsWithTablename).")";
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
		} else {
			return Config::inst()->get('i18n', 'date_format');
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
		} else {
			return Config::inst()->get('i18n', 'time_format');
		}
	}

	//---------------------------------------------------------------------//


	/**
	 * Get a "many-to-many" map that holds for all members their group memberships,
	 * including any parent groups where membership is implied.
	 * Use {@link DirectGroups()} to only retrieve the group relations without inheritance.
	 *
	 * @todo Push all this logic into Member_GroupSet's getIterator()?
	 * @return Member_Groupset
	 */
	public function Groups() {
		$groups = Member_GroupSet::create('Group', 'Group_Members', 'GroupID', 'MemberID');
		$groups = $groups->forForeignID($this->ID);

		$this->extend('updateGroups', $groups);

		return $groups;
	}

	/**
	 * @return ManyManyList
	 */
	public function DirectGroups() {
		return $this->getManyManyComponents('Groups');
	}

	/**
	 * Get a member SQLMap of members in specific groups
	 *
	 * If no $groups is passed, all members will be returned
	 *
	 * @param mixed $groups - takes a SS_List, an array or a single Group.ID
	 * @return SQLMap Returns an SQLMap that returns all Member data.
	 * @see map()
	 */
	public static function map_in_groups($groups = null) {
		$groupIDList = array();

		if($groups instanceof SS_List) {
			foreach( $groups as $group ) {
				$groupIDList[] = $group->ID;
			}
		} elseif(is_array($groups)) {
			$groupIDList = $groups;
		} elseif($groups) {
			$groupIDList[] = $groups;
		}

		// No groups, return all Members
		if(!$groupIDList) {
			return Member::get()->sort(array('Surname'=>'ASC', 'FirstName'=>'ASC'))->map();
		}

		$membersList = new ArrayList();
		// This is a bit ineffective, but follow the ORM style
		foreach(Group::get()->byIDs($groupIDList) as $group) {
			$membersList->merge($group->Members());
		}

		$membersList->removeDuplicates('ID');
		return $membersList->map();
	}


	/**
	 * Get a map of all members in the groups given that have CMS permissions
	 *
	 * If no groups are passed, all groups with CMS permissions will be used.
	 *
	 * @param array $groups Groups to consider or NULL to use all groups with
	 *                      CMS permissions.
	 * @return SS_Map Returns a map of all members in the groups given that
	 *                have CMS permissions.
	 */
	public static function mapInCMSGroups($groups = null) {
		if(!$groups || $groups->Count() == 0) {
			$perms = array('ADMIN', 'CMS_ACCESS_AssetAdmin');

			if(class_exists('CMSMain')) {
				$cmsPerms = singleton('CMSMain')->providePermissions();
			} else {
				$cmsPerms = singleton('LeftAndMain')->providePermissions();
			}

			if(!empty($cmsPerms)) {
				$perms = array_unique(array_merge($perms, array_keys($cmsPerms)));
			}

			$permsClause = DB::placeholders($perms);
			$groups = DataObject::get('Group')
				->innerJoin("Permission", '"Permission"."GroupID" = "Group"."ID"')
				->where(array(
					"\"Permission\".\"Code\" IN ($permsClause)" => $perms
				));
		}

		$groupIDList = array();

		if(is_a($groups, 'SS_List')) {
			foreach($groups as $group) {
				$groupIDList[] = $group->ID;
			}
		} elseif(is_array($groups)) {
			$groupIDList = $groups;
		}

		$members = Member::get()
			->innerJoin("Group_Members", '"Group_Members"."MemberID" = "Member"."ID"')
			->innerJoin("Group", '"Group"."ID" = "Group_Members"."GroupID"');
		if($groupIDList) {
			$groupClause = DB::placeholders($groupIDList);
			$members = $members->where(array(
				"\"Group\".\"ID\" IN ($groupClause)" => $groupIDList
			));
		}

		return $members->sort('"Member"."Surname", "Member"."FirstName"')->map();
	}


	/**
	 * Get the groups in which the member is NOT in
	 *
	 * When passed an array of groups, and a component set of groups, this
	 * function will return the array of groups the member is NOT in.
	 *
	 * @param array $groupList An array of group code names.
	 * @param array $memberGroups A component set of groups (if set to NULL,
	 *                            $this->groups() will be used)
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
	 * Return a {@link FieldList} of fields that would appropriate for editing
	 * this member.
	 *
	 * @return FieldList Return a FieldList of fields that would appropriate for
	 *                   editing this member.
	 */
	public function getCMSFields() {
		require_once 'Zend/Date.php';

		$self = $this;
		$this->beforeUpdateCMSFields(function(FieldList $fields) use ($self) {
			/** @var FieldList $mainFields */
			$mainFields = $fields->fieldByName("Root")->fieldByName("Main")->getChildren();

			// Build change password field
			$mainFields->replaceField('Password', $self->getMemberPasswordField());

			$mainFields->replaceField('Locale', new DropdownField(
				"Locale",
				_t('Member.INTERFACELANG', "Interface Language", 'Language of the CMS'),
				i18n::get_existing_translations()
			));

			$mainFields->removeByName($self->config()->hidden_fields);

			// make sure that the "LastVisited" field exists
			// it may have been removed using $self->config()->hidden_fields
			if($mainFields->fieldByName("LastVisited")){
			$mainFields->makeFieldReadonly('LastVisited');
			}

			if( ! $self->config()->lock_out_after_incorrect_logins) {
				$mainFields->removeByName('FailedLoginCount');
			}


			// Groups relation will get us into logical conflicts because
			// Members are displayed within  group edit form in SecurityAdmin
			$fields->removeByName('Groups');

			// Members shouldn't be able to directly view/edit logged passwords
			$fields->removeByName('LoggedPasswords');

			if(Permission::check('EDIT_PERMISSIONS')) {
				$groupsMap = array();
				foreach(Group::get() as $group) {
					// Listboxfield values are escaped, use ASCII char instead of &raquo;
					$groupsMap[$group->ID] = $group->getBreadcrumbs(' > ');
				}
				asort($groupsMap);
				$fields->addFieldToTab('Root.Main',
					ListboxField::create('DirectGroups', singleton('Group')->i18n_plural_name())
						->setMultiple(true)
						->setSource($groupsMap)
						->setAttribute(
							'data-placeholder',
							_t('Member.ADDGROUP', 'Add group', 'Placeholder text for a dropdown')
						)
				);


				// Add permission field (readonly to avoid complicated group assignment logic).
				// This should only be available for existing records, as new records start
				// with no permissions until they have a group assignment anyway.
				if($self->ID) {
					$permissionsField = new PermissionCheckboxSetField_Readonly(
						'Permissions',
						false,
						'Permission',
						'GroupID',
						// we don't want parent relationships, they're automatically resolved in the field
						$self->getManyManyComponents('Groups')
					);
					$fields->findOrMakeTab('Root.Permissions', singleton('Permission')->i18n_plural_name());
					$fields->addFieldToTab('Root.Permissions', $permissionsField);
				}
			}

			$permissionsTab = $fields->fieldByName("Root")->fieldByName('Permissions');
			if($permissionsTab) $permissionsTab->addExtraClass('readonly');

			$defaultDateFormat = Zend_Locale_Format::getDateFormat(new Zend_Locale($self->Locale));
			$dateFormatMap = array(
				'MMM d, yyyy' => Zend_Date::now()->toString('MMM d, yyyy'),
				'yyyy/MM/dd' => Zend_Date::now()->toString('yyyy/MM/dd'),
				'MM/dd/yyyy' => Zend_Date::now()->toString('MM/dd/yyyy'),
				'dd/MM/yyyy' => Zend_Date::now()->toString('dd/MM/yyyy'),
			);
			$dateFormatMap[$defaultDateFormat] = Zend_Date::now()->toString($defaultDateFormat)
				. sprintf(' (%s)', _t('Member.DefaultDateTime', 'default'));
			$mainFields->push(
				$dateFormatField = new MemberDatetimeOptionsetField(
					'DateFormat',
					$self->fieldLabel('DateFormat'),
					$dateFormatMap
				)
			);
			$dateFormatField->setValue($self->DateFormat);

			$defaultTimeFormat = Zend_Locale_Format::getTimeFormat(new Zend_Locale($self->Locale));
			$timeFormatMap = array(
				'h:mm a' => Zend_Date::now()->toString('h:mm a'),
				'H:mm' => Zend_Date::now()->toString('H:mm'),
			);
			$timeFormatMap[$defaultTimeFormat] = Zend_Date::now()->toString($defaultTimeFormat)
				. sprintf(' (%s)', _t('Member.DefaultDateTime', 'default'));
			$mainFields->push(
				$timeFormatField = new MemberDatetimeOptionsetField(
					'TimeFormat',
					$self->fieldLabel('TimeFormat'),
					$timeFormatMap
				)
			);
			$timeFormatField->setValue($self->TimeFormat);
		});

		return parent::getCMSFields();
	}

	/**
	 *
	 * @param boolean $includerelations a boolean value to indicate if the labels returned include relation fields
	 *
	 */
	public function fieldLabels($includerelations = true) {
		$labels = parent::fieldLabels($includerelations);

		$labels['FirstName'] = _t('Member.FIRSTNAME', 'First Name');
		$labels['Surname'] = _t('Member.SURNAME', 'Surname');
		$labels['Email'] = _t('Member.EMAIL', 'Email');
		$labels['Password'] = _t('Member.db_Password', 'Password');
		$labels['NumVisit'] = _t('Member.db_NumVisit', 'Number of Visits');
		$labels['LastVisited'] = _t('Member.db_LastVisited', 'Last Visited Date');
		$labels['PasswordExpiry'] = _t('Member.db_PasswordExpiry', 'Password Expiry Date', 'Password expiry date');
		$labels['LockedOutUntil'] = _t('Member.db_LockedOutUntil', 'Locked out until', 'Security related date');
		$labels['Locale'] = _t('Member.db_Locale', 'Interface Locale');
		$labels['DateFormat'] = _t('Member.DATEFORMAT', 'Date format');
		$labels['TimeFormat'] = _t('Member.TIMEFORMAT', 'Time format');
		if($includerelations){
			$labels['Groups'] = _t('Member.belongs_many_many_Groups', 'Groups',
				'Security Groups this member belongs to');
		}
		return $labels;
	}

	/**
	 * Users can view their own record.
	 * Otherwise they'll need ADMIN or CMS_ACCESS_SecurityAdmin permissions.
	 * This is likely to be customized for social sites etc. with a looser permission model.
	 */
	public function canView($member = null) {
		if(!$member || !(is_a($member, 'Member')) || is_numeric($member)) $member = Member::currentUser();

		// extended access checks
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
	public function canEdit($member = null) {
		if(!$member || !(is_a($member, 'Member')) || is_numeric($member)) $member = Member::currentUser();

		// extended access checks
		$results = $this->extend('canEdit', $member);
		if($results && is_array($results)) {
			if(!min($results)) return false;
			else return true;
		}

		// No member found
		if(!($member && $member->exists())) return false;

		// If the requesting member is not an admin, but has access to manage members,
		// they still can't edit other members with ADMIN permission.
		// This is a bit weak, strictly speaking they shouldn't be allowed to
		// perform any action that could change the password on a member
		// with "higher" permissions than himself, but thats hard to determine.
		if(!Permission::checkMember($member, 'ADMIN') && Permission::checkMember($this, 'ADMIN')) return false;

		return $this->canView($member);
	}

	/**
	 * Users can edit their own record.
	 * Otherwise they'll need ADMIN or CMS_ACCESS_SecurityAdmin permissions
	 */
	public function canDelete($member = null) {
		if(!$member || !(is_a($member, 'Member')) || is_numeric($member)) $member = Member::currentUser();

		// extended access checks
		$results = $this->extend('canDelete', $member);
		if($results && is_array($results)) {
			if(!min($results)) return false;
			else return true;
		}

		// No member found
		if(!($member && $member->exists())) return false;

		// Members are not allowed to remove themselves,
		// since it would create inconsistencies in the admin UIs.
		if($this->ID && $member->ID == $this->ID) return false;

		return $this->canEdit($member);
	}


	/**
	 * Validate this member object.
	 */
	public function validate() {
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
	public function changePassword($password) {
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
	public function registerFailedLogin() {
		if(self::config()->lock_out_after_incorrect_logins) {
			// Keep a tally of the number of failed log-ins so that we can lock people out
			$this->FailedLoginCount = $this->FailedLoginCount + 1;

			if($this->FailedLoginCount >= self::config()->lock_out_after_incorrect_logins) {
				$lockoutMins = self::config()->lock_out_delay_mins;
				$this->LockedOutUntil = date('Y-m-d H:i:s', SS_Datetime::now()->Format('U') + $lockoutMins*60);
				$this->FailedLoginCount = 0;
			}
		}
		$this->extend('registerFailedLogin');
		$this->write();
	}

	/**
	 * Tell this member that a successful login has been made
	 */
	public function registerSuccessfulLogin() {
		if(self::config()->lock_out_after_incorrect_logins) {
			// Forgive all past login failures
			$this->FailedLoginCount = 0;
			$this->write();
		}
	}
	/**
	 * Get the HtmlEditorConfig for this user to be used in the CMS.
	 * This is set by the group. If multiple configurations are set,
	 * the one with the highest priority wins.
	 *
	 * @return string
	 */
	public function getHtmlEditorConfigForCMS() {
		$currentName = '';
		$currentPriority = 0;

		foreach($this->Groups() as $group) {
			$configName = $group->HtmlEditorConfig;
			if($configName) {
				$config = HtmlEditorConfig::get($group->HtmlEditorConfig);
				if($config && $config->getOption('priority') > $currentPriority) {
					$currentName = $configName;
					$currentPriority = $config->getOption('priority');
				}
			}
		}

		// If can't find a suitable editor, just default to cms
		return $currentName ? $currentName : 'cms';
	}

	public static function get_template_global_variables() {
		return array(
			'CurrentMember' => 'currentUser',
			'currentUser',
		);
	}
}

/**
 * Represents a set of Groups attached to a member.
 * Handles the hierarchy logic.
 * @package framework
 * @subpackage security
 */
class Member_GroupSet extends ManyManyList {

	protected function linkJoinTable() {
		// Do not join the table directly
		if($this->extraFields) {
			user_error('Member_GroupSet does not support many_many_extraFields', E_USER_ERROR);
		}
	}

	/**
	 * Link this group set to a specific member.
	 *
	 * Recursively selects all groups applied to this member, as well as any
	 * parent groups of any applied groups
	 *
	 * @param array|integer $id (optional) An ID or an array of IDs - if not provided, will use the current
	 * ids as per getForeignID
	 * @return array Condition In array(SQL => parameters format)
	 */
	public function foreignIDFilter($id = null) {
		if ($id === null) $id = $this->getForeignID();

		// Find directly applied groups
		$manyManyFilter = parent::foreignIDFilter($id);
		$query = new SQLQuery('"Group_Members"."GroupID"', '"Group_Members"', $manyManyFilter);
		$groupIDs = $query->execute()->column();

		// Get all ancestors, iteratively merging these into the master set
		$allGroupIDs = array();
		while($groupIDs) {
			$allGroupIDs = array_merge($allGroupIDs, $groupIDs);
			$groupIDs = DataObject::get("Group")->byIDs($groupIDs)->column("ParentID");
			$groupIDs = array_filter($groupIDs);
		}

		// Add a filter to this DataList
		if(!empty($allGroupIDs)) {
			$allGroupIDsPlaceholders = DB::placeholders($allGroupIDs);
			return array("\"Group\".\"ID\" IN ($allGroupIDsPlaceholders)" => $allGroupIDs);
		} else {
			return array('"Group"."ID"' => 0);
		}
	}

	public function foreignIDWriteFilter($id = null) {
		// Use the ManyManyList::foreignIDFilter rather than the one
		// in this class, otherwise we end up selecting all inherited groups
		return parent::foreignIDFilter($id);
	}

	public function add($item, $extraFields = null) {
		// Get Group.ID
		$itemID = null;
		if(is_numeric($item)) {
			$itemID = $item;
		} else if($item instanceof Group) {
			$itemID = $item->ID;
		}

		// Check if this group is allowed to be added
		if($this->canAddGroups(array($itemID))) {
			parent::add($item, $extraFields);
		}
	}

	/**
	 * Determine if the following groups IDs can be added
	 *
	 * @param array $itemIDs
	 * @return boolean
	 */
	protected function canAddGroups($itemIDs) {
		if(empty($itemIDs)) {
			return true;
		}
		$member = $this->getMember();
		return empty($member) || $member->onChangeGroups($itemIDs);
	}

	/**
	 * Get foreign member record for this relation
	 *
	 * @return Member
	 */
	protected function getMember() {
		$id = $this->getForeignID();
		if($id) {
			return DataObject::get_by_id('Member', $id);
		}
	}
}

/**
 * Class used as template to send an email saying that the password has been
 * changed.
 *
 * @package framework
 * @subpackage security
 */
class Member_ChangePasswordEmail extends Email {

	protected $from = '';   // setting a blank from address uses the site's default administrator email
	protected $subject = '';
	protected $ss_template = 'ChangePasswordEmail';

	public function __construct() {
		parent::__construct();

		$this->subject = _t('Member.SUBJECTPASSWORDCHANGED', "Your password has been changed", 'Email subject');
	}
}



/**
 * Class used as template to send the forgot password email
 *
 * @package framework
 * @subpackage security
 */
class Member_ForgotPasswordEmail extends Email {
	protected $from = '';  // setting a blank from address uses the site's default administrator email
	protected $subject = '';
	protected $ss_template = 'ForgotPasswordEmail';

	public function __construct() {
		parent::__construct();

		$this->subject = _t('Member.SUBJECTPASSWORDRESET', "Your password reset link", 'Email subject');
	}
}

/**
 * Member Validator
 *
 * Custom validation for the Member object can be achieved either through an
 * {@link DataExtension} on the Member_Validator object or, by specifying a subclass of
 * {@link Member_Validator} through the {@link Injector} API.
 * The Validator can also be modified by adding an Extension to Member and implement the
 * <code>updateValidator</code> hook.
 * {@see Member::getValidator()}
 *
 * Additional required fields can also be set via config API, eg.
 * <code>
 * Member_Validator:
 *   customRequired:
 *     - Surname
 * </code>
 *
 * @package framework
 * @subpackage security
 */
class Member_Validator extends RequiredFields
{
	/**
	 * Fields that are required by this validator
	 * @config
	 * @var array
	 */
	protected $customRequired = array(
		'FirstName',
		'Email'
	);

	/**
	 * Determine what member this validator is meant for
	 * @var Member
	 */
	protected $forMember = null;

	/**
	 * Constructor
	 */
	public function __construct() {
		$required = func_get_args();

		if(isset($required[0]) && is_array($required[0])) {
			$required = $required[0];
		}

		$required = array_merge($required, $this->customRequired);

		// check for config API values and merge them in
		$config = $this->config()->customRequired;
		if(is_array($config)){
			$required = array_merge($required, $config);
		}

		parent::__construct(array_unique($required));
	}

	/**
	 * Get the member this validator applies to.
	 * @return Member
	 */
	public function getForMember()
	{
		return $this->forMember;
	}

	/**
	 * Set the Member this validator applies to.
	 * @param Member $value
	 * @return $this
	 */
	public function setForMember(Member $value)
	{
		$this->forMember = $value;
		return $this;
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
	public function php($data)
	{
		$valid = parent::php($data);

		$identifierField = (string)Member::config()->unique_identifier_field;

		// Only validate identifier field if it's actually set. This could be the case if
		// somebody removes `Email` from the list of required fields.
		if(isset($data[$identifierField])){
			$id = isset($data['ID']) ? (int)$data['ID'] : 0;
			if(!$id && ($ctrl = $this->form->getController())){
				// get the record when within GridField (Member editing page in CMS)
				if($ctrl instanceof GridFieldDetailForm_ItemRequest && $record = $ctrl->getRecord()){
					$id = $record->ID;
				}
			}

			// If there's no ID passed via controller or form-data, use the assigned member (if available)
			if(!$id && ($member = $this->getForMember())){
				$id = $member->exists() ? $member->ID : 0;
			}

			// set the found ID to the data array, so that extensions can also use it
			$data['ID'] = $id;

			$members = Member::get()->filter($identifierField, $data[$identifierField]);
			if($id) {
				$members = $members->exclude('ID', $id);
			}

			if($members->count() > 0) {
				$this->validationError(
					$identifierField,
					_t(
						'Member.VALIDATIONMEMBEREXISTS',
						'A member already exists with the same {identifier}',
						array('identifier' => Member::singleton()->fieldLabel($identifierField))
					),
					'required'
				);
				$valid = false;
			}
		}


		// Execute the validators on the extensions
		$results = $this->extend('updatePHP', $data, $this->form);
		$results[] = $valid;
		return min($results);
	}
}
