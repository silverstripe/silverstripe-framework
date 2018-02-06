<?php

namespace SilverStripe\Security;

use SilverStripe\ORM\DataList;
use SilverStripe\ORM\DataObject;

/**
 * Record all login attempts through the {@link LoginForm} object.
 * This behaviour is disabled by default.
 *
 * Enable through {@link Security::$login_recording}.
 *
 * Caution: Please make sure that enabling logging
 * complies with your privacy standards. We're logging
 * username and IP.
 *
 * @property string $Email Email address used for login attempt. @deprecated 3.0...5.0
 * @property string $EmailHashed sha1 hashed Email address used for login attempt
 * @property string $Status Status of the login attempt, either 'Success' or 'Failure'
 * @property string $IP IP address of user attempting to login
 * @property int $MemberID ID of the Member, only if Member with Email exists
 *
 * @method Member Member() Member object of the user trying to log in, only if Member with Email exists
 */
class LoginAttempt extends DataObject
{
    /**
     * Success status
     */
    const SUCCESS = 'Success';

    /**
     * Failure status
     */
    const FAILURE = 'Failure';

    private static $db = array(
        'Email' => 'Varchar(255)', // Remove in 5.0
        'EmailHashed' => 'Varchar(255)',
        'Status' => "Enum('Success,Failure')",
        'IP' => 'Varchar(255)',
    );

    private static $has_one = array(
        'Member' => Member::class, // only linked if the member actually exists
    );

    private static $table_name = "LoginAttempt";

    /**
     * @skipUpgrade
     * @param bool $includerelations Indicate if the labels returned include relation fields
     * @return array
     */
    public function fieldLabels($includerelations = true)
    {
        $labels = parent::fieldLabels($includerelations);
        $labels['Email'] = _t(__CLASS__ . '.Email', 'Email Address');
        $labels['EmailHashed'] = _t(__CLASS__ . '.EmailHashed', 'Email Address (hashed)');
        $labels['Status'] = _t(__CLASS__ . '.Status', 'Status');
        $labels['IP'] = _t(__CLASS__ . '.IP', 'IP Address');

        return $labels;
    }

    /**
     * Set email used for this attempt
     *
     * @param string $email
     * @return $this
     */
    public function setEmail($email)
    {
        // Store hashed email only
        $this->EmailHashed = sha1($email);
        return $this;
    }

    /**
     * Get all login attempts for the given email address
     *
     * @param string $email
     * @return DataList|LoginAttempt[]
     */
    public static function getByEmail($email)
    {
        return static::get()->filterAny(array(
            'Email' => $email,
            'EmailHashed' => sha1($email),
        ));
    }
}
