@database-defaults
Feature: Admin tooltips
  As an author
  I want to be able to click on help icon links
  So that I can see a jQuery UI tooltip with more information

  @javascript
  Scenario: I can see a tooltip when clicking on the help icon next to a form field
    Given I am logged in with "ADMIN" permissions
    When I go to "/admin/security"
    And I click "Default Admin" in the "#Form_EditForm_Members" element
    Then I click "Toggle formatting help" in the "#Form_ItemEditForm_DateFormat" element
    And I see a tooltip on the element "#Form_ItemEditForm_DateFormat" with the "Toggle formatting help" text 
    And I click "Toggle formatting help" in the "#Form_ItemEditForm_TimeFormat" element
    And I see a tooltip on the element "#Form_ItemEditForm_TimeFormat" with the "Toggle formatting help" text 
