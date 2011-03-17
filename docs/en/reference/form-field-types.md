# Form Field Types

This is a highlevel overview of available `[apiFormField]` subclasses. An automatically generated list is available through our [API](api:FormField)

## Formatted Input

*  `[AjaxUniqueTextField](api:AjaxUniqueTextField)`: Text field that automatically checks that the value entered is unique for
the given set of fields in a given set of tables
*  `[AutocompleteTextField](api:AutocompleteTextField)`
*  `[ConfirmedPasswordField](api:ConfirmedPasswordField)`: Shows two password-fields, and checks for matching passwords.
*  `[CreditCardField](api:CreditCardField)`
*  `[CurrencyField](api:CurrencyField)`
*  `[EmailField](api:EmailField)`
*  `[HTMLEditorField](api:HTMLEditorField)`: A WYSIWYG editor field, powered by tinymce.
*  `[NumericField](api:NumericField)`: A Single Numeric field extending a typical TextField but with validation.
*  `[PasswordField](api:PasswordField)`
*  `[UniqueRestrictedTextField](api:UniqueRestrictedTextField)`: Text field that automatically checks that the value entered
is unique for the given set of fields in a given set of tables
*  `[UniqueTextField](api:UniqueTextField)`: Text field that automatically checks that the value entered is unique for the
given set of fields in a given set of tables

## Date/Time

*  `[DateField](api:DateField)`: Represents a date in a textfield (New Zealand)
*  `[DatetimeField](api:DatetimeField)`: Combined date- and time field
*  `[TimeField](api:TimeField)`: Represents time in a textfield (New Zealand)

## Structure

*  `[CompositeField](api:CompositeField)`: Base class for all fields that contain other fields. Uses `<div>` in template, but
doesn't necessarily have any visible styling.
*  `[FieldGroup](api:FieldGroup)`: Same as CompositeField, but has default styling (indentation) attached in CMS-context.
*  `[api:FieldSet]`: Basic container for sequential fields, or nested fields through CompositeField. Does NOT render a
`<fieldgroup>`.
*  `[TabSet](api:TabSet)`
*  `[Tab](api:Tab)`


## Actions

*  `[api:Form]` for more info
*  `[InlineFormAction](api:InlineFormAction)`:  Render a button that will act as If you want to add custom behaviour, please
set {inlcudeDefaultJS} to false and work with behaviour.js.
*  `[api:Image]`: Action that uses an image instead of a button
*  `[InlineFormAction](api:InlineFormAction)`: Prevents placement of a button in the CMS-button-bar.

## Files

*  `[FileField](api:FileField)`: Simple file upload dialog.
*  `[FileIFrameField](api:FileIFrameField)`: File uploads through an iframe
*  `[api:ImageField]`: Image upload through an iframe, with thumbnails and file-selection from existing assets
*  `[SimpleImageField](api:SimpleImageField)`:  SimpleImageField provides an easy way of uploading images to Image has_one
relationships. Unlike ImageField, it doesn't use an iframe.


## Relations

*  `[ComplexTableField](api:ComplexTableField)`: Provides a tabuar list in your form with view/edit/add/delete links to modify
records with a "has-one"-relationship (in a lightbox-popup).
*  `[HasManyComplexTableField](api:HasManyComplexTableField)`
*  `[HasOneComplexTableField](api:HasOneComplexTableField)`
*  `[LanguageDropdownField](api:LanguageDropdownField)`:  An extension to dropdown field, pre-configured to list languages.
Tied into i18n.
*  `[ManyManyComplexTableField](api:ManyManyComplexTableField)`
*  `[TableField](api:TableField)`
*  `[api:TableListField]`
*  `[TreeDropdownField](api:TreeDropdownField)`
*  `[TreeMultiselectField](api:TreeMultiselectField)`: represents many-many joins using a tree selector shown in a
dropdown-like element
*  `[api:WidgetArea]`



## Dataless/Utility

*  `[DatalessField](api:DatalessField)` - Base class for fields which add some HTML to the form but don't submit any data or
save it to the database
*  `[HeaderField](api:HeaderField)`: Renders a simple `<h1>`-`<h6>` header
*  `[HiddenField](api:HiddenField)`
*  `[LabelField](api:LabelField)`
*  `[LiteralField](api:LiteralField)`: Renders arbitrary HTML into a form.

## CMS Field Editor

Please see `[api:HTMLEditorField]` for in-depth documentation about custom forms created through a GUI in the CMS.
