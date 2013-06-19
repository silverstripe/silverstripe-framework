Feature: My Profile
  As a CMS user
  I want to be able to change personal settings
  In order to streamline my CMS experience

  @javascript
  Scenario: I can see date formatting help
    Given I am logged in with "ADMIN" permissions
    # Only tests this specific field and admin UI because its got built-in tooltips
    When I go to "/admin/myprofile"
    And I follow "Show formatting help"
    Then I should see "Four-digit year"