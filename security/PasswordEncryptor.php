<?php
/**
 * Allows pluggable password encryption.
 * By default, this might be PHP's integrated sha1()
 * function, but could also be more sophisticated to facilitate
 * password migrations from other systems.
 * Use {@link register()} to add new implementations.
 *
 * Used in {@link Security::encrypt_password()}.
 *
 * @package framework
 * @subpackage security
 */
abstract class PasswordEncryptor {

	/**
	 * @var array
	 * @config
	 */
	private static $encryptors = array();

	/**
	 * @return Array Map of encryptor code to the used class.
	 */
	public static function get_encryptors() {
		return Config::inst()->get('PasswordEncryptor', 'encryptors');
	}

	/**
	 * @param String $algorithm
	 * @return PasswordEncryptor
	 * @throws PasswordEncryptor_NotFoundException
	 */
	public static function create_for_algorithm($algorithm) {
		$encryptors = self::get_encryptors();
		if(!isset($encryptors[$algorithm])) {
			throw new PasswordEncryptor_NotFoundException(
				sprintf('No implementation found for "%s"', $algorithm)
			);
		}

		$class=key($encryptors[$algorithm]);
		if(!class_exists($class)) {
			throw new PasswordEncryptor_NotFoundException(
				sprintf('No class found for "%s"', $class)
			);

		}
		$refClass = new ReflectionClass($class);
		if(!$refClass->getConstructor()) {
			return new $class;
		}

		$arguments = $encryptors[$algorithm];
		return($refClass->newInstanceArgs($arguments));
	}

	/**
	 * Return a string value stored in the {@link Member->Password} property.
	 * The password should be hashed with {@link salt()} if applicable.
	 *
	 * @param String $password Cleartext password to be hashed
	 * @param String $salt (Optional)
	 * @param Member $member (Optional)
	 * @return String Maximum of 512 characters.
	 */
	abstract public function encrypt($password, $salt = null, $member = null);

	/**
	 * Return a string value stored in the {@link Member->Salt} property.
	 *
	 * @uses RandomGenerator
	 *
	 * @param String $password Cleartext password
	 * @param Member $member (Optional)
	 * @return String Maximum of 50 characters
	 */
	public function salt($password, $member = null) {
		$generator = new RandomGenerator();
		return substr($generator->randomToken('sha1'), 0, 50);
	}

	/**
	 * This usually just returns a strict string comparison,
	 * but is necessary for retain compatibility with password hashed
	 * with flawed algorithms - see {@link PasswordEncryptor_LegacyPHPHash} and
	 * {@link PasswordEncryptor_Blowfish}
	 */
	public function check($hash, $password, $salt = null, $member = null) {
		return $hash === $this->encrypt($password, $salt, $member);
	}
}

/**
 * Blowfish encryption - this is the default from SilverStripe 3.
 * PHP 5.3+ will provide a php implementation if there is no system
 * version available.
 *
 * @package framework
 * @subpackage security
 */
class PasswordEncryptor_Blowfish extends PasswordEncryptor {
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
	 * @return null
	 */
	public static function set_cost($cost) {
		self::$cost = max(min(31, $cost), 4);
	}

	/**
	 * Gets the cost that is set for the blowfish algorithm
	 *
	 * @param int $cost
	 * @return null
	 */
	public static function get_cost() {
		return self::$cost;
	}

	public function encrypt($password, $salt = null, $member = null) {
		// See: http://nz.php.net/security/crypt_blowfish.php
		// There are three version of the algorithm - y, a and x, in order
		// of decreasing security. Attempt to use the strongest version.
		$encryptedPassword = $this->encryptY($password, $salt);
		if(!$encryptedPassword) {
			$encryptedPassword = $this->encryptA($password, $salt);
		}
		if(!$encryptedPassword) {
			$encryptedPassword = $this->encryptX($password, $salt);
		}

		// We *never* want to generate blank passwords. If something
		// goes wrong, throw an exception.
		if(strpos($encryptedPassword, '$2') === false) {
			throw new PasswordEncryptor_EncryptionFailed('Blowfish password encryption failed.');
		}

		return $encryptedPassword;
	}

	public function encryptX($password, $salt) {
		$methodAndSalt = '$2x$' . $salt;
		$encryptedPassword = crypt($password, $methodAndSalt);

		if(strpos($encryptedPassword, '$2x$') === 0) {
			return $encryptedPassword;
		}

		// Check if system a is actually x, and if available, use that.
		if($this->checkAEncryptionLevel() == 'x') {
			$methodAndSalt = '$2a$' . $salt;
			$encryptedPassword = crypt($password, $methodAndSalt);

			if(strpos($encryptedPassword, '$2a$') === 0) {
				$encryptedPassword = '$2x$' . substr($encryptedPassword, strlen('$2a$'));
				return $encryptedPassword;
			}
		}

		return false;
	}

	public function encryptY($password, $salt) {
		$methodAndSalt = '$2y$' . $salt;
		$encryptedPassword = crypt($password, $methodAndSalt);

		if(strpos($encryptedPassword, '$2y$') === 0) {
			return $encryptedPassword;
		}

		// Check if system a is actually y, and if available, use that.
		if($this->checkAEncryptionLevel() == 'y') {
			$methodAndSalt = '$2a$' . $salt;
			$encryptedPassword = crypt($password, $methodAndSalt);

			if(strpos($encryptedPassword, '$2a$') === 0) {
				$encryptedPassword = '$2y$' . substr($encryptedPassword, strlen('$2a$'));
				return $encryptedPassword;
			}
		}

		return false;
	}

