# TextField Component

Generates an editable text field.

## Example

```js
<TextField name="my-text" />
```


## Properties

 * `id` (string): The ID for the component.
 * `extraClass` (string): Extra classes the component should have.
 * `name` (string) (required): The name for the component.
 * `onChange` (function): Event handler for when the component changes.
 * `value` (string|number): The value to display for the field, can use `defaultValue` for uncontrollable component.
 * `readOnly` (boolean): Whether this field is read only.
 * `disabled` (boolean): Whether this field is disabled.
 * `type` (string): Defines the type this component will have, e.g. `email`, `tel`.

 _NOTE:_ For other properties, please refer to the [react-bootstrap FormControl](https://react-bootstrap.github.io/components.html#forms-props-form-control) documentation.
