# CheckboxField Component

Generates a single checkbox element.

## Example
```js
<CheckboxField name="my-select" value="1" />
```

## Properties

 * `id` (string): The ID for the component.
 * `extraClass` (string): Extra classes the component should have.
 * `name` (string) (required): The name for the component.
 * `leftTitle` (any): Title to display to the left (if inline) or above the field, check below NOTE about handling raw html.
 * `title` (any): Title to display if leftTitle is not defined, check below NOTE about handling raw html.
 * `onChange` (function): Event handler for when the component changes.
 * `value` (boolean): Whether this is checked or not, *this does not hold an explicit value*!
 * `readOnly` (boolean): Whether this field is read only.
 * `disabled` (boolean): Whether this field is disabled.

 _NOTE:_ For other properties, please refer to the [react-bootstrap Radio/Checkbox](https://react-bootstrap.github.io/components.html#forms-props-checkbox) documentation.
