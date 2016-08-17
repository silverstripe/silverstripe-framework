# FormBuilderComponent

Used to generate forms, made up of field components and actions, from FormFieldSchema data.

This component will be moved to Framweork or CMS when dependency injection is implemented.

## PropTypes

### createFn (func)

Gives container components a chance to access a form component before it's constructed. Use this as an opportunity to pass a custom click handler to to a field for example.

### schemaUrl

The schema URL where the form will be scaffolded from e.g. '/admin/pages/schema/1'.

### handleSubmit (func)

Event handler passed to the Form Component as a prop.

### handleAction (func)

Event handler when a form action is clicked on, allows preventing submit and know which action was clicked on.
