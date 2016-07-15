# Popover Field Component

Creates a popup box that may contain other nested fields. The activator for this popup
is represented by a button.

## Props

### placement

Position to place this popover compared to the activation button. Options are:

* left
* right
* top
* bottom

Can be provided within a `data` object passed to this component.

## popoverTitle

This title will be used as the header in the popup.

Can be provided within a `data` object passed to this component.

### title

This will be used as the label for the button. If left blank, `...` (elipses) will be
used in place as a default.

### id (required)

Used for the field's `id` attribute.

