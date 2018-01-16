<?php

class MemberLoginFormTest extends SapphireTest
{
	protected $usesDatabase = true;

	public function testLogInUserAndRedirect()
	{
		// logout current user
		Member::currentUser()->logOut();

		// create a new member to login as
		$member = new Member();
		$member->FirstName = 'Test';
		$member->Surname = 'User';
		$member->Email = 'test@example.com';
		$member->SetPassword = 'password';
		$member->write();

		$requestData = array(
			'Email' => 'test@example.com',
			'Password' => 'password',
			'BackURL' => '/home/',
		);

		$controller = $this->getMockBuilder('Controller')
			->setMethods(array(
				'redirect',
			))
			->getMock();

		$controller->expects($this->once())->method('redirect')->with('http://localhost/home/');

		$loginForm = $this->getMockBuilder('MemberLoginForm')
			->setMethods(array(
				'performLogin',
				'logInUserAndRedirect',
			))
			->setConstructorArgs(array(
				$controller,
				'MemberLoginForm',
			))
			->enableProxyingToOriginalMethods()
			->getMock();

		$loginForm->expects($this->once())->method('performLogin')->with($requestData);
		$loginForm->expects($this->once())->method('logInUserAndRedirect')->with($requestData);

		$loginForm->dologin($requestData);
	}

	public function testLogInUserAndRedirectWithExpiredPassword()
	{
		SS_Datetime::set_mock_now('2018-01-16 00:00:00');
		// logout current user
		Member::currentUser()->logOut();

		// create a new member to login as
		$member = new Member();
		$member->FirstName = 'Test';
		$member->Surname = 'User';
		$member->Email = 'test@example.com';
		$member->SetPassword = 'password';
		$member->PasswordExpiry = '2018-01-01';
		$member->write();

		$requestData = array(
			'Email' => 'test@example.com',
			'Password' => 'password',
			'BackURL' => '/home/',
		);

		$controller = $this->getMockBuilder('Controller')
			->setMethods(array(
				'redirect',
			))
			->getMock();

		$controller->expects($this->once())->method('redirect')->with('Security/changepassword');

		$loginForm = $this->getMockBuilder('MemberLoginForm')
			->setMethods(array(
				'performLogin',
				'logInUserAndRedirect',
			))
			->setConstructorArgs(array(
				$controller,
				'MemberLoginForm',
			))
			->enableProxyingToOriginalMethods()
			->getMock();

		$loginForm->expects($this->once())->method('performLogin')->with($requestData);
		$loginForm->expects($this->once())->method('logInUserAndRedirect')->with($requestData);

		$loginForm->dologin($requestData);
	}
}
