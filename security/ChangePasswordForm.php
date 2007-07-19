<?php

 /**
	* Standard Change Password Form
	*/

class ChangePasswordForm extends Form {	

	function __construct($controller, $name, $fields = null, $actions = null) {
		
		if(!$fields) {
			$fields = new FieldSet(
				new EncryptField("OldPassword","Your old password"),
				new EncryptField("NewPassword1", "New Password"),
				new EncryptField("NewPassword2", "Confirm New Password")
			);
		}
		if(!$actions) {
			$actions = new FieldSet(
				new FormAction("changePassword", "Change Password")
			);
		}
		
		parent::__construct($controller, $name, $fields, $actions);
	}
	
	function changePassword($data){
		if($member = Member::currentUser()){
			if($data['OldPassword'] != $member->Password){
				$this->clearMessage();
				$this->sessionMessage("Your current password does not match, please try again", "bad");
				Director::redirectBack();
			}else	if($data[NewPassword1] == $data[NewPassword2]){
				$member->Password = $data[NewPassword1] ;
				$member->sendinfo('changePassword');
				$member->write();
				$this->clearMessage();
				$this->sessionMessage("Your password has been changed, and a copy emailed to you.", "good");
				Director::redirectBack();
			}
			else{
				$this->clearMessage();
				$this->sessionMessage("Your have entered your new password differently, try again", "bad");
				Director::redirectBack();
			}
		}
		else		{
			Director::redirect('loginpage');
		}
	}
	
}

?>