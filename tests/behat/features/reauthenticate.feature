@modal @retry
Feature: Reauthenticate
  As a content editor
  I want to be able to log in through a CMS popup when my session expires
  So that I can avoid losing unsaved work

  Background:
    And I am logged in with "ADMIN" permissions
    And I go to "/admin/security"
    And I am not in an iframe
    And I click the "Users" CMS tab
    And my session expires

  Scenario: Reauthenticate with correct login
    When I press the "Add Member" button
      And I switch to the "login-dialog-iframe" iframe
    Then I should see "Your session has timed out due to inactivity" in the ".cms-security__container" element
    When I fill in "Password" with "Secret!123"
      And I press the "Let me back in" button
      And I am not in an iframe
      And I click "ADMIN" in the "#Root_Users" element
    Then I should see "Save" in the "#Form_ItemEditForm_action_doSave" element

  Scenario: Reauthenticate with wrong login
    When I press the "Add Member" button
      And I switch to the "login-dialog-iframe" iframe
    Then I should see "Your session has timed out due to inactivity" in the ".cms-security__container" element
    When I fill in "Password" with "wrong password"
      And I press the "Let me back in" button
    Then I should see "The provided details don't seem to be correct. Please try again."
    When I fill in "Password" with "Secret!123"
      And I press the "Let me back in" button
      And I am not in an iframe
      And I click "ADMIN" in the "#Root_Users" element
    Then I should see "Save" in the "#Form_ItemEditForm_action_doSave" element
