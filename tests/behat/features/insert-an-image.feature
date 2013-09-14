@assets
Feature: Insert an image into a page
  As a cms author
  I want to insert an image into a page
  So that I can insert them into my content efficiently

  Background:
    Given a "page" "About Us"
    #And a "file" "assets/folder1/file1.jpg"
    #And a "file" "assets/folder1/file3.jpg"
    #And a "file" "assets/folder1/folder1.1/file2.jpg"
    #And a "folder" "assets/folder2"
    And I am logged in with "ADMIN" permissions
    And I go to "/admin/pages"
    And I click on "About Us" in the tree

  Scenario: I can insert an image from a URL
    Given I press the "Insert Media" button
    Then I should see "Choose files to upload..."

    When I press the "From the web" button
    And I fill in "RemoteURL" with "http://www.silverstripe.com/themes/sscom/images/silverstripe_logo_web.png"
    And I press the "Add url" button
    Then I should see "silverstripe_logo_web.png (www.silverstripe.com)" in the ".ss-assetuploadfield span.name" element

    When I press the "Insert" button  
    Then the "Content" HTML field should contain "silverstripe_logo_web.png"
    # Required to avoid "unsaved changed" browser dialog
    Then I press the "Save draft" button

  @todo
  Scenario: I can insert an image uploaded from my own computer
    Given I press the "Insert Media" button
    And I press the "From your computer" button
    # TODO Figure out how to provide the file
    And I attach the file "testfile.jpg" to "AssetUploadField" with HTML5
    Then the upload field should have successfully uploaded "testfile.jpg"
    When I press the "Insert" button
    Then the "Content" HTML field should contain "testfile.jpg"

  @todo
  Scenario: I can insert an image from the CMS file store
    Given I press the "Insert Media" button
    And I press the "From the CMS" button
    And I select "folder1" in the "Find in Folder" dropdown
    And I select "file1.jpg"
    When I press the "Insert" button
    Then the "Content" HTML field should contain "file1.jpg"

  @todo
  Scenario: I can insert multiple images at once
    Given I press the "Insert Media" button
    And I press the "From the CMS" button
    And I select "folder1" in the "Find in Folder" dropdown
    And I select "file1.jpg"
    And I select "file3.jpg"
    When I press the "Insert" button
    Then the "Content" HTML field should contain "file1.jpg"
    And the "Content" HTML field should contain "file1.jpg"

  @todo
  Scenario: I can edit properties of an image before inserting it
    Given I press the "Insert Media" button
    And I press the "From the CMS" button
    And I select "folder1" in the "Find in Folder" dropdown
    And I select "file1.jpg"
    And I follow "Edit"
    When I fill in "Alternative text (alt)" with "My alt"
    And I press the "Insert" button
    Then the "Content" HTML field should contain "file1.jpg"
    And the "Content" HTML field should contain "My alt"

  @todo
  Scenario: I can edit dimensions of an image before inserting it
    Given I press the "Insert Media" button
    And I press the "From the CMS" button
    And I select "folder1" in the "Find in Folder" dropdown
    And I select "file1.jpg"
    And I follow "Edit"
    When I fill in "Width" with "10"
    When I fill in "Height" with "20"
    And I press the "Insert" button
    Then the "Content" HTML field should contain "<img src=assets/folder1/file1.jpg width=10 height=20>"

  @todo
  Scenario: I can edit dimensions of an existing image
    Given the "page" "About us" contains "<img src=assets/folder1/file1.jpg>"
    And I reload the current page
    When I highlight "<img src=assets/folder1/file1.jpg>" in the "Content" HTML field
    And I press the "Insert Media" button
    Then I should see "file1.jpg"
    When I fill in "Width" with "10"
    When I fill in "Height" with "20"
    And I press the "Insert" button
    Then the "Content" HTML field should contain "<img src=assets/folder1/file1.jpg width=10 height=20>"