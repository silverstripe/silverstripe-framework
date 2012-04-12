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
	 */
	protected static $encryptors = array();
	
	/**
	 * @return Array Map of encryptor code to the used class.
	 */
	static function get_encryptors() {
		return Config::inst()->get('PasswordEncryptor', 'encryptors');
	}
	
	/**
	 * Add a new encryptor implementation.
	 * 
	 * Note: Due to portability concerns, its not advisable to 
	 * override an existing $code mapping with different behaviour.
	 * 
	 * @param String $code This value will be stored stored in the 
	 * 	{@link Member->PasswordEncryption} property.
	 * @param String $class Classname of a {@link PasswordEncryptor} subclass
	 */
	static function register($code, $class) {
		Deprecation::notice('3.0', 'Use the Config system to register Password encryptors');
		self::$encryptors[$code] = $class;
	}
	
	/**
	 * @param String $code Unique lookup.
	 */
	static function unregister($code) {
		Deprecation::notice('3.0', 'Use the Config system to unregister Password encryptors');
		if(isset(self::$encryptors[$code])) unset(self::$encryptors[$code]);
	}
	
	/**
	 * @param String $algorithm
	 * @return PasswordEncryptor
	 * @throws PasswordEncryptor_NotFoundException
	 */
	static function create_for_algorithm($algorithm) {
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
	abstract function encrypt($password, $salt = null, $member = null);
	
	/**
	 * Return a string value stored in the {@link Member->Salt} property.
	 * 
	 * @uses RandomGenerator
	 * 
	 * @param String $password Cleartext password
	 * @param Member $member (Optional)
	 * @return String Maximum of 50 characters
	 */
	function salt($password, $member = null) {
		$generator = new RandomGenerator();
		return substr($generator->generateHash('sha1'), 0, 50);
	}
	
	/**
	 * This usually just returns a strict string comparison,
	 * but is necessary for {@link PasswordEncryptor_LegacyPHPHash}.
	 * 
	 * @param String $hash1
	 * @param String $hash2
	 * @return boolean
	 */
	function compare($hash1, $hash2) {
		return ($hash1 === $hash2);
	}
}

/**
 * This is the default class used for built-in hash types in PHP.
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
	function __construct($algorithm) {
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
	function getAlgorithm() {
		return $this->algorithm;
	}
	
	function encrypt($password, $salt = null, $member = null) {
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
	function encrypt($password, $salt = null, $member = null) {
		$password = parent::encrypt($password, $salt, $member);
		
		// Legacy fix: This shortening logic is producing unpredictable results.
		// 
		// Convert the base of the hexadecimal password to 36 to make it shorter
		// In that way we can store also a SHA256 encrypted password in just 64
		// letters.
		return substr(base_convert($password, 16, 36), 0, 64);
	}
	
	function compare($hash1, $hash2) {
		// Due to flawed base_convert() floating poing precision, 
		// only the first 10 characters are consistently useful for comparisons.
		return (substr($hash1, 0, 10) === substr($hash2, 0, 10));
	}
}

/**
 * Uses MySQL's PASSWORD encryption. Requires an active DB connection.
 * 
 * @package framework
 * @subpackage security
 */
class PasswordEncryptor_MySQLPassword extends PasswordEncryptor {
	function encrypt($password, $salt = null, $member = null) {
		return DB::query(
			sprintf("SELECT PASSWORD('%s')", Convert::raw2sql($password))
		)->value();
	}
	
	function salt($password, $member = null) {
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
	function encrypt($password, $salt = null, $member = null) {
		return DB::query(
			sprintf("SELECT OLD_PASSWORD('%s')", Convert::raw2sql($password))
		)->value();
	}
	
	function salt($password, $member = null) {
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
	function encrypt($password, $salt = null, $member = null) {
		return $password;
	}
	
	function salt($password, $member = null) {
		return false;
	}
}

/**
 * @package framework
 * @subpackage security
 */
class PasswordEncryptor_NotFoundException extends Exception {}
