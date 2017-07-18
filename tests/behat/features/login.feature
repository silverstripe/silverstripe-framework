@retry
Feature: Log in
  As an site owner
  I want to access to the CMS to be secure
  So that only my team can make content changes

  Scenario: Bad login
    Given I log in with "bad@example.com" and "badpassword"
    Then I should see "The provided details don't seem to be correct"

  Scenario: Valid login
    Given I am logged in with "ADMIN" permissions
    When I go to "/admin/"
    Then I should see the CMS

  Scenario: /admin/ redirect for not logged in user
    # disable automatic redirection so we can use the profiler
    When I go to "/admin/"
    And I should see a log-in form

  Scenario: Logout without token
    Given I am logged in with "ADMIN" permissions
    When I go to "/Security/logout"
    Then I should see a log-out form
    When I press the "Log out" button
    And I go to "/admin/"
    Then I should see a log-in form

  Scenario: Log in as someone else
    Given I am logged in with "ADMIN" permissions
    When I go to "/Security/login"
    Then the response should contain "Log in as someone else"

    When I press the "Log in as someone else" button
    Then I should see a log-in form
