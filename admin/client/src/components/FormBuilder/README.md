# FormBuilderComponent

Used to generate forms, made up of field components and actions, from FormFieldSchema data.

This component will be moved to Framweork or CMS when dependency injection is implemented.

## PropTypes

### actions

Actions the component can dispatch. This should include but is not limited to:

#### setSchema

An action to call when the response from fetching schema data is returned. This would normally be a simple action to set the store's `schema` key to the returned data.

### createFn (func)

Gives container components a chance to access a form component before it's constructed. Use this as an opportunity to pass a custom click handler to to a field for example.

### schemaUrl

The schema URL where the form will be scaffolded from e.g. '/admin/pages/schema/1'.

### schema

JSON schema representing the form. Used as the blueprint for generating the form.

### onSubmit (func)

Event handler passed to the Form Component as a prop.
