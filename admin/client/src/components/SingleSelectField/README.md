# SingleSelectField Component

Generates a select/dropdown field.

## Example
```js
<SingleSelectField name="my-select" source={[
	{ value: 'one', title: '1' },
	{ value: 'two', title: '2', disabled: true },
	{ value: 'four', title: '4' }
]} value="one" />
```

## Properties

 * `id` (string): The ID for the component.
 * `extraClass` (string): Extra classes the component should have.
 * `name` (string) (required): The name for the component.
 * `onChange` (function): Event handler for when the component changes.
 * `value` (string|number): The value to display for the field, can use `defaultValue` for uncontrollable component.
 * `readOnly` (boolean): Whether this field is read only.
 * `disabled` (boolean): Whether this field is disabled.
 * `source` (array): Array of items to appear in the list with the following properties excepted.
   * `value` (string|number): The value for item.
   * `title` (string|number): The displayed value for item.
   * `disabled` (boolean): Tells if item is disabled from selecting.
 * `data` (object): Extra data that helps define this field uniquely.
   * `hasEmptyDefault` (boolean): Defines if this has a "blank" option.
   * `emptyString` (string): The title for the "blank" option.

 _NOTE:_ For other properties, please refer to the [react-bootstrap FormControl](https://react-bootstrap.github.io/components.html#forms-props-form-control) documentation.
