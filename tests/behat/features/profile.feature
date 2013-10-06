Feature: My Profile
  As a CMS user
  I want to be able to change personal settings
  In order to streamline my CMS experience

  Background:
    Given I am logged in with "ADMIN" permissions

  @javascript
  Scenario: I can see date formatting help
    # Only tests this specific field and admin UI because its got built-in tooltips
    Given I go to "/admin/myprofile"
    And I follow "Show formatting help"
    Then I should see "Four-digit year"

  Scenario: I can change the interface language
    Given I go to "/admin/myprofile"
    Then I should see "My Profile"
    When I fill in "German (Germany)" for the "Interface Language" dropdown
    And I press the "Save" button
    Then I should see "Mein Profil"