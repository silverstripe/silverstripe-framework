@javascript @retry
Feature: Manage users
  As a site administrator
  I want to create and manage user accounts on my site
  So that I can control access to the CMS

  Background:
    Given a "member" "ADMIN" belonging to "ADMIN group" with "Email"="admin@example.org"
    And the "member" "ADMIN" belonging to "ADMIN group2"
    And a "member" "Staff" belonging to "Staff group" with "Email"="staffmember@example.org"
    And the "group" "ADMIN group" has permissions "Full administrative rights"
    And the "group" "ADMIN group2" has permissions "Full administrative rights"
    And I am logged in with "ADMIN" permissions
    And I go to "/admin/security"


  Scenario: I cannot remove my admin access, but can remove myself from an admin group
    When I click the "Groups" CMS tab
      And I click "ADMIN group" in the "#Root_Groups" element
      And I should see the "Unlink" button in the "Members" gridfield for the "ADMIN" row
    Then I click "Groups" in the ".breadcrumbs-wrapper" element
      And I click the "Groups" CMS tab
      And I click "ADMIN group2" in the "#Root_Groups" element
      And I should see the "Unlink" button in the "Members" gridfield for the "ADMIN" row
    Then I click the "Unlink" button in the "Members" gridfield for the "ADMIN" row
      And I should not see the "Unlink" button in the "Members" gridfield for the "ADMIN" row
    Then I click "Groups" in the ".breadcrumbs-wrapper" element
      And I click the "Groups" CMS tab
      And I click "ADMIN group" in the "#Root_Groups" element
      And I should not see the "Unlink" button in the "Members" gridfield for the "ADMIN" row

  Scenario: I can list all users regardless of group
    When I click the "Users" CMS tab
    Then I should see "admin@example.org" in the "#Root_Users" element
    And I should see "staffmember@example.org" in the "#Root_Users" element

  Scenario: I can list all users in a specific group
    When I click the "Groups" CMS tab
    # TODO Please check how performant this is
    And I click "ADMIN group" in the "#Root_Groups" element
    Then I should see "admin@example.org" in the "#Root_Members" element
    And I should not see "staffmember@example.org" in the "#Root_Members" element

  Scenario: I can add a user to the system
    When I click the "Users" CMS tab
    And I press the "Add Member" button
    And I fill in the following:
      | First Name | John |
      | Surname | Doe |
      | Email | john.doe@example.org |
    And I press the "Create" button
    Then I should see a "Saved member" message

    When I go to "admin/security/"
    Then I should see "john.doe@example.org" in the "#Root_Users" element

  Scenario: I can edit an existing user and add him to an existing group
    When I click the "Users" CMS tab
    And I click "staffmember@example.org" in the "#Root_Users" element
    And I select "ADMIN group" from "Groups"
    And I press the "Save" button
    Then I should see a "Saved Member" message

    When I go to "admin/security"
    And I click the "Groups" CMS tab
    And I click "ADMIN group" in the "#Root_Groups" element
    Then I should see "staffmember@example.org"

  Scenario: I can delete an existing user
    When I click the "Users" CMS tab
    And I click "staffmember@example.org" in the "#Root_Users" element
    And I press the "Delete" button, confirming the dialog
    Then I should see "admin@example.org"
    And I should not see "staffmember@example.org"
