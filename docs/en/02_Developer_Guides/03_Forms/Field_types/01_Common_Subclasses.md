title: Common FormField type subclasses
summary: A table containing a list of the common FormField subclasses.

# Common FormField type subclasses

This is a high level overview of available [api:SilverStripe\Forms\FormField] subclasses. An automatically generated list is available 
on the SilverStripe API documentation.

## Basic

 * [api:SilverStripe\Forms\CheckboxField]: Single checkbox field.
 * [api:SilverStripe\Forms\DropdownField]: A `<select>` tag. Can optionally save into has-one relationships.
 * [api:SilverStripe\Forms\ReadonlyField]: Read-only field to display a non-editable value with a label.
 * [api:SilverStripe\Forms\TextareaField]: Multi-line text field.
 * [api:SilverStripe\Forms\TextField]: Single-line text field.
 * [api:SilverStripe\Forms\PasswordField]: Masked input field.

## Actions

 * [api:SilverStripe\Forms\FormAction]: Button element for forms, both for `<input type="submit">` and `<button>`.
 * [api:ResetFormAction]: Action that clears all fields on a form.

## Formatted input

 * [api:SilverStripe\Forms\ConfirmedPasswordField]: Two masked input fields, checks for matching passwords.
 * [api:SilverStripe\Forms\CurrencyField]: Text field, validating its input as a currency. Limited to US-centric formats, including a hardcoded currency symbol and decimal separators. 
 See [api:SilverStripe\Forms\MoneyField] for a more flexible implementation.
 * [api:SilverStripe\Forms\DateField]: Represents a date in a single input field, or separated into day, month, and year. Can optionally use a calendar popup.
 * [api:SilverStripe\Forms\DatetimeField]: Combined date- and time field.
 * [api:SilverStripe\Forms\EmailField]: Text input field with validation for correct email format according to RFC 2822.
 * [api:SilverStripe\Forms\GroupedDropdownField]: Grouped dropdown, using <optgroup> tags.
 * [api:SilverStripe\Forms\HTMLEditor\HtmlEditorField]: A WYSIWYG editor interface.
 * [api:SilverStripe\ORM\FieldType\DBMoneyField]: A form field that can save into a [api:SilverStripe\ORM\FieldType\DBMoney] database field.
 * [api:SilverStripe\Forms\NumericField]: Text input field with validation for numeric values.
 * [api:SilverStripe\Forms\OptionsetField]: Set of radio buttons designed to emulate a dropdown.
 * [api:SilverStripe\Forms\SelectionGroup]: SelectionGroup represents a number of fields which are selectable by a radio button that appears at the beginning of each item.
 * [api:SilverStripe\Forms\TimeField]: Input field with time-specific, localised validation.

## Structure

 * [api:SilverStripe\Forms\CompositeField]: Base class for all fields that contain other fields. Uses `<div>` in template, but
doesn't necessarily have any visible styling.
 * [api:SilverStripe\Forms\FieldGroup] attached in CMS-context.
 * [api:SilverStripe\Forms\FieldList]: Basic container for sequential fields, or nested fields through CompositeField.
 * [api:SilverStripe\Forms\TabSet]: Collection of fields which is rendered as separate tabs. Can be nested.
 * [api:SilverStripe\Forms\Tab]: A single tab inside a `TabSet`.
 * [api:SilverStripe\Forms\ToggleCompositeField]: Allows visibility of a group of fields to be toggled.

## Files

 * [api:SilverStripe\Forms\FileField]: Simple file upload dialog.

## Relations

 * [api:SilverStripe\Forms\CheckboxSetField]: Displays a set of checkboxes as a logical group.
 * [api:TableField]: In-place editing of tabular data.
 * [api:SilverStripe\Forms\TreeDropdownField]: Dropdown-like field that allows you to select an item from a hierarchical AJAX-expandable tree.
 * [api:SilverStripe\Forms\TreeMultiselectField]: Represents many-many joins using a tree selector shown in a dropdown-like element
 * [api:SilverStripe\Forms\GridField\GridField]: Displays a [api:SilverStripe\ORM\SS_List] in a tabular format. Versatile base class which can be configured to allow editing, sorting, etc.
 * [api:SilverStripe\Forms\ListboxField]: Multi-line listbox field, through `<select multiple>`.


## Utility

 * [api:SilverStripe\Forms\DatalessField] - Base class for fields which add some HTML to the form but don't submit any data or
save it to the database
 * [api:SilverStripe\Forms\HeaderField]: Renders a simple HTML header element.
 * [api:SilverStripe\Forms\HiddenField] - Renders a hidden input field.
 * [api:SilverStripe\Forms\LabelField]: Simple label tag. This can be used to add extra text in your forms.
 * [api:SilverStripe\Forms\LiteralField]: Renders arbitrary HTML into a form.
