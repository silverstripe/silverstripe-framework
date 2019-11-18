---
title: Form Templates
summary: Customize the generated HTML for a FormField or an entire Form.
icon: file-code
---

# Form Templates

Most markup generated in SilverStripe can be replaced by custom templates. Both [Form](api:SilverStripe\Forms\Form) and [FormField](api:SilverStripe\Forms\FormField) instances
can be rendered out using custom templates using `setTemplate`.


```php
$form = new Form(..);
$form->setTemplate('MyCustomFormTemplate');

// or, just a field
$field = new TextField(..);
$field->setTemplate('MyCustomTextField');
```

To override the template for CMS forms, the custom templates should be located in **/app/templates**. Front-end form templates can be located in **/app/templates** or in the active theme's **/templates** directory.

[notice]
It's recommended to copy the contents of the template you're going to replace and use that as a start. For instance, if
you want to create a `MyCustomFormTemplate` copy the contents of `Form.ss` to a `MyCustomFormTemplate.ss` file and 
modify as you need.

*The default Form.ss can be found in `/vendor/silverstripe/framework/templates/SilverStripe/Forms/Includes/`*
[/notice]

By default, Form and Fields follow the SilverStripe Template convention and are rendered into templates of the same 
class name (i.e EmailField will attempt to render into `EmailField.ss` and if that isn't found, `TextField.ss` or 
finally `FormField.ss`).

[alert]
While you can override all templates using normal view inheritance (i.e defining a `Form.ss`) other modules may rely on 
the core template structure. It is recommended to use `setTemplate` and unique templates for specific forms.
[/alert]

For [FormField](api:SilverStripe\Forms\FormField) instances, there are several other templates that are used on top of the main `setTemplate`.


```php
$field = new TextField();

$field->setTemplate('CustomTextField');
// Sets the template for the <input> tag. i.e '<input $AttributesHTML />'

$field->setFieldHolderTemplate('CustomTextField_Holder');
// Sets the template for the wrapper around the text field. i.e 
//    '<div class="text">'
//
// The actual FormField is rendered into the holder via the `$Field` 
// variable.
//
// setFieldHolder() is used in most `Form` instances and needs to output 
// labels, error messages and the like.

$field->setSmallFieldHolderTemplate('CustomTextField_Holder_Small');
// Sets the template for the wrapper around the text field.
//
// The difference here is the small field holder template is used when the 
// field is embedded within another field. For example, if the field is 
// part of a `FieldGroup` or `CompositeField` alongside other fields.
```

All templates are rendered within the scope of the [FormField](api:SilverStripe\Forms\FormField). To understand more about Scope within Templates as 
well as the available syntax, see the [Templates](../templates) documentation.

## Related Documentation

* [How to: Create a lightweight Form](how_tos/lightweight_form)

## API Documentation

* [Form](api:SilverStripe\Forms\Form)
* [FormField](api:SilverStripe\Forms\FormField)
