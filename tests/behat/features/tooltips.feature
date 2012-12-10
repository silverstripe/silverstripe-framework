@database-defaults
Feature: Tooltips
  As an author
  I want to be able to click on help icon links
  So that I can see a jQuery UI tooltip with more information

  @javascript
  Scenario: I can see a tooltip when clicking on the help icon next to a form field
    Given I am logged in with "ADMIN" permissions
    # Only tests this specific field and admin UI because its got built-in tooltips
    When I go to "/admin/security"
    And I click "Default Admin" in the "#Form_EditForm_Members" element
    And I fill in "TimeFormat_custom" with "HH:ii"
    And I wait for 1 seconds
    Then I should see "Four-digit year"