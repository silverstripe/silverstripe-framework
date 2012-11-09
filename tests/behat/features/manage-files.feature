@javascript @assets
Feature: Manage files
  As a cms author
  I want to upload and manage files within the CMS
  So that I can insert them into my content efficiently

  Background:
    # Idea: We could weave the database reset into this through
    # saying 'Given there are ONLY the following...'.
    Given there are the following Folder records
    """
    folder1:
        Filename: assets/folder1
    folder1.1:
        Filename: assets/folder1/folder1.1
        Parent: =>Folder.folder1
    folder2:
        Filename: assets/folder2
        Name: folder2
    """
    And there are the following File records
    """
    file1:
        Filename: assets/folder1/file1.jpg
        Name: file1.jpg
        Parent: =>Folder.folder1
    file2:
        Filename: assets/folder1/folder1.1/file2.jpg
        Name: file2.jpg
        Parent: =>Folder.folder1.1
    """
    And I am logged in with "ADMIN" permissions
    # Alternative fixture shortcuts, with their titles
    # as shown in admin/security rather than technical permission codes.
    # Just an idea for now, could be handled by YAML fixtures as well
#    And I am logged in with the following permissions
#      - Access to 'Pages' section
#      - Access to 'Files' section
    And I go to "/admin/assets"

  @modal
  Scenario: I can add a new folder
    Given I press the "Add folder" button
    And I type "newfolder" into the dialog
    And I confirm the dialog
    Then the "Files" table should contain "newfolder"

  Scenario: I can list files in a folder
    Given I click on "folder1" in the "Files" table
    Then the "folder1" table should contain "file1"
    And the "folder1" table should not contain "file1.1"

  Scenario: I can upload a file to a folder
    Given I click on "folder1" in the "Files" table
    And I press the "Upload" button
    And I attach the file "testfile.jpg" to "AssetUploadField" with HTML5
    And I wait for 5 seconds
    And I press the "Back to folder" button
    Then the "folder1" table should contain "testfile"

  Scenario: I can edit a file
    Given I click on "folder1" in the "Files" table
    And I click on "file1" in the "folder1" table
    And I fill in "renamedfile" for "Title"
    And I press the "Save" button
    And I press the "Back" button
    Then the "folder1" table should not contain "testfile"
    And the "folder1" table should contain "renamedfile"

  Scenario: I can delete a file
    Given I click on "folder1" in the "Files" table
    And I click on "file1" in the "folder1" table
    And I press the "Delete" button
    Then the "folder1" table should not contain "file1"

  Scenario: I can change the folder of a file
    Given I click on "folder1" in the "Files" table
    And I click on "file1" in the "folder1" table
    And I fill in =>Folder.folder2 for "ParentID"
    And I press the "Save" button
    # /show/0 is to ensure that we are on top level folder
    And I go to "/admin/assets/show/0"
    And I click on "folder2" in the "Files" table
    And the "folder2" table should contain "file1"