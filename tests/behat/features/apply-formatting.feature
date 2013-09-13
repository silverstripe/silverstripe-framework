@todo
Feature: Apply rich formatting to content
  As a cms author
  I want to work with content in the way I'm used to from word processing software
  So that I make it more appealing by creating structure and highlights

  Background:
    Given a "page" "About Us"
    Given I am logged in with "ADMIN" permissions
    Given "About Us" has the Content
    """<h1>My awesome headline</1>
    <p>Some amazing content</p>"""
    And I go to "/admin/pages"
    Then I click on "About Us" in the tree
    And I focus the "Content" field

  Scenario: I can control alignment of selected content
    Given I highlight the content "My awesome headline"
    When I press the "Align Right" button in the HTML editor
    Then the content "My awesome headline" should be right aligned
    But the content "Some amazing content" should be left aligned
    Then I press the "Save draft" button in the HTML editor
    Then the content "My awesome headline" should still be right aligned

  Scenario: I can bold selected content
    Given I highlight the content "awesome"
    When I press the "Bold" button in the HTML editor
    Then the content "awesome" should be bold
    But the content "My" should not be bold
    When I press the "Save draft" button in the HTML editor
    Then the content "awesome" should still be bold
    But the content "My" should still not be bold
    