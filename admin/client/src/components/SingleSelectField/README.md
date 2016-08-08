# Single Select Field

Generates a `<select><option></option></select>`

## Props

### leftTitle

The label text to display with the field.

### extraClass

Addition CSS classes to apply to the `<select>` element.

### name (required)

Used for the field's `name` attribute.

### onChange

Handler function called when the field's value changes.

### value

The field's value.

### source (required)

The list of possible values that could be selected

### disabled

A list of values within `source` that can be seen but not selected

### hasEmptyDefault

If true, create an empty value option first

### emptyString

When `hasEmptyDefault` is true, this sets the label for the option
