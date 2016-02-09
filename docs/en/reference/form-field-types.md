# Form Field Types

This is a highlevel overview of available [api:FormField] subclasses. An automatically generated list is available through our [API](api:FormField)

## Formatted Input

*  [api:AjaxUniqueTextField]: Text field that automatically checks that the value entered is unique for
the given set of fields in a given set of tables
*  [api:AutocompleteTextField]
*  [api:ConfirmedPasswordField]: Shows two password-fields, and checks for matching passwords.
*  [api:CreditCardField]
*  [api:CurrencyField]
*  [api:EmailField]
*  [api:HTMLEditorField]: A WYSIWYG editor field, powered by tinymce.
*  [api:NumericField]: A Single Numeric field extending a typical TextField but with validation.
*  [api:PasswordField]
*  [api:UniqueRestrictedTextField]: Text field that automatically checks that the value entered
is unique for the given set of fields in a given set of tables
*  [api:UniqueTextField]: Text field that automatically checks that the value entered is unique for the
given set of fields in a given set of tables

## Date/Time

*  [api:DateField]: Represents a date in a textfield (New Zealand)
*  [api:DatetimeField]: Combined date- and time field
*  [api:TimeField]: Represents time in a textfield (New Zealand)

## Structure

*  [api:CompositeField]: Base class for all fields that contain other fields. Uses `<div>` in template, but
doesn't necessarily have any visible styling.
*  [api:FieldGroup]: Same as CompositeField, but has default styling (indentation) attached in CMS-context.
*  [api:FieldSet]: Basic container for sequential fields, or nested fields through CompositeField. Does NOT render a
`<fieldgroup>`.
*  [api:TabSet]
*  [api:Tab]


## Actions

*  [api:Form] for more info
*  [api:InlineFormAction]:  Render a button that will act as If you want to add custom behaviour, please
set {inlcudeDefaultJS} to false and work with behaviour.js.
*  [api:Image]: Action that uses an image instead of a button
*  [api:InlineFormAction]: Prevents placement of a button in the CMS-button-bar.

## Files

*  [api:FileField]: Simple file upload dialog.
*  [api:FileIFrameField]: File uploads through an iframe
*  [api:ImageField]: Image upload through an iframe, with thumbnails and file-selection from existing assets
*  [api:SimpleImageField]:  SimpleImageField provides an easy way of uploading images to Image has_one
relationships. Unlike ImageField, it doesn't use an iframe.


## Relations

*  [api:ComplexTableField]: Provides a tabuar list in your form with view/edit/add/delete links to modify
records with a "has-one"-relationship (in a lightbox-popup).
*  [api:HasManyComplexTableField]
*  [api:HasOneComplexTableField]
*  [api:LanguageDropdownField]:  An extension to dropdown field, pre-configured to list languages.
Tied into i18n.
*  [api:ManyManyComplexTableField]
*  [api:TableField]
*  [api:TableListField]
*  [api:TreeDropdownField]
*  [api:TreeMultiselectField]: represents many-many joins using a tree selector shown in a
dropdown-like element
*  [api:WidgetArea]



## Dataless/Utility

*  [api:DatalessField] - Base class for fields which add some HTML to the form but don't submit any data or
save it to the database
*  [api:HeaderField]: Renders a simple `<h1>`-`<h6>` header
*  [api:HiddenField]
*  [api:LabelField]
*  [api:LiteralField]: Renders arbitrary HTML into a form.

## CMS Field Editor

Please see [api:HTMLEditorField] for in-depth documentation about custom forms created through a GUI in the CMS.
