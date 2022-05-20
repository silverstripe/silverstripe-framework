---
title: CMS form field help text
summary: Add help text to the form fields in the CMS
icon: question
---
# How to Show Help Text on CMS Form Fields

Sometimes you need to express more context for a form field
than is suitable for its `<label>` element.
The CMS provides you with an easy way to transform
form field attributes into help text
shown alongside the field, a tooltip which shows on demand, or toggleable description text.

The `FormField->setDescription()` method will add a `<span class="description">`
at the last position within the field, and expects unescaped HTML content.


```php
use SilverStripe\Forms\TextField;

TextField::create('MyText', 'My Text Label')
    ->setDescription('More <strong>detailed</strong> help');
```

Sometimes a field requires a longer description to provide the user with context. Another option you have available is making the field's description togglable. This keeps
the UI tidy by hiding the description until the user requests more information
by clicking the 'info' icon displayed alongside the field.


```php
TextField::create('MyText', 'My Text Label')
    ->setDescription('More <strong>detailed</strong> help')
    ->addExtraClass('cms-description-toggle');
```

Note: For more advanced help text we recommend using
[Custom form field templates](/developer_guides/forms/form_templates);
