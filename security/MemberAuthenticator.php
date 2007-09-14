<?php

/**
 * Authenticator for the default "member" method
 *
 * @author Markus Lanthaler <markus@silverstripe.com>
 */
class MemberAuthenticator extends Authenticator {

  /**
   * Method to authenticate an user
   *
   * @param array $RAW_data Raw data to authenticate the user
   * @return bool|Member Returns FALSE if authentication fails, otherwise
   *                     the member object
   */
  public function authenticate(array $RAW_data) {
    $SQL_user = Convert::raw2sql($RAW_data['Email']);
    $SQL_password = Convert::raw2sql($RAW_data['Password']);

    $member = DataObject::get_one(
        "Member", "Email = '$SQL_user' And Password = '$SQL_password'");

    if($member) {
      Session::clear("BackURL");
    }

    return $member;
  }


  /**
   * Method that creates the login form for this authentication method
   *
   * @return Form Returns the login form to use with this authentication
   *              method
   */
  public function getLoginForm() {
    return Object::create("MemberLoginForm", $this, "LoginForm");
  }
}

?>