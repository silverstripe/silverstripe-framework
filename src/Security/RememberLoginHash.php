<?php

namespace SilverStripe\Security;

use DateInterval;
use DateTime;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\FieldType\DBDatetime;

/**
 * Persists a token associated with a device for users who opted for the "Remember Me"
 * feature when logging in.
 * By default, logging out will discard all existing tokens for this user
 * The device ID is a temporary ID associated with the device when the user logged in
 * and chose to get the login state remembered on this device. When logging out, the ID
 * is discarded as well.
 *
 * @property string $DeviceID
 * @property string $ExpiryDate
 * @property string $Hash
 * @method Member Member()
 */
class RememberLoginHash extends DataObject
{
    private static $singular_name = 'Login Hash';

    private static $plural_name = 'Login Hashes';

    private static $db = array (
        'DeviceID' => 'Varchar(40)',
        'Hash' => 'Varchar(160)',
        'ExpiryDate' => 'Datetime'
    );

    private static $has_one = array (
        'Member' => Member::class,
    );

    private static $indexes = array(
        'DeviceID' => true,
        'Hash' => true
    );

    private static $table_name = "RememberLoginHash";

    /**
     * Determines if logging out on one device also clears existing login tokens
     * on all other devices owned by the member.
     * If set to false, there is no way for users to revoke a login, unless additional
     * code (custom or with a module) is provided by the developer
     *
     * @config
     * @var bool
     */
    private static $logout_across_devices = true;

    /**
     * Number of days the token will be valid for
     *
     * @config
     * @var int
     */
    private static $token_expiry_days = 90;

    /**
     * Number of days the device ID will be valid for
     *
     * @config
     * @var int
     */
    private static $device_expiry_days = 365;

    /**
     * If true, user can only use auto login on one device. A user can still login from multiple
     * devices, but previous tokens from other devices will become invalid.
     *
     * @config
     * @var bool
     */
    private static $force_single_token = false;

    /**
     * The token used for the hash
     */
    private $token = null;

    public function getToken()
    {
        return $this->token;
    }

    public function setToken($token)
    {
        $this->token = $token;
    }

    /**
     * Randomly generates a new ID used for the device
     * @return string A device ID
     */
    protected function getNewDeviceID()
    {
        $generator = new RandomGenerator();
        return $generator->randomToken('sha1');
    }

    /**
     * Creates a new random token and hashes it using the
     * member information
     * @param Member $member The logged in user
     * @return string The hash to be stored in the database
     */
    public function getNewHash(Member $member)
    {
        $generator = new RandomGenerator();
        $this->setToken($generator->randomToken('sha1'));
        return $member->encryptWithUserSettings($this->token);
    }

    /**
     * Generates a new login hash associated with a device
     * The device is assigned a globally unique device ID
     * The returned login hash stores the hashed token in the
     * database, for this device and this member
     * @param Member $member The logged in user
     * @return RememberLoginHash The generated login hash
     */
    public static function generate(Member $member)
    {
        if (!$member->exists()) {
            return null;
        }
        if (static::config()->force_single_token) {
            RememberLoginHash::get()->filter('MemberID', $member->ID)->removeAll();
        }
        /** @var RememberLoginHash $rememberLoginHash */
        $rememberLoginHash = RememberLoginHash::create();
        do {
            $deviceID = $rememberLoginHash->getNewDeviceID();
        } while (RememberLoginHash::get()->filter('DeviceID', $deviceID)->count());

        $rememberLoginHash->DeviceID = $deviceID;
        $rememberLoginHash->Hash = $rememberLoginHash->getNewHash($member);
        $rememberLoginHash->MemberID = $member->ID;
        $now = DBDatetime::now();
        $expiryDate = new DateTime($now->Rfc2822());
        $tokenExpiryDays = static::config()->token_expiry_days;
        $expiryDate->add(new DateInterval('P' . $tokenExpiryDays . 'D'));
        $rememberLoginHash->ExpiryDate = $expiryDate->format('Y-m-d H:i:s');
        $rememberLoginHash->extend('onAfterGenerateToken');
        $rememberLoginHash->write();
        return $rememberLoginHash;
    }

    /**
     * Generates a new hash for this member but keeps the device ID intact
     *
     * @return RememberLoginHash
     */
    public function renew()
    {
        $hash = $this->getNewHash($this->Member());
        $this->Hash = $hash;
        $this->extend('onAfterRenewToken');
        $this->write();
        return $this;
    }

    /**
     * Deletes existing tokens for this member
     * if logout_across_devices is true, all tokens are deleted, otherwise
     * only the token for the provided device ID will be removed
     *
     * @param Member $member
     * @param string $alcDevice
     */
    public static function clear(Member $member, $alcDevice = null)
    {
        if (!$member->exists()) {
            return;
        }
        $filter = array('MemberID'=>$member->ID);
        if (!static::config()->logout_across_devices && $alcDevice) {
            $filter['DeviceID'] = $alcDevice;
        }
        RememberLoginHash::get()
            ->filter($filter)
            ->removeAll();
    }
}
