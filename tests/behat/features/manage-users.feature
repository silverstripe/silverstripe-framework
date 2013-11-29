@javascript
Feature: Manage users
  As a site administrator
  I want to create and manage user accounts on my site
  So that I can control access to the CMS

  Background:
    Given a "member" "ADMIN" belonging to "ADMIN Group" with "Email"="admin@test.com"
    And a "member" "Staff" belonging to "Staff Group" with "Email"="staffmember@test.com"
    And the "group" "ADMIN group" has permissions "Full administrative rights"
    And I am logged in with "ADMIN" permissions
    And I go to "/admin/security"

  Scenario: I can list all users regardless of group
    When I click the "Users" CMS tab
    Then I should see "admin@test.com" in the "#Root_Users" element
    And I should see "staffmember@test.com" in the "#Root_Users" element

  Scenario: I can list all users in a specific group
    When I click the "Groups" CMS tab
    # TODO Please check how performant this is
    And I click "ADMIN group" in the "#Root_Groups" element
    Then I should see "admin@test.com" in the "#Root_Members" element
    And I should not see "staffmember@test.com" in the "#Root_Members" element

  Scenario: I can add a user to the system
    When I click the "Users" CMS tab
    And I press the "Add Member" button
    And I fill in the following:
      | First Name | John |
      | Surname | Doe |
      | Email | john.doe@test.com |
    And I press the "Create" button
    Then I should see a "Saved member" message

    When I go to "admin/security/"
    Then I should see "john.doe@test.com" in the "#Root_Users" element

  Scenario: I can edit an existing user and add him to an existing group
    When I click the "Users" CMS tab
    And I click "staffmember@test.com" in the "#Root_Users" element
    And I select "ADMIN group" from "Groups"
    And I press the "Save" button
    Then I should see a "Saved Member" message

    When I go to "admin/security"
    And I click the "Groups" CMS tab
    And I click "ADMIN group" in the "#Root_Groups" element
    Then I should see "staffmember@test.com"

  Scenario: I can delete an existing user
    When I click the "Users" CMS tab
    And I click "staffmember@test.com" in the "#Root_Users" element
    And I press the "Delete" button, confirming the dialog
    Then I should see "admin@test.com"
    And I should not see "staffmember@test.com"
