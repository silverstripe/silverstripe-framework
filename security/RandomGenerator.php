<?php
/**
 * Generates entropy values based on strongest available methods
 * (mcrypt_create_iv(), openssl_random_pseudo_bytes(), /dev/urandom, COM.CAPICOM.Utilities.1, mt_rand()).
 * Chosen method depends on operating system and PHP version.
 * 
 * @package sapphire
 * @subpackage security
 * @author Ingo Schommer
 */
class RandomGenerator {

	/**
	 * @return string Returns a random series of bytes
	 */
	function generateEntropy() {
		// mcrypt with urandom is only available on PHP 5.3 or newer
		if(version_compare(PHP_VERSION, '5.3.0', '>=')) {
			return mcrypt_create_iv(64, MCRYPT_DEV_URANDOM);
		}

		// Fall back to SSL methods - may slow down execution by a few ms
		if (function_exists('openssl_random_pseudo_bytes')) {
			$e = openssl_random_pseudo_bytes(64, $strong);
			// Only return if strong algorithm was used
			if($strong) return $e;
		}

		// Read from the unix random number generator
		if (is_readable('/dev/urandom') && ($h = fopen('/dev/urandom', 'rb'))) {
			$e = fread($h, 64);
			fclose($h);
			return $e;
		}

		// Warning: Both methods below are considered weak

		// try to read from the windows RNG
		if (class_exists('COM')) {
			try {
				$comObj = new COM('CAPICOM.Utilities.1');
				$e = base64_decode($comObj->GetRandom(64, 0));
				return $e;
			} catch (Exception $ex) {
			}
		}

		// Fallback to good old mt_rand()
		return uniqid(mt_rand(), true);
	}
	
	/**
	 * Generates a hash suitable for manual session identifiers, CSRF tokens, etc.
	 * 
	 * @param String $algorithm Any identifier listed in hash_algos() (Default: whirlpool)
	 *  If possible, choose a slow algorithm which complicates brute force attacks.
	 * @return String Returned length will depend on the used $algorithm
	 */
	function generateHash($algorithm = 'whirlpool') {
		return hash($algorithm, $this->generateEntropy());
	}
	
}