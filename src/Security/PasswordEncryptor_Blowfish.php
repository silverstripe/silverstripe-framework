<?php

namespace SilverStripe\Security;

/**
 * Blowfish encryption - this is the default from SilverStripe 3.
 * PHP 5.3+ will provide a php implementation if there is no system
 * version available.
 */
class PasswordEncryptor_Blowfish extends PasswordEncryptor
{
    /**
     * Cost of encryption.
     * Higher costs will increase security, but also increase server load.
     * If you are using basic auth, you may need to decrease this as encryption
     * will be run on every request.
     * The two digit cost parameter is the base-2 logarithm of the iteration
     * count for the underlying Blowfish-based hashing algorithmeter and must
     * be in range 04-31, values outside this range will cause crypt() to fail.
     */
    protected static $cost = 10;

    /**
     * Sets the cost of the blowfish algorithm.
     * See {@link PasswordEncryptor_Blowfish::$cost}
     * Cost is set as an integer but
     * Ensure that set values are from 4-31
     *
     * @param int $cost range 4-31
     */
    public static function set_cost($cost)
    {
        PasswordEncryptor_Blowfish::$cost = max(min(31, $cost), 4);
    }

    /**
     * Gets the cost that is set for the blowfish algorithm
     *
     * @return int
     */
    public static function get_cost()
    {
        return PasswordEncryptor_Blowfish::$cost;
    }

    public function encrypt($password, $salt = null, $member = null)
    {
        // See: http://nz.php.net/security/crypt_blowfish.php
        // There are three version of the algorithm - y, a and x, in order
        // of decreasing security. Attempt to use the strongest version.
        $encryptedPassword = $this->encryptY($password, $salt);
        if (!$encryptedPassword) {
            $encryptedPassword = $this->encryptA($password, $salt);
        }
        if (!$encryptedPassword) {
            $encryptedPassword = $this->encryptX($password, $salt);
        }

        // We *never* want to generate blank passwords. If something
        // goes wrong, throw an exception.
        if (strpos($encryptedPassword ?? '', '$2') === false) {
            throw new PasswordEncryptor_EncryptionFailed('Blowfish password encryption failed.');
        }

        return $encryptedPassword;
    }

    public function encryptX($password, $salt)
    {
        $methodAndSalt = '$2x$' . $salt;
        $encryptedPassword = crypt($password ?? '', $methodAndSalt ?? '');

        if (strpos($encryptedPassword ?? '', '$2x$') === 0) {
            return $encryptedPassword;
        }

        // Check if system a is actually x, and if available, use that.
        if ($this->checkAEncryptionLevel() == 'x') {
            $methodAndSalt = '$2a$' . $salt;
            $encryptedPassword = crypt($password ?? '', $methodAndSalt ?? '');

            if (strpos($encryptedPassword ?? '', '$2a$') === 0) {
                $encryptedPassword = '$2x$' . substr($encryptedPassword ?? '', strlen('$2a$'));
                return $encryptedPassword;
            }
        }

        return false;
    }

    public function encryptY($password, $salt)
    {
        $methodAndSalt = '$2y$' . $salt;
        $encryptedPassword = crypt($password ?? '', $methodAndSalt ?? '');

        if (strpos($encryptedPassword ?? '', '$2y$') === 0) {
            return $encryptedPassword;
        }

        // Check if system a is actually y, and if available, use that.
        if ($this->checkAEncryptionLevel() == 'y') {
            $methodAndSalt = '$2a$' . $salt;
            $encryptedPassword = crypt($password ?? '', $methodAndSalt ?? '');

            if (strpos($encryptedPassword ?? '', '$2a$') === 0) {
                $encryptedPassword = '$2y$' . substr($encryptedPassword ?? '', strlen('$2a$'));
                return $encryptedPassword;
            }
        }

        return false;
    }

    public function encryptA($password, $salt)
    {
        if ($this->checkAEncryptionLevel() == 'a') {
            $methodAndSalt = '$2a$' . $salt;
            $encryptedPassword = crypt($password ?? '', $methodAndSalt ?? '');

            if (strpos($encryptedPassword ?? '', '$2a$') === 0) {
                return $encryptedPassword;
            }
        }

        return false;
    }

    /**
     * The algorithm returned by using '$2a$' is not consistent -
     * it might be either the correct (y), incorrect (x) or mostly-correct (a)
     * version, depending on the version of PHP and the operating system,
     * so we need to test it.
     */
    public function checkAEncryptionLevel()
    {
        // Test hashes taken from
        // http://cvsweb.openwall.com/cgi/cvsweb.cgi/~checkout~/Owl/packages/glibc
        //    /crypt_blowfish/wrapper.c?rev=1.9.2.1;content-type=text%2Fplain
        $xOrY = crypt("\xff\xa334\xff\xff\xff\xa3345", '$2a$05$/OK.fbVrR/bpIqNJ5ianF.o./n25XVfn6oAPaUvHe.Csk4zRfsYPi')
            == '$2a$05$/OK.fbVrR/bpIqNJ5ianF.o./n25XVfn6oAPaUvHe.Csk4zRfsYPi';
        $yOrA = crypt("\xa3", '$2a$05$/OK.fbVrR/bpIqNJ5ianF.Sa7shbm4.OzKpvFnX1pQLmQW96oUlCq')
            == '$2a$05$/OK.fbVrR/bpIqNJ5ianF.Sa7shbm4.OzKpvFnX1pQLmQW96oUlCq';

        if ($xOrY && $yOrA) {
            return 'y';
        } elseif ($xOrY) {
            return 'x';
        } elseif ($yOrA) {
            return 'a';
        }

        return 'unknown';
    }

    /**
     * PasswordEncryptor_Blowfish::$cost param is forced to be two digits with leading zeroes for ints 4-9
     *
     * @param string $password
     * @param Member $member
     * @return string
     */
    public function salt($password, $member = null)
    {
        $generator = new RandomGenerator();
        return sprintf('%02d', PasswordEncryptor_Blowfish::$cost) . '$' . substr($generator->randomToken('sha1') ?? '', 0, 22);
    }

    public function check($hash, $password, $salt = null, $member = null)
    {
        if (strpos($hash ?? '', '$2y$') === 0) {
            return $hash === $this->encryptY($password, $salt);
        } elseif (strpos($hash ?? '', '$2a$') === 0) {
            return $hash === $this->encryptA($password, $salt);
        } elseif (strpos($hash ?? '', '$2x$') === 0) {
            return $hash === $this->encryptX($password, $salt);
        }

        return false;
    }
}
