@todo
Feature: Manage my own settings
  As a CMS user
  I want to be able to change personal settings
  In order to streamline my CMS experience

  Background:
    Given a "member" "Joe" belongs to "Admin Group" with "Email"="joe@test.com" and "Password"="secret"
    And the "group" "Admin Group" has permissions "Full administrative rights"
    And I am logged in with "joe@test.com" and "secret"
    And I navigate to "admin/myprofile"

  Scenario: I can edit my personal details
    Given I fill in "First Name" with "Jack"
    And I fill in "Surname" with "Johnson"
    And I fill in "Email" with "jack@test.com"
    When I press the "Save" button
    Then I should not see "John"
    But I should see "Jack"
    And I should see "Johnson"
    And I should see "jack@test.com"

  Scenario: I can change my password
    Given I click "Change Password"
    And I fill out "Password" with "newsecret"
    And I fill out "Confirm Password" with "newsecret"
    And I press the "Save" button
    And I log out
    When I login with "joe@test.com" and "newsecret"
    Then I should see the CMS

  Scenario: I can change the interface language
    Given I fill in "Interface Language" with "German (Germany)"
    And I press the "Save" button
    Then I should see "Sprache"

  # TODO Date/time format - Difficult because its not exposed anywhere in the CMS?
  # TODO Group modification as ADMIN user