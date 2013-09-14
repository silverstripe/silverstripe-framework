Feature: Apply rich formatting to content
  As a cms author
  I want to work with content in the way I'm used to from word processing software
  So that I make it more appealing by creating structure and highlights

  Background:
    Given a "page" "About Us" has the "Content" "<h1>My awesome headline</h1><p>Some amazing content</p>"
    And I am logged in with "ADMIN" permissions
    And I go to "/admin/pages"
    Then I click on "About Us" in the tree

  Scenario: I can control alignment of selected content
    Given I select "My awesome headline" in the "Content" HTML field
    When I press the "Align Right" button
    Then "My awesome headline" in the "Content" HTML field should be right aligned
    But "Some amazing content" in the "Content" HTML field should be left aligned
    Then I press the "Save draft" button
    Then "My awesome headline" in the "Content" HTML field should be right aligned

  Scenario: I can bold selected content
    Given I select "awesome" in the "Content" HTML field
    When I press the "Bold (Ctrl+B)" button
    Then "awesome" in the "Content" HTML field should be bold
    But "My" in the "Content" HTML field should not be bold
    When I press the "Save draft" button
    Then "awesome" in the "Content" HTML field should be bold
    But "My" in the "Content" HTML field should not be bold
    