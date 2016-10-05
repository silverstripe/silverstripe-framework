# LookupField Component

Generates a CSV list of values inside a Readonly styled box.

## Example
```js
<LookupField name="my-select" source={[
	{ value: 'one', title: '1' },
	{ value: 'two', title: '2' },
	{ value: 'four', title: '4' }
]} value="one" />
```

## Properties

 * `id` (string): The ID for the component.
 * `extraClass` (string): Extra classes the component should have.
 * `name` (string) (required): The name for the component.
 * `value` (string|array): The values to look up in the source.
 * `source` (array): Array of items to appear in the list with the following properties.
   * `value` (string|number): The value for item.
   * `title` (any): The displayed value for item.

 _NOTE:_ For other properties, please refer to the [react-bootstrap FormControl.Static](https://react-bootstrap.github.io/components.html#forms-props-form-control-static) documentation.
