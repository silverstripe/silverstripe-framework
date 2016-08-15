# Single Select Field

Generates a `<select><option></option></select>`

## Props

### extraClass

Addition CSS classes to apply to the `<select>` element.

### name (required)

Used for the field's `name` attribute.

### onChange

Handler function called when the field's value changes.

### value

The field's selected value.

### source (required)

The list of possible values that could be selected

#### value

The value for this option.

#### title

The title or label displayed for users.

#### disabled

This option is shown but disabled from being selected.

### data

Additional field specific data

#### hasEmptyDefault

If true, create an empty value option first

#### emptyString

When `hasEmptyDefault` is true, this sets the label for the option
