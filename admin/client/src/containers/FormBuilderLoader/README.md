# FormBuilderLoader Component

Used to retrieve a schema for FormBuilder to generate forms made up of field components and actions.
Wraps a [FormBuilder](../../components/FormBuilder/README.md] component with async loading logic,
and stores the loaded schemas in a Redux store.

## Properties

 * `schemaUrl` (string): The schema URL where the form will be scaffolded from e.g. '/admin/pages/schema/1'.
 * `schemaActions` (object): A `setSchema()` function to interact with the redux store.

See [FormBuilder](../../components/FormBuilder/README.md] for more properties.
