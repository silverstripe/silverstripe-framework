<?php

namespace SilverStripe\Security;

use IntlDateFormatter;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use SilverStripe\Admin\LeftAndMain;
use SilverStripe\CMS\Controllers\CMSMain;
use SilverStripe\Control\Director;
use SilverStripe\Control\Email\Email;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Convert;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Dev\TestMailer;
use SilverStripe\Forms\CheckboxField;
use SilverStripe\Forms\ConfirmedPasswordField;
use SilverStripe\Forms\DropdownField;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\HTMLEditor\HTMLEditorConfig;
use SilverStripe\Forms\ListboxField;
use SilverStripe\Forms\Tab;
use SilverStripe\Forms\TabSet;
use SilverStripe\i18n\i18n;
use SilverStripe\ORM\ArrayList;
use SilverStripe\ORM\DataList;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\DB;
use SilverStripe\ORM\FieldType\DBDatetime;
use SilverStripe\ORM\HasManyList;
use SilverStripe\ORM\ManyManyList;
use SilverStripe\ORM\Map;
use SilverStripe\ORM\SS_List;
use SilverStripe\ORM\UnsavedRelationList;
use SilverStripe\ORM\ValidationException;
use SilverStripe\ORM\ValidationResult;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Exception\RfcComplianceException;
use Closure;
use RuntimeException;

/**
 * The member class which represents the users of the system
 *
 * @property string $FirstName
 * @property string $Surname
 * @property string $Email
 * @property string $Password
 * @property string $TempIDHash
 * @property string $TempIDExpired
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
 * @method HasManyList<MemberPassword> LoggedPasswords()
 * @method HasManyList<RememberLoginHash> RememberLoginHashes()
 */
class Member extends DataObject
{
    private static $db = [
        'FirstName' => 'Varchar',
        'Surname' => 'Varchar',
        'Email' => 'Varchar(254)', // See RFC 5321, Section 4.5.3.1.3. (256 minus the < and > character)
        'TempIDHash' => 'Varchar(160)', // Temporary id used for cms re-authentication
        'TempIDExpired' => 'Datetime', // Expiry of temp login
        'Password' => 'Varchar(160)',
        'AutoLoginHash' => 'Varchar(160)', // Used to auto-login the user on password reset
        'AutoLoginExpired' => 'Datetime',
        // This is an arbitrary code pointing to a PasswordEncryptor instance,
        // not an actual encryption algorithm.
        // Warning: Never change this field after its the first password hashing without
        // providing a new cleartext password as well.
        'PasswordEncryption' => "Varchar(50)",
        'Salt' => 'Varchar(50)',
        'PasswordExpiry' => 'Date',
        'LockedOutUntil' => 'Datetime',
        'Locale' => 'Varchar(6)',
        // handled in registerFailedLogin(), only used if $lock_out_after_incorrect_logins is set
        'FailedLoginCount' => 'Int',
    ];

    private static $belongs_many_many = [
        'Groups' => Group::class,
    ];

    private static $has_many = [
        'LoggedPasswords' => MemberPassword::class,
        'RememberLoginHashes' => RememberLoginHash::class,
    ];

    private static $table_name = "Member";

    private static $default_sort = '"Surname", "FirstName"';

    private static $indexes = [
        'Email' => true,
        //Removed due to duplicate null values causing MSSQL problems
        //'AutoLoginHash' => Array('type'=>'unique', 'value'=>'AutoLoginHash', 'ignoreNulls'=>true)
    ];

    /**
     * @config
     * @var boolean
     */
    private static $notify_password_change = true;

    /**
     * All searchable database columns
     * in this object, currently queried
     * with a "column LIKE '%keywords%'
     * statement.
     *
     * @var array
     */
    private static $searchable_fields = [
        'FirstName',
        'Surname',
        'Email',
    ];

    /**
     * @config
     * @var array
     */
    private static $summary_fields = [
        'FirstName',
        'Surname',
        'Email',
    ];

    /**
     * @config
     * @var array
     */
    private static $casting = [
        'Name' => 'Varchar',
    ];

    /**
     * Internal-use only fields
     *
     * @config
     * @var array
     */
    private static $hidden_fields = [
        'AutoLoginHash',
        'AutoLoginExpired',
        'PasswordEncryption',
        'PasswordExpiry',
        'LockedOutUntil',
        'TempIDHash',
        'TempIDExpired',
        'Salt',
    ];

    /**
     * @config
     * @var array
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
     * The number of days that a password should be valid for.
     * By default, this is null, which means that passwords never expire
     */
    private static $password_expiry_days = null;

    /**
     * @config
     * @var bool enable or disable logging of previously used passwords. See {@link onAfterWrite}
     */
    private static $password_logging_enabled = true;

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
     * Default lifetime of auto login token.
     *
     * This is the maximum allowed period between a user requesting a password reset link and using it to reset
     * their password.
     *
     * Defaults to 2 days.
     *
     * @config
     * @var int Lifetime in seconds
     */
    private static $auto_login_token_lifetime = 172800;

    /**
     * Used to track whether {@link Member::changePassword} has made changed that need to be written. Used to prevent
     * the write from calling changePassword again.
     *
     * @var bool
     */
    protected $passwordChangesToWrite = false;

    /**
     * Ensure the locale is set to something sensible by default.
     */
    public function populateDefaults()
    {
        parent::populateDefaults();
        $this->Locale = i18n::config()->get('default_locale');
    }

    public function requireDefaultRecords()
    {
        parent::requireDefaultRecords();
        // Default groups should've been built by Group->requireDefaultRecords() already
        $service = DefaultAdminService::singleton();
        $service->findOrCreateDefaultAdmin();
    }

