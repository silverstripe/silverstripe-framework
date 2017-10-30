@retry
Feature: Lost Password
  As a site owner
  I want to be able to reset my password
  Using my email

  Background:
    Given a "member" "Admin" with "Email"="admin@example.org"

  Scenario: I can request a password reset by email
    Given I go to "Security/login"
    When I follow "I've lost my password"
    And I fill in "admin@example.org" for "Email"
    And I press the "Send me the password reset link" button
    Then I should see "A reset link has been sent to 'admin@example.org'"
    And there should be an email to "admin@example.org" titled "Your password reset link"
    When I click on the "password reset link" link in the email to "admin@example.org"
    Then I should see "Please enter a new password"
    When I fill in "newpassword" for "New Password"
    And I fill in "newpassword" for "Confirm New Password"
    And I press the "Change Password" button
    Then the password for "admin@example.org" should be "newpassword"
