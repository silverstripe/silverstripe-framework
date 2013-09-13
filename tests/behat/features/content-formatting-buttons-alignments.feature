@assets
Feature: Using tinymce buttons to edit formatting of text content
As a cms author
I want to format my content to left, right and centre aligned
So that I can create basic formatted content

Background:
Given a "page" "About Us"
Given I am logged in with "ADMIN" permissions
Given "About Us" has text in content "You can fill this page out with your own content, or delete it and create your own pages."
And I go to "/admin/pages"
Then I should see "About Us" in CMS Tree

@javascript
Scenario: I can select text within the content and apply right alignment formatting using a buttton in the HTML Editor
When I follow "About Us"
Then I should see an edit page form
And I should see HTML editor field

When I highlight the content all text "You can fill this page out with your own content, or delete it and create your own pages." 
When I press the "mceIcon mce_justifyright" button
Then I should see content text "You" is set to "<p style="text-align: right;">You can fill this page out with your own content, or delete it and create your own pages.</a></p>"

# Required to avoid "unsaved changed" browser dialog
Then I press the "Save draft" button
Then I should see content text "You" is set to "<p style="text-align: right;">You can fill this page out with your own content, or delete it and create your own pages.</a></p>"

@javascript
Scenario: I can select text within the content and apply centre alignment formatting using a buttton in the HTML Editor
When I follow "About Us"
Then I should see an edit page form
And I should see HTML editor field

When I highlight the content all text "You can fill this page out with your own content, or delete it and create your own pages." 
When I press the "mceIcon mce_justifycenter" button
Then I should see content text "You" is set to "<p style="text-align: center;">You can fill this page out with your own content, or delete it and create your own pages.</a></p>"

# Required to avoid "unsaved changed" browser dialog
Then I press the "Save draft" button
Then I should see content text "You" is set to "<p style="text-align: center;">You can fill this page out with your own content, or delete it and create your own pages.</a></p>"


@javascript
Scenario: I can select text within the content and apply left alignment formatting using a buttton in the HTML Editor
When I follow "About Us"
Then I should see an edit page form
And I should see HTML editor field

When I highlight the content all text "You can fill this page out with your own content, or delete it and create your own pages." 
When I press the "mceIcon mce_justifyleft" button
Then I should see content text "You" is set to "<p style="text-align: left;">You can fill this page out with your own content, or delete it and create your own pages.</a></p>"

# Required to avoid "unsaved changed" browser dialog
Then I press the "Save draft" button
Then I should see content text "You" is set to "<p style="text-align: left;">You can fill this page out with your own content, or delete it and create your own pages.</a></p>"