	public function encryptA($password, $salt) {
		if($this->checkAEncryptionLevel() == 'a') {
			$methodAndSalt = '$2a$' . $salt;
			$encryptedPassword = crypt($password, $methodAndSalt);

			if(strpos($encryptedPassword, '$2a$') === 0) {
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
	public function checkAEncryptionLevel() {
		// Test hashes taken from
		// http://cvsweb.openwall.com/cgi/cvsweb.cgi/~checkout~/Owl/packages/glibc
		//    /crypt_blowfish/wrapper.c?rev=1.9.2.1;content-type=text%2Fplain
		$xOrY = crypt("\xff\xa334\xff\xff\xff\xa3345", '$2a$05$/OK.fbVrR/bpIqNJ5ianF.o./n25XVfn6oAPaUvHe.Csk4zRfsYPi')
			== '$2a$05$/OK.fbVrR/bpIqNJ5ianF.o./n25XVfn6oAPaUvHe.Csk4zRfsYPi';
		$yOrA = crypt("\xa3", '$2a$05$/OK.fbVrR/bpIqNJ5ianF.Sa7shbm4.OzKpvFnX1pQLmQW96oUlCq')
			== '$2a$05$/OK.fbVrR/bpIqNJ5ianF.Sa7shbm4.OzKpvFnX1pQLmQW96oUlCq';

		if($xOrY && $yOrA) {
			return 'y';
		} elseif($xOrY) {
			return 'x';
		} elseif($yOrA) {
			return 'a';
		}

		return 'unknown';
	}

	/**
	 * self::$cost param is forced to be two digits with leading zeroes for ints 4-9
	 */
	public function salt($password, $member = null) {
		$generator = new RandomGenerator();
		return sprintf('%02d', self::$cost) . '$' . substr($generator->randomToken('sha1'), 0, 22);
	}

	public function check($hash, $password, $salt = null, $member = null) {
		if(strpos($hash, '$2y$') === 0) {
			return $hash === $this->encryptY($password, $salt);
		} elseif(strpos($hash, '$2a$') === 0) {
			return $hash === $this->encryptA($password, $salt);
		} elseif(strpos($hash, '$2x$') === 0) {
			return $hash === $this->encryptX($password, $salt);
		}

		return false;
	}
}

/**
 * Encryption using built-in hash types in PHP.
 * Please note that the implemented algorithms depend on the PHP
 * distribution and architecture.
 *
 * @package framework
 * @subpackage security
 */
class PasswordEncryptor_PHPHash extends PasswordEncryptor {

	protected $algorithm = 'sha1';

	/**
	 * @param String $algorithm A PHP built-in hashing algorithm as defined by hash_algos()
	 */
	public function __construct($algorithm) {
		if(!in_array($algorithm, hash_algos())) {
			throw new Exception(
				sprintf('Hash algorithm "%s" not found in hash_algos()', $algorithm)
			);
		}

		$this->algorithm = $algorithm;
	}

	/**
	 * @return string
	 */
	public function getAlgorithm() {
		return $this->algorithm;
	}

	public function encrypt($password, $salt = null, $member = null) {
		return hash($this->algorithm, $password . $salt);
	}
}

/**
 * Legacy implementation for SilverStripe 2.1 - 2.3,
 * which had a design flaw in password hashing that caused
 * the hashes to differ between architectures due to
 * floating point precision problems in base_convert().
 * See http://open.silverstripe.org/ticket/3004
 *
 * @package framework
 * @subpackage security
 */
class PasswordEncryptor_LegacyPHPHash extends PasswordEncryptor_PHPHash {
	public function encrypt($password, $salt = null, $member = null) {
		$password = parent::encrypt($password, $salt, $member);

		// Legacy fix: This shortening logic is producing unpredictable results.
		//
		// Convert the base of the hexadecimal password to 36 to make it shorter
		// In that way we can store also a SHA256 encrypted password in just 64
		// letters.
		return substr(base_convert($password, 16, 36), 0, 64);
	}

	public function check($hash, $password, $salt = null, $member = null) {
		// Due to flawed base_convert() floating poing precision,
		// only the first 10 characters are consistently useful for comparisons.
		return (substr($hash, 0, 10) === substr($this->encrypt($password, $salt, $member), 0, 10));
	}
}

/**
 * Uses MySQL's PASSWORD encryption. Requires an active DB connection.
 *
 * @package framework
 * @subpackage security
 */
class PasswordEncryptor_MySQLPassword extends PasswordEncryptor {
	public function encrypt($password, $salt = null, $member = null) {
		return DB::prepared_query("SELECT PASSWORD(?)", array($password))->value();
	}

	public function salt($password, $member = null) {
		return false;
	}
}

/**
 * Uses MySQL's OLD_PASSWORD encyrption. Requires an active DB connection.
 *
 * @package framework
 * @subpackage security
 */
class PasswordEncryptor_MySQLOldPassword extends PasswordEncryptor {
	public function encrypt($password, $salt = null, $member = null) {
		return DB::prepared_query("SELECT OLD_PASSWORD(?)", array($password))->value();
	}

	public function salt($password, $member = null) {
		return false;
	}
}

/**
 * Cleartext passwords (used in SilverStripe 2.1).
 * Also used when Security::$encryptPasswords is set to FALSE.
 * Not recommended.
 *
 * @package framework
 * @subpackage security
 */
class PasswordEncryptor_None extends PasswordEncryptor {
	public function encrypt($password, $salt = null, $member = null) {
		return $password;
	}

	public function salt($password, $member = null) {
		return false;
	}
}

/**
 * @package framework
 * @subpackage security
 */
class PasswordEncryptor_NotFoundException extends Exception {}

/**
 * @package framework
 * @subpackage security
 */
class PasswordEncryptor_EncryptionFailed extends Exception {}
