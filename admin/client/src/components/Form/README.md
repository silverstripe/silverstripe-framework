# Form Component

The FormComponent is used to render forms in SilverStripe. The only time you should need to use `FormComponent` directly is when you're composing custom layouts. Forms can be automatically generated from a schema using the `FormBuilder` component.

This component should be moved to Framework when dependency injection is implemented.

## Properties

 * `actions` (object) (required): A list of objects representing the form actions. For example the submit button.
 * `attributes` (object) (required): An object of HTML attributes for the form. For example:

```js
attributes = {
    'action': 'admin/assets/EditForm',
    'class': 'cms-edit-form root-form AssetAdmin LeftAndMain',
    'enctype': 'multipart/form-data',
    'id': 'Form_EditForm',
    'method': 'POST'
}
```

 * `componentWillUnmount` (func): Optional function which will be called in the component's 'native' `componentWillUnmount` method. This can be used to tidy up Redux state that's no longer required.
 * `data` (object) (required): Extra data that helps define this field uniquely.
 * `fields` (required): A list of field objects to display in the form. These objects should be transformed to Components using the `this.props.mapFieldsToComponents` method.
 * `mapFieldsToComponents` (required): A function that maps each schema field (`this.props.fields`) to the component responsibe for render it.
 * `handleSubmit` (func): Called then the form is submitted.