    /**
     * Check if this user is the currently configured default admin
     *
     * @return bool
     */
    public function isDefaultAdmin()
    {
        return DefaultAdminService::isDefaultAdmin($this->Email);
    }

    /**
     * Check if this user can login
     *
     * @return bool
     */
    public function canLogin()
    {
        return $this->validateCanLogin()->isValid();
    }

    /**
     * Returns a valid {@link ValidationResult} if this member can currently log in, or an invalid
     * one with error messages to display if the member is locked out.
     *
     * You can hook into this with a "canLogIn" method on an attached extension.
     *
     * @param ValidationResult $result Optional result to add errors to
     * @return ValidationResult
     */
    public function validateCanLogin(ValidationResult &$result = null)
    {
        $result = $result ?: ValidationResult::create();
        if ($this->isLockedOut()) {
            $result->addError(
                _t(
                    __CLASS__ . '.ERRORLOCKEDOUT2',
                    'Your account has been temporarily disabled because of too many failed attempts at ' . 'logging in. Please try again in {count} minutes.',
                    null,
                    ['count' => static::config()->get('lock_out_delay_mins')]
                )
            );
        }

        $this->extend('canLogIn', $result);

        return $result;
    }

    /**
     * Returns true if this user is locked out
     *
     * @return bool
     */
    public function isLockedOut()
    {
        /** @var DBDatetime $lockedOutUntilObj */
        $lockedOutUntilObj = $this->dbObject('LockedOutUntil');
        if ($lockedOutUntilObj->InFuture()) {
            return true;
        }

        $maxAttempts = $this->config()->get('lock_out_after_incorrect_logins');
        if ($maxAttempts <= 0) {
            return false;
        }

        $attempts = LoginAttempt::getByEmail($this->Email)
            ->sort('Created', 'DESC')
            ->limit($maxAttempts);

        if ($attempts->count() < $maxAttempts) {
            return false;
        }

        foreach ($attempts as $attempt) {
            if ($attempt->Status === 'Success') {
                return false;
            }
        }

        // Calculate effective LockedOutUntil
        /** @var DBDatetime $firstFailureDate */
        $firstFailureDate = $attempts->first()->dbObject('Created');
        $maxAgeSeconds = $this->config()->get('lock_out_delay_mins') * 60;
        $lockedOutUntil = $firstFailureDate->getTimestamp() + $maxAgeSeconds;
        $now = DBDatetime::now()->getTimestamp();
        if ($now < $lockedOutUntil) {
            return true;
        }

        return false;
    }

    /**
     * Set a {@link PasswordValidator} object to use to validate member's passwords.
     *
     * @param PasswordValidator $validator
     */
    public static function set_password_validator(PasswordValidator $validator = null)
    {
        // Override existing config
        Config::modify()->remove(Injector::class, PasswordValidator::class);
        if ($validator) {
            Injector::inst()->registerService($validator, PasswordValidator::class);
        } else {
            Injector::inst()->unregisterNamedObject(PasswordValidator::class);
        }
    }

    /**
     * Returns the default {@link PasswordValidator}
     *
     * @return PasswordValidator|null
     */
    public static function password_validator()
    {
        if (Injector::inst()->has(PasswordValidator::class)) {
            return Injector::inst()->get(PasswordValidator::class);
        }
        return null;
    }

    /**
     * Used to get the value for the reset password on next login checkbox
     */
    public function getRequiresPasswordChangeOnNextLogin(): bool
    {
        return $this->isPasswordExpired();
    }

    /**
     * Set password expiry to "now" to require a change of password next log in
     *
     * @param int|null $dataValue 1 is checked, 0/null is not checked {@see CheckboxField::dataValue}
     */
    public function saveRequiresPasswordChangeOnNextLogin(?int $dataValue): static
    {
        if (!$this->canEdit()) {
            return $this;
        }

        $currentValue = $this->PasswordExpiry;
        $currentDate = $this->dbObject('PasswordExpiry');

        if ($dataValue && (!$currentValue || $currentDate->inFuture())) {
            // Only alter future expiries - this way an admin could see how long ago a password expired still
            $this->PasswordExpiry = DBDatetime::now()->Rfc2822();
        } elseif (!$dataValue && $this->isPasswordExpired()) {
            // Only unset if the expiry date is in the past
            $this->PasswordExpiry = null;
        }

        return $this;
    }

    public function isPasswordExpired(): bool
    {
        if (!$this->PasswordExpiry) {
            return false;
        }

        return strtotime(date('Y-m-d')) >= strtotime($this->PasswordExpiry ?? '');
    }

    /**
     * Called before a member is logged in via session/cookie/etc
     */
    public function beforeMemberLoggedIn()
    {
        $this->extend('beforeMemberLoggedIn');
    }

    /**
     * Called after a member is logged in via session/cookie/etc
     */
    public function afterMemberLoggedIn()
    {
        // Clear the incorrect log-in count
        $this->registerSuccessfulLogin();

        $this->LockedOutUntil = null;

        $this->regenerateTempID();

        $this->write();

        // Audit logging hook
        $this->extend('afterMemberLoggedIn');
    }

    /**
     * Trigger regeneration of TempID.
     *
     * This should be performed any time the user presents their normal identification (normally Email)
     * and is successfully authenticated.
     */
    public function regenerateTempID()
    {
        $generator = new RandomGenerator();
        $lifetime = static::config()->get('temp_id_lifetime');
        $this->TempIDHash = $generator->randomToken('sha1');
        $this->TempIDExpired = $lifetime
            ? date('Y-m-d H:i:s', strtotime(DBDatetime::now()->getValue()) + $lifetime)
            : null;
        $this->write();
    }

