<?php

/**
 * This class represents a validator for member passwords.
 * 
 * <code>
 * $pwdVal = new PasswordValidator();
 * $pwdValidator->minLength(7);
 * $pwdValidator->checkHistoricalPasswords(6);
 * $pwdValidator->characterStrength('lowercase','uppercase','digits','punctuation');
 * 
 * Member::set_password_validator($pwdValidator);
 * </code>
 *
 * @package framework
 * @subpackage security
 */
class PasswordValidator extends Object {
	static $character_strength_tests = array(
		'lowercase' => '/[a-z]/',
		'uppercase' => '/[A-Z]/',
		'digits' => '/[0-9]/',
		'punctuation' => '/[^A-Za-z0-9]/',
	);
	
	protected $minLength, $minScore, $testNames, $historicalPasswordCount;

	/**
	 * Minimum password length
	 */
	function minLength($minLength) {
		$this->minLength = $minLength;
	}
	
	/**
	 * Check the character strength of the password.
	 *
	 * Eg: $this->characterStrength(3, array("lowercase", "uppercase", "digits", "punctuation"))
	 * 
	 * @param $minScore The minimum number of character tests that must pass
	 * @param $testNames The names of the tests to perform
	 */
	function characterStrength($minScore, $testNames) {
		$this->minScore = $minScore;
		$this->testNames = $testNames;
	}
	
	/**
	 * Check a number of previous passwords that the user has used, and don't let them change to that.
	 */
	function checkHistoricalPasswords($count) {
		$this->historicalPasswordCount = $count;
	}
	
	/**
	 * @param String $password
	 * @param Member $member
	 * @return ValidationResult
	 */
	function validate($password, $member) {
		$valid = new ValidationResult();
		
		if($this->minLength) {
			if(strlen($password) < $this->minLength) $valid->error(sprintf("Password is too short, it must be %s or more characters long.", $this->minLength), "TOO_SHORT");
		}

		if($this->minScore) {
			$score = 0;
			$missedTests = array();
			foreach($this->testNames as $name) {
				if(preg_match(self::$character_strength_tests[$name], $password)) $score++;
				else $missedTests[] = $name;
			}
			
			if($score < $this->minScore) {
				$valid->error("You need to increase the strength of your passwords by adding some of the following characters: " . implode(", ", $missedTests), "LOW_CHARACTER_STRENGTH");
			}
		}
		
		if($this->historicalPasswordCount) {
			$previousPasswords = DataObject::get("MemberPassword", "\"MemberID\" = $member->ID", "\"Created\" DESC, \"ID\" Desc", "", $this->historicalPasswordCount);
			if($previousPasswords) foreach($previousPasswords as $previousPasswords) {
				if($previousPasswords->checkPassword($password)) {
					$valid->error("You've already used that password in the past, please choose a new password", "PREVIOUS_PASSWORD");
					break;
				}
			}
		}
		
		return $valid;
	}
	
}
