# FieldHolder

This is a higher order component which can be used to wrap 
a [form group](http://v4-alpha.getbootstrap.com/components/forms/#form-groups)
around a React component (usually a form field). It also add a `<label>`
and visually groups the two elements.

## Props

### title (string)

HTML value passed through to the `<label>` element

### leftTitle (string)

Same as `title` (legacy use)

### extraClass (string)

A `class` which is added to both the container and the wrapped React component.

### id (string)

The HTML `id` for the form field. Important to associate the `<label>`
the the actual field.

### Other

All other props are passed through to the wrapped React component.
