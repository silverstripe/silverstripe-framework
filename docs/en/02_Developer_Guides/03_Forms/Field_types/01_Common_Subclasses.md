title: Common FormField type subclasses
summary: A table containing a list of the common FormField subclasses.

# Common FormField type subclasses

This is a high level overview of available [FormField](api:SilverStripe\Forms\FormField) subclasses. An automatically generated list is available 
on the SilverStripe API documentation.

## Basic

 * [CheckboxField](api:SilverStripe\Forms\CheckboxField): Single checkbox field.
 * [DropdownField](api:SilverStripe\Forms\DropdownField): A `<select>` tag. Can optionally save into has-one relationships.
 * [ReadonlyField](api:SilverStripe\Forms\ReadonlyField): Read-only field to display a non-editable value with a label.
 * [TextareaField](api:SilverStripe\Forms\TextareaField): Multi-line text field.
 * [TextField](api:SilverStripe\Forms\TextField): Single-line text field.
 * [PasswordField](api:SilverStripe\Forms\PasswordField): Masked input field.

## Actions

 * [FormAction](api:SilverStripe\Forms\FormAction): Button element for forms, both for `<input type="submit">` and `<button>`.
 * [ResetFormAction](api:ResetFormAction): Action that clears all fields on a form.

## Formatted input

 * [ConfirmedPasswordField](api:SilverStripe\Forms\ConfirmedPasswordField): Two masked input fields, checks for matching passwords.
 * [CurrencyField](api:SilverStripe\Forms\CurrencyField): Text field, validating its input as a currency. Limited to US-centric formats, including a hardcoded currency symbol and decimal separators. 
 See [MoneyField](api:SilverStripe\Forms\MoneyField) for a more flexible implementation.
 * [DateField](api:SilverStripe\Forms\DateField): Represents a date in a single input field, or separated into day, month, and year. Can optionally use a calendar popup.
 * [DatetimeField](api:SilverStripe\Forms\DatetimeField): Combined date- and time field.
 * [EmailField](api:SilverStripe\Forms\EmailField): Text input field with validation for correct email format according to RFC 2822.
 * [GroupedDropdownField](api:SilverStripe\Forms\GroupedDropdownField): Grouped dropdown, using <optgroup> tags.
 * [HtmlEditorField](api:SilverStripe\Forms\HTMLEditor\HtmlEditorField): A WYSIWYG editor interface.
 * [DBMoneyField](api:SilverStripe\ORM\FieldType\DBMoneyField): A form field that can save into a [DBMoney](api:SilverStripe\ORM\FieldType\DBMoney) database field.
 * [NumericField](api:SilverStripe\Forms\NumericField): Text input field with validation for numeric values.
 * [OptionsetField](api:SilverStripe\Forms\OptionsetField): Set of radio buttons designed to emulate a dropdown.
 * [SelectionGroup](api:SilverStripe\Forms\SelectionGroup): SelectionGroup represents a number of fields which are selectable by a radio button that appears at the beginning of each item.
 * [TimeField](api:SilverStripe\Forms\TimeField): Input field with time-specific, localised validation.

## Structure

 * [CompositeField](api:SilverStripe\Forms\CompositeField): Base class for all fields that contain other fields. Uses `<div>` in template, but
doesn't necessarily have any visible styling.
 * [FieldGroup](api:SilverStripe\Forms\FieldGroup) attached in CMS-context.
 * [FieldList](api:SilverStripe\Forms\FieldList): Basic container for sequential fields, or nested fields through CompositeField.
 * [TabSet](api:SilverStripe\Forms\TabSet): Collection of fields which is rendered as separate tabs. Can be nested.
 * [Tab](api:SilverStripe\Forms\Tab): A single tab inside a `TabSet`.
 * [ToggleCompositeField](api:SilverStripe\Forms\ToggleCompositeField): Allows visibility of a group of fields to be toggled.

## Files

 * [FileField](api:SilverStripe\Forms\FileField): Simple file upload dialog.

## Relations

 * [CheckboxSetField](api:SilverStripe\Forms\CheckboxSetField): Displays a set of checkboxes as a logical group.
 * [TableField](api:TableField): In-place editing of tabular data.
 * [TreeDropdownField](api:SilverStripe\Forms\TreeDropdownField): Dropdown-like field that allows you to select an item from a hierarchical AJAX-expandable tree.
 * [TreeMultiselectField](api:SilverStripe\Forms\TreeMultiselectField): Represents many-many joins using a tree selector shown in a dropdown-like element
 * [GridField](api:SilverStripe\Forms\GridField\GridField): Displays a [SS_List](api:SilverStripe\ORM\SS_List) in a tabular format. Versatile base class which can be configured to allow editing, sorting, etc.
 * [ListboxField](api:SilverStripe\Forms\ListboxField): Multi-line listbox field, through `<select multiple>`.


## Utility

 * [DatalessField](api:SilverStripe\Forms\DatalessField) - Base class for fields which add some HTML to the form but don't submit any data or
save it to the database
 * [HeaderField](api:SilverStripe\Forms\HeaderField): Renders a simple HTML header element.
 * [HiddenField](api:SilverStripe\Forms\HiddenField) - Renders a hidden input field.
 * [LabelField](api:SilverStripe\Forms\LabelField): Simple label tag. This can be used to add extra text in your forms.
 * [LiteralField](api:SilverStripe\Forms\LiteralField): Renders arbitrary HTML into a form.
