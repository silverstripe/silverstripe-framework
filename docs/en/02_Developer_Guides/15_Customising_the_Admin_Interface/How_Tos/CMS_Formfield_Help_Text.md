# How to Show Help Text on CMS Form Fields

Sometimes you need to express more context for a form field
than is suitable for its `<label>` element.
The CMS provides you with an easy way to transform
form field attributes into help text
shown alongside the field, a tooltip which shows on demand, or toggleable description text.

The `FormField->setDescription()` method will add a `<span class="description">`
at the last position within the field, and expects unescaped HTML content.

	:::php
	TextField::create('MyText', 'My Text Label')
		->setDescription('More <strong>detailed</strong> help');

To show the help text as a tooltip instead of inline,
add a `.cms-description-tooltip` class.

	:::php
	TextField::create('MyText', 'My Text Label')
		->setDescription('More <strong>detailed</strong> help')
		->addExtraClass('cms-description-tooltip');

Tooltips are only supported
for native, focusable input elements, which excludes
more complex fields like `GridField`, `UploadField`
or `DropdownField` with the chosen.js behaviour applied.

Sometimes a field requires a longer description to provied the user with context.
Tooltips can be unwieldy when dealing with large blocks of text, especially if
you're including interactive elements like links.

Another option you have available is making the field's description togglable. This keeps
the UI tidy by hiding the description until the user requests more information
by clicking the 'info' icon displayed alongside the field.

	:::php
	TextField::create('MyText', 'My Text Label')
		->setDescription('More <strong>detailed</strong> help')
		->addExtraClass('cms-description-toggle');

If you want to provide a custom icon for toggling the description, you can do that
by setting an additional `RightTitle`.

	:::php
	TextField::create('MyText', 'My Text Label')
		->setDescription('More <strong>detailed</strong> help')
		->addExtraClass('cms-description-toggle')
		->setRightTitle('<a class="cms-description-trigger">My custom icon</a>');

Note: For more advanced help text we recommend using
[Custom form field templates](../form_templates);
