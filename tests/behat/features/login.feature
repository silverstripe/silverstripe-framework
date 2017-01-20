# features/login.feature
Feature: Log in
  As an site owner
  I want to access to the CMS to be secure
  So that only my team can make content changes

  Scenario: Bad login
    Given I log in with "bad@example.com" and "badpassword"
    Then I will see a "error" log-in message

  Scenario: Valid login
    Given I am logged in with "ADMIN" permissions
    When I go to "/admin/"
    Then I should see the CMS

  Scenario: /admin/ redirect for not logged in user
    # disable automatic redirection so we can use the profiler
    When I go to "/admin/"
    And I should see a log-in form
