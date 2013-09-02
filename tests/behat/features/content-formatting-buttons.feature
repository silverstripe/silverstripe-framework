@assets
Feature: Using tinymce buttons to edit formatting of text content
As a cms author
I want to format my content to Bold, Italics, strikethrough and underline
So that I can create basic formatted content

Background:
Given a "page" "About Us"
Given I am logged in with "ADMIN" permissions
Given "About Us" has text in content "You can fill this page out with your own content, or delete it and create your own pages."
And I go to "/admin/pages"
Then I should see "About Us" in CMS Tree

@javascript
Scenario: I can select text within the content and apply bold formatting using a buttton in the HTML Editor
When I follow "About Us"
Then I should see an edit page form
And I should see HTML editor field

When I highlight the content text "You" 
When I press the "mceIcon mce_bold" button

# Required to avoid "unsaved changed" browser dialog
Then I press the "Save draft" button
Then I should see content text "You" is set to "<strong>You</strong>"

@javascript
Scenario: I can select text within the content and apply italic formatting using a buttton in the HTML Editor
When I follow "About Us"
Then I should see an edit page form
And I should see HTML editor field

When I highlight the content text "You" 
When I press the "mceIcon mce_italic" button

# Required to avoid "unsaved changed" browser dialog
Then I press the "Save draft" button
Then I should see content text "You" is set to "<em>You</em>"

@javascript
Scenario: I can select text within the content and apply underline formatting using a buttton in the HTML Editor
When I follow "About Us"
Then I should see an edit page form
And I should see HTML editor field

When I highlight the content text "You" 
When I press the "mceIcon mce_underline" button
Then I should see content text "You" is set to "<span style="text-decoration: underline;">You</span>"

# Required to avoid "unsaved changed" browser dialog
Then I press the "Save draft" button
Then I should see content text "You" is set to "<span style="text-decoration: underline;">You</span>"

@javascript
Scenario: I can select text within the content and apply strikethrough formatting using a buttton in the HTML Editor
When I follow "About Us"
Then I should see an edit page form
And I should see HTML editor field

When I highlight the content text "You" 
When I press the "mceIcon mce_strikethrough" button
Then I should see content text "You" is set to "<span style="text-decoration: line-through;">You</span>"

# Required to avoid "unsaved changed" browser dialog
Then I press the "Save draft" button
Then I should see content text "You" is set to "<span style="text-decoration: line-through;">You</span>"

