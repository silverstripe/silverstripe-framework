@assets
Feature: Insert a url link into content using tinymce insert link button
As a cms author
I want to insert a link into my content
So that I can link to a external website or a page on my site

Background:
Given a "page" "About Us"
Given I am logged in with "ADMIN" permissions
And I go to "/admin/pages"
Then I should see "About Us" in CMS Tree

@javascript
Scenario: I can select text within the content and apply an internal sitetree url link using the add url button
When I follow "About Us"
Then I should see an edit page form

When I highlight the text "pages" 
And the "Insert Link" button activates
When I press the "Insert Link" button
Then I should see "Form_EditorToolbarLinkForm"

When I check the "Form_EditorToolbarLinkForm_LinkType_internal" radio button
And I select "home" in "treedropdownfield-title" field
And I enter "Test Link Description" in "Form_EditorToolbarLinkForm_Description" field
And I check the "Form_EditorToolbarLinkForm_TargetBlank" tickbox
And I press the "Form_EditorToolbarLinkForm_action_insert" button
Then I should see the "content" HTML field contains "pages" with tag "<a href="[sitetree_link,id=1]">pages</a>"

# Required to avoid "unsaved changed" browser dialog
Then I press the "Save draft" button

@javascript
Scenario: I can select text within the content and apply an external url link using the add url button
When I follow "About Us"
Then I should see an edit page form

When I highlight the text "pages" 
And the "Insert Link" button activates
When I press the "Insert Link" button
Then I should see "Form_EditorToolbarLinkForm"

When I check the "Form_EditorToolbarLinkForm_LinkType_external" radio button
And I enter "http://silverstripe.com" in "Form_EditorToolbarLinkForm_external" field
And I enter "Test Link Description" in "Form_EditorToolbarLinkForm_Description" field
And I check the "Form_EditorToolbarLinkForm_TargetBlank" tickbox
And I press the "Form_EditorToolbarLinkForm_action_insert" button
Then I should see the "content" HTML field contains "pages" with tag "<a href="http://www.silverstripe.com">pages</a>"

# Required to avoid "unsaved changed" browser dialog
Then I press the "Save draft" button