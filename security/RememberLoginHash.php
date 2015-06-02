<?php

class RememberLoginHash extends DataObject {

	private static $db = array (
		'DeviceID' => 'VarChar(40)',
		'RememberLoginHash' => 'Varchar(160)',
	);

	private static $has_one = array (
		'Member' => 'Member',
	);

	private static $indexes = array(
		'DeviceID' => true
	);

	/**
	 * Determines if logging out on one device also clears existing login tokens
	 * on all other devices owned by the member.
	 *
	 * @config
	 * @var bool
	 */
	private static $logout_across_devices = false;

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
	 * If true, user can only use auto login on one device
	 *
	 * @config
	 * @var bool
	 */
	private static $force_single_token = false;

	/**
	 * The token used for the hash
	 */
	private $token = null;

	public function getToken() {
		return $this->token;
	}

	public function setToken($token) {
		$this->token = $token;
	}

	/**
	 * Randomly generates a new ID used for the device
	 * @return string A device ID
	 */
	protected function getNewDeviceID(){
		$generator = new RandomGenerator();
		return $generator->randomToken('sha1');
	}

	/**
	 * Creates a new random token and hashes it using the 
	 * member information
	 * @param Member The logged in user 
	 * @return string The hash to be stored in the database
	 */
	public function getNewHash(Member $member){
		$generator = new RandomGenerator();
		$this->setToken($generator->randomToken('sha1'));
		return $member->encryptWithUserSettings($this->token);
	}

	/**
	 * Generates a new login hash associated with a device
	 * The device is assigned a globally unique device ID
	 * The returned login hash stores the hashed token in the 
	 * database, for this device and this member
	 * @param Member The logged in user 
	 * @return RememberLoginHash The generated login hash
	 */
	public static function generate(Member $member) {
		if ($member) {
			if (Config::inst()->get('RememberLoginHash', 'force_single_token') == true) {
				$rememberLoginHash = RememberLoginHash::get()->filter('MemberID', $member->ID)->removeAll();
			} 
			$rememberLoginHash = RememberLoginHash::create();
			do {
				$deviceID = $rememberLoginHash->getNewDeviceID();
			} while (RememberLoginHash::get()->filter('DeviceID', $deviceID)->Count());
			
			$rememberLoginHash->DeviceID = $deviceID;
			$rememberLoginHash->RememberLoginHash = $rememberLoginHash->getNewHash($member);
			$rememberLoginHash->MemberID = $member->ID;
			$rememberLoginHash->write();
			return $rememberLoginHash;
		}
	}

	/**
	 * Generates a new hash for this member but keeps the device ID intact
	 * @param Member the logged in user
	 * @return RememberLoginHash
	 */
	public function renew(Member $member) {
		$hash = $this->getNewHash($member);
		$this->RememberLoginHash = $hash;
		$this->write();
		return $this;
	}

	/**
	 * Deletes existing tokens for this member
	 * if logout_across_devices is true, all tokens are deleted, otherwise
	 * only the token for the provided device ID will be removed
	 */
	public static function clear(Member $member, $alcDevice = null) {
		if ($member) {
			$filter = array('MemberID'=>$member->ID);
			if ((Config::inst()->get('RememberLoginHash', 'logout_across_devices') == false) && $alcDevice) {
				$filter['DeviceID'] = $alcDevice;
			}		
			RememberLoginHash::get()
				->filter($filter)
				->removeAll();
		}
	}
	
}