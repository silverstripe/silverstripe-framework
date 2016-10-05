# OptionsetField Component

Generates a radio button group, behaves the similarly to `SingleSelectField`.

## Example
```js
<OptionsetField name="my-select" source={[
	{ value: 'one', title: '1' },
	{ value: 'two', title: '2', disabled: true },
	{ value: 'four', title: '4' }
]} value="one" />
```

## OptionsetField Properties

 * `id` (string): The ID for the component.
 * `extraClass` (string): Extra classes the component should have.
 * `itemClass` (string): Classes applicable to each item in the group.
 * `name` (string) (required): The name for the component.
 * `onChange` (function): Event handler for when the component changes.
 * `value` (string|number): The value that matches one of the source items value.
 * `readOnly` (boolean): Whether this field is read only.
 * `disabled` (boolean): Whether this field is disabled.
 * `source` (array): Array of items to appear in the list with the following properties.
   * `value` (string|number): The value for item.
   * `title` (any): The displayed value for item.
   * `disabled` (boolean): Tells if item is disabled from selecting.

## OptionField Properties

 * `id` (string): The ID for the component.
 * `extraClass` (string): Extra classes the component should have.
 * `name` (string) (required): The name for the component.
 * `type` (string): The type of option component will be: `checkbox` or `radio`
 * `leftTitle` (any): Title to display to the left (if inline) or above the field, check below NOTE about handling raw html.
 * `title` (any): Title to display if leftTitle is not defined, check below NOTE about handling raw html.
 * `onChange` (function): Event handler for when the component changes.
 * `value` (boolean): Whether this is checked or not, *this does not hold an explicit value*!
 * `readOnly` (boolean): Whether this field is read only.
 * `disabled` (boolean): Whether this field is disabled.

 _NOTE:_ For other properties, please refer to the [react-bootstrap Radio/Checkbox](https://react-bootstrap.github.io/components.html#forms-props-checkbox) documentation.
