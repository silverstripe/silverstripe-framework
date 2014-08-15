<?php
/**
 * Encrypt all passwords
 *
 * Action to encrypt all *clear text* passwords in the database according
 * to the current settings.
 * If the current settings are so that passwords shouldn't be encrypted,
 * an explanation will be printed out.
 *
 * To run this action, the user needs to have administrator rights!
 *
 * @package framework
 * @subpackage tasks
 */
class EncryptAllPasswordsTask extends BuildTask {
	protected $title = 'Encrypt all passwords tasks';

	protected $description = 'Convert all plaintext passwords on the Member table to the default encryption/hashing
		algorithm. Note: This mainly applies to passwords in SilverStripe 2.1 or earlier, passwords in newer versions
		are hashed by default.';

	public function init() {
		parent::init();

		if(!Permission::check('ADMIN')) {
			return Security::permissionFailure($this);
		}
	}

	public function run($request) {
		$algo = Security::config()->password_encryption_algorithm;
		if($algo == 'none') {
			$this->debugMessage('Password encryption disabled');
			return;
		}

		// Are there members with a clear text password?
		$members = DataObject::get("Member")->where(array(
			'"Member"."PasswordEncryption"' => 'none',
			'"Member"."Password" IS NOT NULL'
		));

		if(!$members) {
			$this->debugMessage('No passwords to encrypt');
			return;
		}

		// Encrypt the passwords...
		$this->debugMessage('Encrypting all passwords');
		$this->debugMessage(sprintf(
			'The passwords will be encrypted using the %s algorithm',
			$algo
		));

		foreach($members as $member) {
			// Force the update of the member record, as new passwords get
			// automatically encrypted according to the settings, this will do all
			// the work for us
			$member->PasswordEncryption = $algo;
			$member->forceChange();
			$member->write();

			$this->debugMessage(sprintf('Encrypted credentials for member #%d;', $member->ID));
		}
	}

	/**
	 * @todo This should really be taken care of by TestRunner
	 */
	protected function debugMessage($msg) {
		if(class_exists('SapphireTest', false) && !SapphireTest::is_running_test()) {
			Debug::message($msg);
		}
	}
}
