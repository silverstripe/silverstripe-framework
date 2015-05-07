title: Common FormField type subclasses
summary: A table containing a list of the common FormField subclasses.

# Common FormField type subclasses

This is a high level overview of available `[api:FormField]` subclasses. An automatically generated list is available 
on the SilverStripe API documentation.

## Basic

 * `[api:CheckboxField]`: Single checkbox field.
 * `[api:DropdownField]`: A `<select>` tag. Can optionally save into has-one relationships.
 * `[api:ReadonlyField]`: Read-only field to display a non-editable value with a label.
 * `[api:TextareaField]`: Multi-line text field.
 * `[api:TextField]`: Single-line text field.
 * `[api:PasswordField]`: Masked input field.

## Actions

 * `[api:FormAction]`: Button element for forms, both for `<input type="submit">` and `<button>`.
 * `[api:ResetFormAction]`: Action that clears all fields on a form.

## Formatted input

 * `[api:AjaxUniqueTextField]`: Text field that automatically checks that the value entered is unique for the given set of fields in a given set of tables.
 * `[api:ConfirmedPasswordField]`: Two masked input fields, checks for matching passwords.
 * `[api:CountryDropdownField]`: A simple extension to dropdown field, pre-configured to list countries.
 * `[api:CreditCardField]`: Allows input of credit card numbers via four separate form fields, including generic validation of its numeric values.
 * `[api:CurrencyField]`: Text field, validating its input as a currency. Limited to US-centric formats, including a hardcoded currency symbol and decimal separators. 
 See `[api:MoneyField]` for a more flexible implementation.
 * `[api:DateField]`: Represents a date in a single input field, or separated into day, month, and year. Can optionally use a calendar popup.
 * `[api:DatetimeField]`: Combined date- and time field.
 * `[api:EmailField]`: Text input field with validation for correct email format according to RFC 2822.
 * `[api:GroupedDropdownField]`: Grouped dropdown, using <optgroup> tags.
 * `[api:HtmlEditorField]`: A WYSIWYG editor interface.
 * `[api:MoneyField]`: A form field that can save into a `[api:Money]` database field.
 * `[api:NumericField]`: Text input field with validation for numeric values.
 * `[api:OptionsetField]`: Set of radio buttons designed to emulate a dropdown.
 * `[api:PhoneNumberField]`: Field for displaying phone numbers. It separates the number, the area code and optionally the country code and extension.
 * `[api:SelectionGroup]`: SelectionGroup represents a number of fields which are selectable by a radio button that appears at the beginning of each item.
 * `[api:TimeField]`: Input field with time-specific, localised validation.

## Structure

 * `[api:CompositeField]`: Base class for all fields that contain other fields. Uses `<div>` in template, but
doesn't necessarily have any visible styling.
 * `[api:FieldGroup] attached in CMS-context.
 * `[api:FieldList]`: Basic container for sequential fields, or nested fields through CompositeField.
 * `[api:TabSet]`: Collection of fields which is rendered as separate tabs. Can be nested.
 * `[api:Tab]`: A single tab inside a `TabSet`.
 * `[api:ToggleCompositeField]`: Allows visibility of a group of fields to be toggled.
 * `[api:ToggleField]`: ReadonlyField with added toggle-capabilities - will preview the first sentence of the contained text-value, and show the full content by a javascript-switch.

## Files

 * `[api:FileField]`: Simple file upload dialog.
 * `[api:UploadField]`: File uploads through HTML5 features, including upload progress, preview and relationship management.

## Relations

 * `[api:CheckboxSetField]`: Displays a set of checkboxes as a logical group.
 * `[api:TableField]`: In-place editing of tabular data.
 * `[api:TreeDropdownField]`: Dropdown-like field that allows you to select an item from a hierarchical AJAX-expandable tree.
 * `[api:TreeMultiselectField]`: Represents many-many joins using a tree selector shown in a dropdown-like element
 * `[api:GridField]`: Displays a `[api:SS_List]` in a tabular format. Versatile base class which can be configured to allow editing, sorting, etc.
 * `[api:ListboxField]`: Multi-line listbox field, through `<select multiple>`.


## Utility

 * `[api:DatalessField]` - Base class for fields which add some HTML to the form but don't submit any data or
save it to the database
 * `[api:HeaderField]`: Renders a simple HTML header element.
 * `[api:HiddenField]` - Renders a hidden input field.
 * `[api:LabelField]`: Simple label tag. This can be used to add extra text in your forms.
 * `[api:LiteralField]`: Renders arbitrary HTML into a form.
