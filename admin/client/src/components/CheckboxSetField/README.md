# CheckboxSetField Component

Generates a checkbox button group.

## Example
```js
<CheckSetField name="my-select" source={[
	{ value: 'one', title: '1' },
	{ value: 'two', title: '2', disabled: true },
	{ value: 'three', title: '3' },
	{ value: 'four', title: '4' }
]} value={['one', 'four']} />
```

## Properties

 * `id` (string): The ID for the component.
 * `extraClass` (string): Extra classes the component should have.
 * `itemClass` (string): Classes applicable to each item in the group.
 * `name` (string) (required): The name for the component.
 * `onChange` (function): Event handler for when the component changes.
 * `value` (string|number): The value that matches one or more of the source items value.
 * `readOnly` (boolean): Whether this field is read only.
 * `disabled` (boolean): Whether this field is disabled.
 * `source` (array): Array of items to appear in the list with the following properties.
   * `value` (string|number): The value for item.
   * `title` (any): The displayed value for item.
   * `disabled` (boolean): Tells if item is disabled from selecting.

 _NOTE:_ For other properties, please refer to the [react-bootstrap Radio/Checkbox](https://react-bootstrap.github.io/components.html#forms-props-checkbox) documentation.
