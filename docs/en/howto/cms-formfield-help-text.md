# How to Show Help Text on CMS Form Fields

Sometimes you need to express more context for a form field
than is suitable for its `<label>` element.
The CMS provides you with an easy way to transform
form field attributes into either help text
shown alongside the field, or a tooltip which shows on demand.

The `FormField->setDescription()` method will set a
`title` attribute, and expects unescaped HTML content.
The attribute will be set on the `<input>` or `<select>`
element for most form fields, although some might set it
on the field container instead.

In order to trigger the behaviour, you also need to add a
`.cms-help` class to the field.

	:::php
	TextField::create('MyText', 'My Text Label')
		->setDescription('More <strong>detailed</strong> help')
		->addExtraClass('cms-help');

To show the help text as a tooltip instead of inline,
add a `.cms-help-tooltip` class. 

	:::php
	TextField::create('MyText', 'My Text Label')
		->setDescription('More <strong>detailed</strong> help')
		->addExtraClass('cms-help cms-help-tooltip');

Tooltips are only supported
for native, focusable input elements, which excludes
more complex fields like `GridField`, `UploadField`
or `DropdownField` with the chosen.js behaviour applied.

Note: For more advanced help text we recommend using
[Custom form field templates](/topics/forms#custom-form-field-templates);