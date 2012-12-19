@database-defaults
Feature: Display valid upload extensions
  As a CMS user
  I want to be able to view the valid upload extensions
  In order to streamline my CMS experience

  @javascript
  Scenario: I can see allowed extensions help
    Given I am logged in with "ADMIN" permissions
    # Only tests this specific field and admin UI because its got built-in tooltips
    When I go to "/admin/assets/add"
    And I follow "Show allowed extensions"
    Then I should see "png,"