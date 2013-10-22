@todo
Feature: Lost Password
  As a site owner
  I want to be able to reset my password
  Using my email

  Background:
    Given a "member" "Admin" with "Email"="admin@test.com"

  Scenario: I can request a password reset by email
    Given I go to "Security/login"
    When I follow "I've lost my password"
    And I fill in "admin@test.com" for "Email"
    And I press the "Send me the password reset link" button
    Then I should see "Password reset link sent to 'admin@test.com'"
    And there should be an email to "admin@test.com" titled "Your password reset link"
    When I click on the "password reset link" link in the email to "admin@test.com"
    Then I should see "Please enter a new password"
    When I fill in "newpassword" for "New Password"
    And I fill in "newpassword" for "Confirm New Password"
    And I press the "Change Password" button
    Then the password for "admin@test.com" should be "newpassword"