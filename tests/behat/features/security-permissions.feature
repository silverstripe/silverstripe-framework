@javascript
Feature: Manage Security Permissions for Groups
  As a site administrator
  I want to control my user's security permissions in an intuitive way
  So that I can easily control access to the CMS

  Background:
    Given a "group" "test group"
    And a "member" "ADMIN" belonging to "ADMIN Group" with "Email"="admin@test.com"
    And the "group" "ADMIN group" has permissions "Full administrative rights"
    And I am logged in with "ADMIN" permissions
    And I go to "/admin/security"
    And I click the "Groups" CMS tab
    And I click on "test group" in the "Groups" table
    And I click the "Permissions" CMS tab

  Scenario: I can see sub-permissions being properly set and restored when using "Access to all CMS sections"
    When I check "Access to all CMS sections"
    Then the "Access to 'Security' section" checkbox should be checked
    And the "Access to 'Security' section" field should be disabled

    When I uncheck "Access to all CMS sections"
    Then the "Access to 'Security' section" checkbox should not be checked
    And the "Access to 'Security' section" field should be enabled

    When I check "Access to 'Security' section"
    And I check "Access to all CMS sections"
    When I uncheck "Access to all CMS sections"
    Then the "Access to 'Security' section" checkbox should be checked

    # Save so the driver can reset without having to deal with the popup alert.
    Then I press the "Save" button

  Scenario: I can see sub-permissions being properly set and restored when using "Full administrative rights"
    When I check "Access to 'Security' section"
    And I check "Full administrative rights"
    Then the "Access to all CMS sections" checkbox should be checked
    And the "Access to all CMS sections" field should be disabled
    And the "Access to 'Security' section" checkbox should be checked
    And the "Access to 'Security' section" field should be disabled

    And I uncheck "Full administrative rights"
    Then the "Access to all CMS sections" checkbox should not be checked
    And the "Access to all CMS sections" field should be enabled
    And the "Access to 'Security' section" checkbox should be checked
    And the "Access to 'Security' section" field should be enabled

    # Save so the driver can reset without having to deal with the popup alert.
    Then I press the "Save" button

  Scenario: I can see sub-permissions being handled properly between reloads when using "Full administrative rights"
    When I check "Full administrative rights"
    And I press the "Save" button
    And I click the "Permissions" CMS tab
    Then the "Full administrative rights" checkbox should be checked
    And the "Access to 'Security' section" checkbox should be checked
    And the "Access to 'Security' section" field should be disabled

    When I uncheck "Full administrative rights"
    Then the "Access to 'Security' section" checkbox should not be checked
    And the "Access to 'Security' section" field should be enabled

    When I press the "Save" button
    And I click the "Permissions" CMS tab
    Then the "Full administrative rights" checkbox should not be checked
    And the "Access to 'Security' section" checkbox should not be checked
    And the "Access to 'Security' section" field should be enabled

  Scenario: I can see sub-permissions being handled properly between reloads when using "Access to all CMS sections"
    When I check "Access to all CMS sections"
    And I press the "Save" button
    And I click the "Permissions" CMS tab
    Then the "Access to all CMS sections" checkbox should be checked
    And the "Access to 'Security' section" checkbox should be checked
    And the "Access to 'Security' section" field should be disabled

    When I uncheck "Access to all CMS sections"
    Then the "Access to 'Security' section" checkbox should not be checked
    And the "Access to 'Security' section" field should be enabled

    When I press the "Save" button
    And I click the "Permissions" CMS tab
    Then the "Access to all CMS sections" checkbox should not be checked
    And the "Access to 'Security' section" checkbox should not be checked
    And the "Access to 'Security' section" field should be enabled
