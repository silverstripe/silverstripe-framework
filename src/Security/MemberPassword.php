<?php

namespace SilverStripe\Security;

use SilverStripe\ORM\DataObject;

/**
 * Keep track of users' previous passwords, so that we can check that new passwords aren't changed back to old ones.
 *
 * @property string $Password
 * @property string $Salt
 * @property string $PasswordEncryption
 * @property int $MemberID ID of the Member
 * @method Member Member()
 */
class MemberPassword extends DataObject
{
    private static $db = [
        'Password' => 'Varchar(160)',
        'Salt' => 'Varchar(50)',
        'PasswordEncryption' => 'Varchar(50)',
    ];

    private static $has_one = [
        'Member' => Member::class,
    ];

    private static $table_name = "MemberPassword";

    /**
     * Log a password change from the given member.
     * Call MemberPassword::log($this) from within Member whenever the password is changed.
     *
     * @param Member $member
     */
    public static function log($member)
    {
        $record = new MemberPassword();
        $record->MemberID = $member->ID;
        $record->Password = $member->Password;
        $record->PasswordEncryption = $member->PasswordEncryption;
        $record->Salt = $member->Salt;
        $record->write();
    }

    /**
     * Check if the given password is the same as the one stored in this record.
     *
     * @param string $password Cleartext password
     * @return bool
     */
    public function checkPassword($password)
    {
        $encryptor = PasswordEncryptor::create_for_algorithm($this->PasswordEncryption);
        return $encryptor->check($this->Password ?? '', $password, $this->Salt, $this->Member());
    }
}
