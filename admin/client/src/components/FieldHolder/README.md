# FieldHolder Higher-order Component

This is a higher order component.
It adds `ControlLabel` and other necessary components around a Component (normally `FormField`).

## Example

```js
fieldHolder(TextField)
```

## Properties

 * `leftTitle` (any): Title to display to the left (if inline) or above the field, check below NOTE about handling raw html.
 * `rightTitle` (any): Title to display to the right (if inline) or below the field, check below NOTE about handling raw html.
 * `title` (any): Title to display if leftTitle is not defined, check below NOTE about handling raw html.
 * `description` (any): For any extra information you'd like to display with the field.
 * `extraClass` (string): Extra classes the component should have.
 * `holderId` (string): An ID for the wrapping element.
 * `id` (string): ID to be used for the `ControlLabels` to link them with the Field.
 * `hideLabels` (boolean): Defines whether to show labels for this holder, handy for if the Field already handles its own label but still need other features like the `description`.

 _NOTE:_ For using titles or descriptions with raw HTML, pass in an object with the following structure:
 ```json
 {
   "html": "<span>My html</span>"
 }
 ```
 _NOTE2:_ For other properties, please refer to the [react-bootstrap FormGroup](https://react-bootstrap.github.io/components.html#forms-props-form-group) documentation.
