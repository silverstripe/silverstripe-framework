@assets 
Feature: Insert links into a page
As a cms author
I want to insert a link into my content
So that I can link to a external website or a page on my site

  Background:
    Given a "page" "Home"
    And a "page" "About Us" has the "Content" "My awesome content"
    #And a "file" "assets/folder1/file1.jpg"
    And I am logged in with "ADMIN" permissions
    And I go to "/admin/pages"
    And I click on "About Us" in the tree

  Scenario: I can link to an internal page
    Given I select "awesome" in the "Content" HTML field
    And I press the "Insert Link" button
    When I check "Page on the site"
    And I fill in the "Page" dropdown with "Home"
    And I fill in "my desc" for "Link description"
    And I press the "Insert" button
    # TODO Dynamic DB identifiers
    Then the "Content" HTML field should contain "<a title="my desc" href="[sitetree_link,id=1]">awesome</a>"
    # Required to avoid "unsaved changed" browser dialog
    Then I press the "Save draft" button

  @todo
  Scenario: I can link to an external URL
    Given I select "awesome" in the "Content" HTML field
    And I press the "Insert Link" button

    When I check "Another website"
    And I fill in "http://silverstripe.org" for "URL"
    And I check "Open link in a new window"
    And I press the "Insert" button
    Then the "Content" HTML field should contain "<a href="http://silverstripe.org" target="_blank">awesome</a>"
    # Required to avoid "unsaved changed" browser dialog
    Then I press the "Save draft" button

  @todo
  Scenario: I can link to a file
    Given I select "awesome" in the "Content" HTML field
    When I press the "Insert Link" button
    When I check "Download a file"
    And I fill in the "File" dropdown with "file1.jpg"
    And I press the "Insert link" button
    Then the "Content" HTML field should contain "<a href="assets/folder1/file1.jpg">awesome</a>"
    # Required to avoid "unsaved changed" browser dialog
    Then I press the "Save draft" button


  @todo
  Scenario: I can link to an anchor
    Given I fill in the "Content" HTML field with "My awesome content <a name=myanchor>"
    And I select "awesome" in the "Content" HTML field
    When I press the "Insert Link" button
    When I check "Anchor on this page"
    And I fill in the "Select an anchor" dropdown with "myanchor"
    And I press the "Insert link" button
    Then the "Content" HTML field should contain "<a href="#myanchor">awesome</a>"
    # Required to avoid "unsaved changed" browser dialog
    Then I press the "Save draft" button

  @todo
  Scenario: I can edit a link
    Given I fill in the "Content" HTML field with "My <a href="http://silverstripe.org">awesome</a> content"
    And I select "awesome" 
    When I press the "Insert Link" button
    And the "URL" field should contain "http://silverstripe.org"
    When I fill in "http://wordpress.org" for "URL"
    And I press the "Insert link" button
    Then the "Content" HTML field should contain "<a href="http://wordpress.org">awesome</a>"
    # Required to avoid "unsaved changed" browser dialog
    Then I press the "Save draft" button

  @todo
  Scenario: I can delete a link
    Given I fill in the "Content" HTML field with "My <a href="http://silverstripe.org">awesome</a> content"
    And I select "awesome" 
    When I press the "Insert Link" button
    And I press the "Remove link" button
    Then the "Content" HTML field should not contain "<a href="http://silverstripe.org">awesome</a>"
    # Required to avoid "unsaved changed" browser dialog
    Then I press the "Save draft" button