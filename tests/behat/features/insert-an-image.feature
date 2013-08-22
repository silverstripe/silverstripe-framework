@assets
Feature: Insert an image into a page
As a cms author
I want to insert an image into a page
So that I can insert them into my content efficiently

Background:
Given a "page" "About Us"
Given I am logged in with "ADMIN" permissions
And I go to "/admin/pages"
Then I should see "About Us" in CMS Tree

@javascript
Scenario: I can insert images into the content
When I follow "About Us"
Then I should see an edit page form

When I press the "Insert Media" button
Then I should see "Choose files to upload..."

When I press the "From the web" button
And I fill in "RemoteURL" with "http://www.silverstripe.com/themes/sscom/images/silverstripe_logo_web.png"
And I press the "Add url" button
Then I should see "silverstripe_logo_web.png (www.silverstripe.com)" in the ".ss-assetuploadfield span.name" element

When I press the "Insert" button  
Then the "Content" HTML field should contain "silverstripe_logo_web.png"
# Required to avoid "unsaved changed" browser dialog
Then I press the "Save draft" button