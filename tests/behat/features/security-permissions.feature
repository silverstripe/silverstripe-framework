@javascript
Feature: Manage Security Permissions for Groups
  As a site administrator
  I want to control my user's security permissions in an intuitive way
  So that I can easily control access to the CMS

  Background:
    Given a "member" "ADMIN" belonging to "ADMIN Group" with "Email"="admin@test.com"
    And the "group" "ADMIN group" has permissions "Full administrative rights"
    And I am logged in with "ADMIN" permissions
    And I go to "/admin/security"

  Scenario: I can change permissions easily
    When I click the "Groups" CMS tab
    And I press the "Add Group" button
    And I click the "Permissions" CMS tab
    And I check "Access to 'Pages' section"
    And I check "Access to all CMS sections"
    Then the "Access to 'Reports' section" checkbox should be checked
    And I uncheck "Access to all CMS sections"
    Then the "Access to 'Reports' section" checkbox should not be checked
    And the "Access to 'Pages' section" checkbox should be checked
    And I uncheck "Access to 'Pages' section"
    And I check "Full administrative rights"
    Then the "Access to 'Reports' section" checkbox should be checked
    And I press the "Create" button
    And I click the "Permissions" CMS tab
    Then the "Full administrative rights" checkbox should be checked
    And the "Access to 'Pages' section" checkbox should be checked
    And I uncheck "Full administrative rights"
    Then the "Access to 'Pages' section" checkbox should not be checked
    And I check "Access to all CMS sections"
    And I press the "Save" button
    Then the "Access to 'Pages' section" checkbox should be checked
    And I uncheck "Access to all CMS sections"
    Then the "Access to 'Pages' section" checkbox should not be checked