    /**
     * Audit logging hook, called before a member is logged out
     *
     * @param HTTPRequest|null $request
     */
    public function beforeMemberLoggedOut(HTTPRequest $request = null)
    {
        $this->extend('beforeMemberLoggedOut', $request);
    }

    /**
     * Audit logging hook, called after a member is logged out
     *
     * @param HTTPRequest|null $request
     */
    public function afterMemberLoggedOut(HTTPRequest $request = null)
    {
        $this->extend('afterMemberLoggedOut', $request);
    }

    /**
     * Utility for generating secure password hashes for this member.
     *
     * @param string $string
     * @return string
     * @throws PasswordEncryptor_NotFoundException
     */
    public function encryptWithUserSettings($string)
    {
        if (!$string) {
            return null;
        }

        // If the algorithm or salt is not available, it means we are operating
        // on legacy account with unhashed password. Do not hash the string.
        if (!$this->PasswordEncryption || !$this->Salt) {
            return $string;
        }

        $e = PasswordEncryptor::create_for_algorithm($this->PasswordEncryption);
        return $e->encrypt($string, $this->Salt);
    }

    /**
     * Generate an auto login token which can be used to reset the password,
     * at the same time hashing it and storing in the database.
     *
     * @return string Token that should be passed to the client (but NOT persisted).
     */
    public function generateAutologinTokenAndStoreHash()
    {
        $lifetime = $this->config()->auto_login_token_lifetime;

        do {
            $generator = new RandomGenerator();
            $token = $generator->randomToken();
            $hash = $this->encryptWithUserSettings($token);
        } while (DataObject::get_one(Member::class, [
            '"Member"."AutoLoginHash"' => $hash
        ]));

        $this->AutoLoginHash = $hash;
        $this->AutoLoginExpired = date('Y-m-d H:i:s', time() + $lifetime);

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
    public function validateAutoLoginToken($autologinToken)
    {
        $hash = $this->encryptWithUserSettings($autologinToken);
        $member = Member::member_from_autologinhash($hash, false);

        return (bool)$member;
    }

    /**
     * Return the member for the auto login hash
     *
     * @param string $hash The hash key
     * @param bool $login Should the member be logged in?
     *
     * @return Member|null the matching member, if valid or null
     */
    public static function member_from_autologinhash($hash, $login = false)
    {
        $member = static::get()->filter([
            'AutoLoginHash' => $hash,
            'AutoLoginExpired:GreaterThan' => DBDatetime::now()->getValue(),
        ])->first();

        if ($login && $member) {
            Injector::inst()->get(IdentityStore::class)->logIn($member);
        }

        return $member;
    }

    /**
     * Find a member record with the given TempIDHash value
     *
     * @param string $tempid
     * @return Member|null the matching member, if valid or null
     */
    public static function member_from_tempid($tempid)
    {
        $members = static::get()
            ->filter('TempIDHash', $tempid);

        // Exclude expired
        if (static::config()->get('temp_id_lifetime')) {
            $members = $members->filter('TempIDExpired:GreaterThan', DBDatetime::now()->getValue());
        }

        return $members->first();
    }

    /**
     * Returns the fields for the member form - used in the registration/profile module.
     * It should return fields that are editable by the admin and the logged-in user.
     *
     *
     * @return FieldList Returns a {@link FieldList} containing the fields for
     *                   the member form.
     */
    public function getMemberFormFields()
    {
        $fields = parent::getFrontEndFields();

        $fields->replaceField('Password', $this->getMemberPasswordField());

        $fields->replaceField('Locale', new DropdownField(
            'Locale',
            $this->fieldLabel('Locale'),
            i18n::getSources()->getKnownLocales()
        ));

        $fields->removeByName(static::config()->get('hidden_fields'));
        $fields->removeByName('FailedLoginCount');


        $this->extend('updateMemberFormFields', $fields);

        return $fields;
    }

    /**
     * Builds "Change / Create Password" field for this member
     *
     * @return ConfirmedPasswordField
     */
    public function getMemberPasswordField()
    {
        $editingPassword = $this->isInDB();
        $label = $editingPassword
            ? _t(__CLASS__ . '.EDIT_PASSWORD', 'New Password')
            : $this->fieldLabel('Password');
        $password = ConfirmedPasswordField::create(
            'Password',
            $label,
            null,
            null,
            $editingPassword
        );

        // If editing own password, require confirmation of existing
        if ($editingPassword && $this->ID == Security::getCurrentUser()->ID) {
            $password->setRequireExistingPassword(true);
        }

        if (!$editingPassword) {
            $password->setCanBeEmpty(true);
            $password->setRandomPasswordCallback(Closure::fromCallable([$this, 'generateRandomPassword']));
            // explicitly set "require strong password" to false because its regex in ConfirmedPasswordField
            // is too restrictive for generateRandomPassword() which will add in non-alphanumeric characters
            $password->setRequireStrongPassword(false);
        }
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
    public function getValidator()
    {
        $validator = Member_Validator::create();
        $validator->setForMember($this);
        $this->extend('updateValidator', $validator);

        return $validator;
    }


    /**
     * Temporarily act as the specified user, limited to a $callback, but
     * without logging in as that user.
     *
     * E.g.
     * <code>
     * Member::actAs(DefaultAdminService::findOrCreateDefaultAdmin(), function() {
     *     $record->write();
     * });
     * </code>
     *
     * @param Member|null|int $member Member or member ID to log in as.
     * Set to null or 0 to act as a logged out user.
     * @param callable $callback
     * @return mixed Result of $callback
     */
    public static function actAs($member, $callback)
    {
        $previousUser = Security::getCurrentUser();

        // Transform ID to member
        if (is_numeric($member)) {
            $member = DataObject::get_by_id(Member::class, $member);
        }
        Security::setCurrentUser($member);

        try {
            return $callback();
        } finally {
            Security::setCurrentUser($previousUser);
        }
    }

    /**
     * Event handler called before writing to the database.
     */
    public function onBeforeWrite()
    {
        // Remove any line-break or space characters accidentally added during a copy-paste operation
        if ($this->Email) {
            $this->Email = trim($this->Email ?? '');
        }

        // If a member with the same "unique identifier" already exists with a different ID, don't allow merging.
        // Note: This does not a full replacement for safeguards in the controller layer (e.g. in a registration form),
        // but rather a last line of defense against data inconsistencies.
        $identifierField = Member::config()->get('unique_identifier_field');
        if ($this->$identifierField) {
            // Note: Same logic as Member_Validator class
            $filter = [
                "\"Member\".\"$identifierField\"" => $this->$identifierField
            ];
            if ($this->ID) {
                $filter[] = ['"Member"."ID" <> ?' => $this->ID];
            }
            $existingRecord = DataObject::get_one(Member::class, $filter);

            if ($existingRecord) {
                throw new ValidationException(_t(
                    __CLASS__ . '.ValidationIdentifierFailed',
                    'Can\'t overwrite existing member #{id} with identical identifier ({name} = {value}))',
                    'Values in brackets show "fieldname = value", usually denoting an existing email address',
                    [
                        'id' => $existingRecord->ID,
                        'name' => $identifierField,
                        'value' => $this->$identifierField
                    ]
                ));
            }
        }

        // We don't send emails out on dev/tests sites to prevent accidentally spamming users.
        // However, if TestMailer is in use this isn't a risk.
        if ((Director::isLive() || Injector::inst()->get(MailerInterface::class) instanceof TestMailer)
            && $this->isChanged('Password')
            && $this->record['Password']
            && Email::is_valid_address($this->Email ?? '')
            && static::config()->get('notify_password_change')
            && $this->isInDB()
        ) {
            try {
                $email = Email::create()
                    ->setHTMLTemplate('SilverStripe\\Control\\Email\\ChangePasswordEmail')
                    ->setData($this)
                    ->setTo($this->Email)
                    ->setSubject(_t(
                        __CLASS__ . '.SUBJECTPASSWORDCHANGED',
                        "Your password has been changed",
                        'Email subject'
                    ));

                $this->extend('updateChangedPasswordEmail', $email);
                $email->send();
            } catch (TransportExceptionInterface | RfcComplianceException $e) {
                /** @var LoggerInterface $logger */
                $logger = Injector::inst()->get(LoggerInterface::class . '.errorhandler');
                $logger->error('Error sending email in ' . __FILE__ . ' line ' . __LINE__ . ": {$e->getMessage()}");
            }
        }

        // The test on $this->ID is used for when records are initially created. Note that this only works with
        // cleartext passwords, as we can't rehash existing passwords.
        if (!$this->ID || $this->isChanged('Password')) {
            $this->encryptPassword();
        }
        if (!$this->PasswordEncryption) {
            $this->PasswordEncryption = Security::config()->get('password_encryption_algorithm');
        }
        // save locale
        if (!$this->Locale) {
            $this->Locale = i18n::config()->get('default_locale');
        }

        // Ensure FailedLoginCount is non-negative
        if ($this->FailedLoginCount < 0) {
            $this->FailedLoginCount = 0;
        }

        parent::onBeforeWrite();
    }

    public function onAfterWrite()
    {
        parent::onAfterWrite();

        Permission::reset();

        if ($this->isChanged('Password') && static::config()->get('password_logging_enabled')) {
            MemberPassword::log($this);
        }
    }

    public function onAfterDelete()
    {
        parent::onAfterDelete();

        // prevent orphaned records remaining in the DB
        $this->deletePasswordLogs();
        $this->Groups()->removeAll();
    }

    /**
     * Delete the MemberPassword objects that are associated to this user
     *
     * @return $this
     */
    protected function deletePasswordLogs()
    {
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
     * @param array $ids Database IDs of Group records
     * @return bool True if the change can be accepted
     */
    public function onChangeGroups($ids)
    {
        // Ensure none of these match disallowed list
        $disallowedGroupIDs = $this->disallowedGroups();
        return count(array_intersect($ids ?? [], $disallowedGroupIDs)) == 0;
    }

    /**
     * List of group IDs this user is disallowed from
     *
     * @return int[] List of group IDs
     */
    protected function disallowedGroups()
    {
        // unless the current user is an admin already OR the logged in user is an admin
        if (Permission::check('ADMIN') || Permission::checkMember($this, 'ADMIN')) {
            return [];
        }

        // Non-admins may not belong to admin groups
        return Permission::get_groups_by_permission('ADMIN')->column('ID');
    }


    /**
     * Check if the member is in one of the given groups.
     *
     * @param array|SS_List $groups Collection of {@link Group} DataObjects to check
     * @param boolean $strict Only determine direct group membership if set to true (Default: false)
     * @return bool Returns TRUE if the member is in one of the given groups, otherwise FALSE.
     */
    public function inGroups($groups, $strict = false)
    {
        if ($groups) {
            foreach ($groups as $group) {
                if ($this->inGroup($group, $strict)) {
                    return true;
                }
            }
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
    public function inGroup($group, $strict = false)
    {
        if (is_numeric($group)) {
            $groupCheckObj = DataObject::get_by_id(Group::class, $group);
        } elseif (is_string($group)) {
            $groupCheckObj = DataObject::get_one(Group::class, [
                '"Group"."Code"' => $group
            ]);
        } elseif ($group instanceof Group) {
            $groupCheckObj = $group;
        } else {
            throw new InvalidArgumentException('Member::inGroup(): Wrong format for $group parameter');
        }

        if (!$groupCheckObj) {
            return false;
        }

        $groupCandidateObjs = ($strict) ? $this->getManyManyComponents("Groups") : $this->Groups();

        return $groupCandidateObjs->filter(['ID' => $groupCheckObj->ID])->exists();
    }

    /**
     * Adds the member to a group. This will create the group if the given
     * group code does not return a valid group object.
     *
     * @param string $groupcode
     * @param string $title Title of the group
     */
    public function addToGroupByCode($groupcode, $title = "")
    {
        $group = DataObject::get_one(Group::class, [
            '"Group"."Code"' => $groupcode
        ]);

        if ($group) {
            $this->Groups()->add($group);
        } else {
            if (!$title) {
                $title = $groupcode;
            }

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
    public function removeFromGroupByCode($groupcode)
    {
        $group = Group::get()->filter(['Code' => $groupcode])->first();

        if ($group) {
            $this->Groups()->remove($group);
        }
    }

    //------------------- HELPER METHODS -----------------------------------//

    /**
     * Simple proxy method to get the Surname property of the member
     *
     * @return string
     */
    public function getLastName()
    {
        return $this->Surname;
    }

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
    public function getTitle()
    {
        $format = static::config()->get('title_format');
        if ($format) {
            $values = [];
            foreach ($format['columns'] as $col) {
                $values[] = $this->getField($col);
            }

            return implode($format['sep'] ?? '', $values);
        }
        if ($this->getField('ID') === 0) {
            return $this->getField('Surname');
        } else {
            if ($this->getField('Surname') && $this->getField('FirstName')) {
                return $this->getField('Surname') . ', ' . $this->getField('FirstName');
            } elseif ($this->getField('Surname')) {
                return $this->getField('Surname');
            } elseif ($this->getField('FirstName')) {
                return $this->getField('FirstName');
            } else {
                return null;
            }
        }
    }

    /**
     * Return a SQL CONCAT() fragment suitable for a SELECT statement.
     * Useful for custom queries which assume a certain member title format.
     *
     * @return String SQL
     */
    public static function get_title_sql()
    {

        // Get title_format with fallback to default
        $format = static::config()->get('title_format');
        if (!$format) {
            $format = [
                'columns' => ['Surname', 'FirstName'],
                'sep' => ' ',
            ];
        }

        $columnsWithTablename = [];
        foreach ($format['columns'] as $column) {
            $columnsWithTablename[] = static::getSchema()->sqlColumnForField(__CLASS__, $column);
        }

        $sepSQL = Convert::raw2sql($format['sep'], true);
        $op = DB::get_conn()->concatOperator();
        return "(" . join(" $op $sepSQL $op ", $columnsWithTablename) . ")";
    }


    /**
     * Get the complete name of the member
     *
     * @return string Returns the first- and surname of the member.
     */
    public function getName()
    {
        $name = ($this->Surname) ? trim($this->FirstName . ' ' . $this->Surname) : $this->FirstName;
        $this->extend('updateName', $name);
        return $name;
    }


    /**
     * Set first- and surname
     *
     * This method assumes that the last part of the name is the surname, e.g.
     * <i>A B C</i> will result in firstname <i>A B</i> and surname <i>C</i>
     *
     * @param string $name The name
     */
    public function setName($name)
    {
        $nameParts = explode(' ', $name ?? '');
        $this->Surname = array_pop($nameParts);
        $this->FirstName = join(' ', $nameParts);
    }


    /**
     * Alias for {@link setName}
     *
     * @param string $name The name
     * @see setName()
     */
    public function splitName($name)
    {
        return $this->setName($name);
    }

    /**
     * Return the date format based on the user's chosen locale,
     * falling back to the default format defined by the i18n::config()->get('default_locale') config setting.
     *
     * @return string ISO date format
     */
    public function getDateFormat()
    {
        $formatter = new IntlDateFormatter(
            $this->getLocale(),
            IntlDateFormatter::MEDIUM,
            IntlDateFormatter::NONE
        );
        $format = $formatter->getPattern();

        $this->extend('updateDateFormat', $format);

        return $format;
    }

    /**
     * Get user locale, falling back to the configured default locale
     */
    public function getLocale()
    {
        $locale = $this->getField('Locale');
        if ($locale) {
            return $locale;
        }

        return i18n::config()->get('default_locale');
    }

    /**
     * Return the time format based on the user's chosen locale,
     * falling back to the default format defined by the i18n::config()->get('default_locale') config setting.
     *
     * @return string ISO date format
     */
    public function getTimeFormat()
    {
        $formatter = new IntlDateFormatter(
            $this->getLocale(),
            IntlDateFormatter::NONE,
            IntlDateFormatter::MEDIUM
        );
        $format = $formatter->getPattern();

        $this->extend('updateTimeFormat', $format);

        return $format;
    }

    //---------------------------------------------------------------------//


    /**
     * Get a "many-to-many" map that holds for all members their group memberships,
     * including any parent groups where membership is implied.
     * Use {@link DirectGroups()} to only retrieve the group relations without inheritance.
     *
     * @return Member_Groupset
     */
    public function Groups()
    {
        $groups = Member_GroupSet::create(Group::class, 'Group_Members', 'GroupID', 'MemberID');
        $groups = $groups->forForeignID($this->ID);

        $this->extend('updateGroups', $groups);

        return $groups;
    }

    /**
     * @return ManyManyList<Group>|UnsavedRelationList<Group>
     */
    public function DirectGroups()
    {
        return $this->getManyManyComponents('Groups');
    }

    /**
     * Get a member SQLMap of members in specific groups
     *
     * If no $groups is passed, all members will be returned
     *
     * @param mixed $groups - takes a SS_List, an array or a single Group.ID
     * @return Map Returns an Map that returns all Member data.
     */
    public static function map_in_groups($groups = null)
    {
        $groupIDList = [];

        if ($groups instanceof SS_List) {
            foreach ($groups as $group) {
                $groupIDList[] = $group->ID;
            }
        } elseif (is_array($groups)) {
            $groupIDList = $groups;
        } elseif ($groups) {
            $groupIDList[] = $groups;
        }

        // No groups, return all Members
        if (!$groupIDList) {
            return static::get()->sort(['Surname' => 'ASC', 'FirstName' => 'ASC'])->map();
        }

        $membersList = new ArrayList();
        // This is a bit ineffective, but follow the ORM style
        foreach (Group::get()->byIDs($groupIDList) as $group) {
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
     * @param SS_List|array|null $groups Groups to consider or NULL to use all groups with
     *                      CMS permissions.
     * @return Map Returns a map of all members in the groups given that
     *                have CMS permissions.
     */
    public static function mapInCMSGroups(SS_List|array|null $groups = null): Map
    {
        // non-countable $groups will issue a warning when using count() in PHP 7.2+
        if ($groups === null) {
            $groups = [];
        }

        // Check CMS module exists
        if (!class_exists(LeftAndMain::class)) {
            return ArrayList::create()->map();
        }

        if (count($groups) === 0) {
            $perms = ['ADMIN', 'CMS_ACCESS_AssetAdmin'];

            if (class_exists(CMSMain::class)) {
                $cmsPerms = CMSMain::singleton()->providePermissions();
            } else {
                $cmsPerms = LeftAndMain::singleton()->providePermissions();
            }

            if (!empty($cmsPerms)) {
                $perms = array_unique(array_merge($perms, array_keys($cmsPerms ?? [])));
            }

            $permsClause = DB::placeholders($perms);
            $groups = Group::get()
                ->innerJoin("Permission", '"Permission"."GroupID" = "Group"."ID"')
                ->where([
                    "\"Permission\".\"Code\" IN ($permsClause)" => $perms
                ]);
        }

        $groupIDList = [];

        if ($groups instanceof SS_List) {
            foreach ($groups as $group) {
                $groupIDList[] = $group->ID;
            }
        } elseif (is_array($groups)) {
            $groupIDList = $groups;
        }

        $members = static::get()
            ->innerJoin("Group_Members", '"Group_Members"."MemberID" = "Member"."ID"')
            ->innerJoin("Group", '"Group"."ID" = "Group_Members"."GroupID"');
        if ($groupIDList) {
            $groupClause = DB::placeholders($groupIDList);
            $members = $members->where([
                "\"Group\".\"ID\" IN ($groupClause)" => $groupIDList
            ]);
        }

        return $members->sort('"Surname", "FirstName"')->map();
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
    public function memberNotInGroups($groupList, $memberGroups = null)
    {
        if (!$memberGroups) {
            $memberGroups = $this->Groups();
        }

        foreach ($memberGroups as $group) {
            if (in_array($group->Code, $groupList ?? [])) {
                $index = array_search($group->Code, $groupList ?? []);
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
    public function getCMSFields()
    {
        $this->beforeUpdateCMSFields(function (FieldList $fields) {
            /** @var TabSet $rootTabSet */
            $rootTabSet = $fields->fieldByName("Root");
            /** @var Tab $mainTab */
            $mainTab = $rootTabSet->fieldByName("Main");
            $mainFields = $mainTab->getChildren();

            // Build change password field
            $mainFields->replaceField('Password', $this->getMemberPasswordField());

            $mainFields->replaceField('Locale', new DropdownField(
                "Locale",
                _t(__CLASS__ . '.INTERFACELANG', "Interface Language", 'Language of the CMS'),
                i18n::getSources()->getKnownLocales()
            ));
            $mainFields->removeByName(static::config()->get('hidden_fields'));

            if (!static::config()->get('lock_out_after_incorrect_logins')) {
                $mainFields->removeByName('FailedLoginCount');
            }

            // Groups relation will get us into logical conflicts because
            // Members are displayed within  group edit form in SecurityAdmin
            $fields->removeByName('Groups');

            // Members shouldn't be able to directly view/edit logged passwords
            $fields->removeByName('LoggedPasswords');

            $fields->removeByName('RememberLoginHashes');

            if (Permission::check('EDIT_PERMISSIONS')) {
                // Filter allowed groups
                $groups = Group::get();
                $disallowedGroupIDs = $this->disallowedGroups();
                if ($disallowedGroupIDs) {
                    $groups = $groups->exclude('ID', $disallowedGroupIDs);
                }
                $groupsMap = [];
                foreach ($groups as $group) {
                    // Listboxfield values are escaped, use ASCII char instead of &raquo;
                    $groupsMap[$group->ID] = $group->getBreadcrumbs(' > ');
                }
                asort($groupsMap);
                $fields->addFieldToTab(
                    'Root.Main',
                    ListboxField::create('DirectGroups', Group::singleton()->i18n_plural_name())
                        ->setSource($groupsMap)
                        ->setAttribute(
                            'data-placeholder',
                            _t(__CLASS__ . '.ADDGROUP', 'Add group', 'Placeholder text for a dropdown')
                        )
                );


                // Add permission field (readonly to avoid complicated group assignment logic).
                // This should only be available for existing records, as new records start
                // with no permissions until they have a group assignment anyway.
                if ($this->ID) {
                    $permissionsField = new PermissionCheckboxSetField_Readonly(
                        'Permissions',
                        false,
                        Permission::class,
                        'GroupID',
                        // we don't want parent relationships, they're automatically resolved in the field
                        $this->getManyManyComponents('Groups')
                    );
                    $fields->findOrMakeTab('Root.Permissions', Permission::singleton()->i18n_plural_name());
                    $fields->addFieldToTab('Root.Permissions', $permissionsField);
                }
            }

            $permissionsTab = $rootTabSet->fieldByName('Permissions');
            if ($permissionsTab) {
                $permissionsTab->addExtraClass('readonly');
            }

            $currentUser = Security::getCurrentUser();
            // We can allow an admin to require a user to change their password. But:
            // - Don't show a read only field if the user cannot edit this record
            // - Don't show if a user views their own profile (just let them reset their own password)
            if ($currentUser && ($currentUser->ID !== $this->ID) && $this->canEdit()) {
                $requireNewPassword = CheckboxField::create(
                    'RequiresPasswordChangeOnNextLogin',
                    _t(__CLASS__ . '.RequiresPasswordChangeOnNextLogin', 'Requires password change on next log in')
                );
                $fields->insertAfter('Password', $requireNewPassword);
                $fields->dataFieldByName('Password')->addExtraClass('form-field--no-divider mb-0 pb-0');
            }
        });

        return parent::getCMSFields();
    }

    /**
     * @param bool $includerelations Indicate if the labels returned include relation fields
     * @return array
     */
    public function fieldLabels($includerelations = true)
    {
        $labels = parent::fieldLabels($includerelations);

        $labels['FirstName'] = _t(__CLASS__ . '.FIRSTNAME', 'First Name');
        $labels['Surname'] = _t(__CLASS__ . '.SURNAME', 'Surname');
        $labels['Email'] = _t(__CLASS__ . '.EMAIL', 'Email');
        $labels['Password'] = _t(__CLASS__ . '.db_Password', 'Password');
        $labels['PasswordExpiry'] = _t(
            __CLASS__ . '.db_PasswordExpiry',
            'Password Expiry Date',
            'Password expiry date'
        );
        $labels['LockedOutUntil'] = _t(__CLASS__ . '.db_LockedOutUntil', 'Locked out until', 'Security related date');
        $labels['Locale'] = _t(__CLASS__ . '.db_Locale', 'Interface Locale');
        if ($includerelations) {
            $labels['Groups'] = _t(
                __CLASS__ . '.belongs_many_many_Groups',
                'Groups',
                'Security Groups this member belongs to'
            );
        }

        return $labels;
    }

    /**
     * Users can view their own record.
     * Otherwise they'll need ADMIN or CMS_ACCESS_SecurityAdmin permissions.
     * This is likely to be customized for social sites etc. with a looser permission model.
     *
     * @param Member $member
     * @return bool
     */
    public function canView($member = null)
    {
        //get member
        if (!$member) {
            $member = Security::getCurrentUser();
        }
        //check for extensions, we do this first as they can overrule everything
        $extended = $this->extendedCan(__FUNCTION__, $member);
        if ($extended !== null) {
            return $extended;
        }

        //need to be logged in and/or most checks below rely on $member being a Member
        if (!$member) {
            return false;
        }
        // members can usually view their own record
        if ($this->ID == $member->ID) {
            return true;
        }

        //standard check
        return Permission::checkMember($member, 'CMS_ACCESS_SecurityAdmin');
    }

    /**
     * Users can edit their own record.
     * Otherwise they'll need ADMIN or CMS_ACCESS_SecurityAdmin permissions
     *
     * @param Member $member
     * @return bool
     */
    public function canEdit($member = null)
    {
        //get member
        if (!$member) {
            $member = Security::getCurrentUser();
        }
        //check for extensions, we do this first as they can overrule everything
        $extended = $this->extendedCan(__FUNCTION__, $member);
        if ($extended !== null) {
            return $extended;
        }

        //need to be logged in and/or most checks below rely on $member being a Member
        if (!$member) {
            return false;
        }

        // HACK: we should not allow for an non-Admin to edit an Admin
        if (!Permission::checkMember($member, 'ADMIN') && Permission::checkMember($this, 'ADMIN')) {
            return false;
        }
        // members can usually edit their own record
        if ($this->ID == $member->ID) {
            return true;
        }

        //standard check
        return Permission::checkMember($member, 'CMS_ACCESS_SecurityAdmin');
    }

    /**
     * Users can edit their own record.
     * Otherwise they'll need ADMIN or CMS_ACCESS_SecurityAdmin permissions
     *
     * @param Member $member
     * @return bool
     */
    public function canDelete($member = null)
    {
        if (!$member) {
            $member = Security::getCurrentUser();
        }
        //check for extensions, we do this first as they can overrule everything
        $extended = $this->extendedCan(__FUNCTION__, $member);
        if ($extended !== null) {
            return $extended;
        }

        //need to be logged in and/or most checks below rely on $member being a Member
        if (!$member) {
            return false;
        }
        // Members are not allowed to remove themselves,
        // since it would create inconsistencies in the admin UIs.
        if ($this->ID && $member->ID == $this->ID) {
            return false;
        }

        // HACK: if you want to delete a member, you have to be a member yourself.
        // this is a hack because what this should do is to stop a user
        // deleting a member who has more privileges (e.g. a non-Admin deleting an Admin)
        if (Permission::checkMember($this, 'ADMIN')) {
            if (!Permission::checkMember($member, 'ADMIN')) {
                return false;
            }
        }

        //standard check
        return Permission::checkMember($member, 'CMS_ACCESS_SecurityAdmin');
    }

    /**
     * Validate this member object.
     */
    public function validate()
    {
        // If validation is disabled, skip this step
        if (!DataObject::config()->uninherited('validation_enabled')) {
            return ValidationResult::create();
        }

        $valid = parent::validate();
        $validator = static::password_validator();

        if ($validator) {
            if ((!$this->ID && $this->Password) || $this->isChanged('Password')) {
                $userValid = $validator->validate($this->Password, $this);
                $valid->combineAnd($userValid);
            }
        }

        return $valid;
    }

    /**
     * Change password. This will cause rehashing according to the `PasswordEncryption` property via the
     * `onBeforeWrite()` method. This method will allow extensions to perform actions and augment the validation
     * result if required before the password is written and can check it after the write also.
     *
     * `onBeforeWrite()` will encrypt the password prior to writing.
     *
     * @param string $password Cleartext password
     * @param bool $write Whether to write the member afterwards
     * @return ValidationResult
     */
    public function changePassword($password, $write = true)
    {
        $this->Password = $password;
        $result = $this->validate();

        $this->extend('onBeforeChangePassword', $password, $result);

        if ($result->isValid()) {
            $this->AutoLoginHash = null;

            if ($write) {
                $this->write();
            }
        }

        $this->extend('onAfterChangePassword', $password, $result);

        return $result;
    }

    /**
     * Takes a plaintext password (on the Member object) and encrypts it
     *
     * @return $this
     */
    protected function encryptPassword()
    {
        // reset salt so that it gets regenerated - this will invalidate any persistent login cookies
        // or other information encrypted with this Member's settings (see Member::encryptWithUserSettings)
        $this->Salt = '';

        // Password was changed: encrypt the password according the settings
        $encryption_details = Security::encrypt_password(
            $this->Password,
            $this->Salt,
            $this->isChanged('PasswordEncryption') ? $this->PasswordEncryption : null,
            $this
        );

        // Overwrite the Password property with the hashed value
        $this->Password = $encryption_details['password'];
        $this->Salt = $encryption_details['salt'];
        $this->PasswordEncryption = $encryption_details['algorithm'];

        // If we haven't manually set a password expiry
        if (!$this->isChanged('PasswordExpiry')) {
            // then set it for us
            if (static::config()->get('password_expiry_days')) {
                $this->PasswordExpiry = date('Y-m-d', time() + 86400 * static::config()->get('password_expiry_days'));
            } else {
                $this->PasswordExpiry = null;
            }
        }

        return $this;
    }

    /**
     * Tell this member that someone made a failed attempt at logging in as them.
     * This can be used to lock the user out temporarily if too many failed attempts are made.
     */
    public function registerFailedLogin()
    {
        $lockOutAfterCount = static::config()->get('lock_out_after_incorrect_logins');
        if ($lockOutAfterCount) {
            // Keep a tally of the number of failed log-ins so that we can lock people out
            ++$this->FailedLoginCount;

            if ($this->FailedLoginCount >= $lockOutAfterCount) {
                $lockoutMins = static::config()->get('lock_out_delay_mins');
                $this->LockedOutUntil = date('Y-m-d H:i:s', DBDatetime::now()->getTimestamp() + $lockoutMins * 60);
                $this->FailedLoginCount = 0;
            }
        }
        $this->extend('registerFailedLogin');
        $this->write();
    }

    /**
     * Tell this member that a successful login has been made
     */
    public function registerSuccessfulLogin()
    {
        if (static::config()->get('lock_out_after_incorrect_logins')) {
            // Forgive all past login failures
            $this->FailedLoginCount = 0;
            $this->LockedOutUntil = null;
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
    public function getHtmlEditorConfigForCMS()
    {
        $currentName = '';
        $currentPriority = 0;

        foreach ($this->Groups() as $group) {
            $configName = $group->HtmlEditorConfig;
            if ($configName) {
                $config = HTMLEditorConfig::get($group->HtmlEditorConfig);
                if ($config && $config->getOption('priority') > $currentPriority) {
                    $currentName = $configName;
                    $currentPriority = $config->getOption('priority');
                }
            }
        }

        // If can't find a suitable editor, just default to cms
        return $currentName ? $currentName : 'cms';
    }

    /**
     * Generate a random password and validate it against the current password validator if one is set
     *
     * @param int $length The length of the password to generate, defaults to 0 which will use the
     *                    greater of the validator's minimum length or 20
     */
    public function generateRandomPassword(int $length = 0): string
    {
        $password = '';
        $validator = Member::password_validator();
        if ($length && $validator && $length < $validator->getMinLength()) {
            throw new InvalidArgumentException('length argument is less than password validator minLength');
        }
        $validatorMinLength = $validator ? $validator->getMinLength() : 0;
        $len = $length ?: max($validatorMinLength, 20);
        // The default PasswordValidator checks the password includes the following four character sets
        $charsets = [
            'abcdefghijklmnopqrstuvwyxz',
            'ABCDEFGHIJKLMNOPQRSTUVWYXZ',
            '0123456789',
            '!@#$%^&*()_+-=[]{};:,./<>?',
        ];
        $password = '';
        for ($i = 0; $i < $len; $i++) {
            $charset = $charsets[$i % 4];
            $randomInt = random_int(0, strlen($charset) - 1);
            $password .= $charset[$randomInt];
        }
        // randomise the order of the characters
        $passwordArr = [];
        $len = strlen($password);
        foreach (str_split($password) as $char) {
            $r = random_int(0, $len + 10000);
            while (array_key_exists($r, $passwordArr)) {
                $r++;
            }
            $passwordArr[$r] = $char;
        }
        ksort($passwordArr);
        $password = implode('', $passwordArr);
        $this->extend('updateRandomPassword', $password);
        if ($validator && !$validator->validate($password, $this)) {
            throw new RuntimeException('Unable to generate a random password');
        }
        return $password;
    }
}
