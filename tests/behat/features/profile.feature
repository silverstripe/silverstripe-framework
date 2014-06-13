Feature: Manage my own settings
	As a CMS user
	I want to be able to change personal settings
	In order to streamline my CMS experience

	Background:
		Given a "member" "Joe" belonging to "Admin Group" with "Email"="joe@test.com" and "Password"="secret"
		And the "group" "Admin Group" has permissions "Full administrative rights"
		And I log in with "joe@test.com" and "secret"
		And I go to "admin/myprofile"

	Scenario: I can edit my personal details
		Given I fill in "First Name" with "Jack"
		And I fill in "Surname" with "Johnson"
		And I fill in "Email" with "jack@test.com"
		When I press the "Save" button
		Given I go to "admin/myprofile"
		Then I should not see "Joe"
		Then I should see "Jack"
		And I should see "Johnson"

	Scenario: I can change my password
		Given I follow "Change Password"
		And I fill in "Password" with "newsecret"
		And I fill in "Confirm Password" with "newsecret"
		And I press the "Save" button
		And I am not logged in
		When I log in with "joe@test.com" and "newsecret"
		And I go to "admin/myprofile"
		Then I should see the CMS

	Scenario: I can change the interface language
		And I select "German (Germany)" from "Interface Language"
		And I press the "Save" button
		Then I should see "Sprache"

  # TODO Date/time format - Difficult because its not exposed anywhere in the CMS?
  # TODO Group modification as ADMIN user