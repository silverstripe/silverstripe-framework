# FormBuilderComponent

Used to generate forms, made up of field components and actions, from FormFieldSchema data.

This component will be moved to Framweork or CMS when dependency injection is implemented.

## PropTypes

### createFn (func)

Gives container components a chance to access a form component before it's constructed. Use this as an opportunity to pass a custom click handler to to a field for example.

### schemaUrl

The schema URL where the form will be scaffolded from e.g. '/admin/pages/schema/1'.

### handleSubmit (func)

Event handler passed to the Form Component as a prop. Parameters received are:
 * event (Event) - The submit event, it is strongly recommended to call `preventDefault()`
 * fieldValues (object) - An object containing the field values captured by the Submit handler
 * submitFn (func) - A callback for when the submission was successful, if submission fails, this function should not be called. (e.g. validation error)

### handleAction (func)

Event handler when a form action is clicked on, allows preventing submit and know which action was clicked on.
