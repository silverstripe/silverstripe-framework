# FormBuilder Component

Used to generate forms, made up of field components and actions, from form schema data.

Forms are usually rendered through [redux-form](http://redux-form.com/),
although this can be controlled through the `baseFormComponent`
and `baseFieldComponent` props.

If you want to load the schema from a server via XHR, use the
[FormBuilderLoader](../../containers/FormBuilderLoader/README.md] instead. 

## Properties

 * `form` (string): Form identifier (useful to reference this form through redux selectors)
 * `baseFormComponent` (function): A React component to render the form
 * `baseFieldComponent` (function): A React component to render each field. Should be a HOC which receives
   the actual field builder through a `component` prop. 
 * `schema` (object): A form schema (see "Schema Structure" below)
 * `createFn` (function): Gives container components a chance to access a form component before it's constructed.
   Use this as an opportunity to pass a custom click handler to to a field for example.
 * `handleSubmit` (function): Event handler passed to the Form Component as a prop.
   Should return a promise (usually the result of the `submitFn` argument). Parameters received are:
   * `data` (object): An object containing the field values captured by the submit handler
   * `action` (string): The name of the button clicked to perform this action. 
        Defaults to first button when form is submitted by pressing the "enter" key.
   * `submitFn` (function): A callback for when the submission was successful, if submission fails, 
     this function should not be called. (e.g. validation error). Pass in your modified `data`
     to influence the data being sent through.
 * `handleAction` (function): Event handler when a form action is clicked on, allows preventing submit and know which action was clicked on. Arguments:
    * `event` (function) Allows cancellation of the submission through `event.stopPropagation()`.
      The action can be identified via `event.currentTarget.name`.
    * `data` (object): Validated and processed field values, ready for submission
 * `asyncValidate` (function): See [redux-form](http://redux-form.com/6.0.5/docs/api/ReduxForm.md/)
 * `onSubmitFail` (function): See [redux-form](http://redux-form.com/6.0.5/docs/api/ReduxForm.md/)
 * `onSubmitSuccess` (function): See [redux-form](http://redux-form.com/6.0.5/docs/api/ReduxForm.md/)
 * `shouldAsyncValidate` (function): See [redux-form](http://redux-form.com/6.0.5/docs/api/ReduxForm.md/)
 * `touchOnBlur` (bool): See [redux-form](http://redux-form.com/6.0.5/docs/api/ReduxForm.md/)
 * `touchOnChange` (bool): See [redux-form](http://redux-form.com/6.0.5/docs/api/ReduxForm.md/)
 * `persistentSubmitErrors` (bool): See [redux-form](http://redux-form.com/6.0.5/docs/api/ReduxForm.md/)
 * `validate` (function): See [redux-form](http://redux-form.com/6.0.5/docs/api/ReduxForm.md/)
 * `responseRequestedSchema` (array): This allows you to customise the response requested from the server
 on submit. See below on "Handling submissions".
  
## Handling submissions

The `responseRequestedSchema` property will control the value of the 'X-Formschema-Request' header, which
in turn communicates to PHP the kind of response react would like. Your form should only specify the
bare minimum that it requires, as each header will represent additional overhead on all XHR requests. 
  
This is an array which may be any combination of the below values:

* `schema`: The schema is requested on submit
* `state`: The state is requested on submit. Note that this may also include form errors.
* `errors`: The list of validation errors is returned in case of error.
* `auto`: (default) Conditionally return `errors` if there are errors, or `state` if there are none.

Note that these are only the requested header values; The PHP submission method may choose to ignore
these values, and return any combination of the above. Typically the only time this requested value
is respected is when handled by the default validation error handler (LeftAndMain::getSchemaResponse)

## Schema Structure

The `schema` prop expects a particular structure, containing the form structure
in a `schema` key, and the form values in a `state` key.
See [RFC: FormField React Integration API](https://github.com/silverstripe/silverstripe-framework/issues/4938) for details.

## Example

```js
import { Field as ReduxFormField, reduxForm } from 'redux-form';
class MyComponent extends Component {
  constructor(props) {
    super(props);
    this.handleSubmit = this.handleSubmit.bind(this);
  }
  handleSubmit(data, action, submitFn) {
    // You can implement custom submission handling and data processing here.
    // Ensure to always return a promise if you want execution to continue.
    if (!this.myCheck(data)) {
      return;
    }
    return submitFn();
  }
  render() {
    const props = {
      form: 'MyForm',
      schema: { /* ... */ },
      baseFormComponent: reduxForm()(Form),
      baseFieldComponent: ReduxFormField,
      handleSubmit: this.handleSubmit
    };
    return <FormBuilder {...props} />
  }
}
```

With the default implementation of [redux-form](http://http://redux-form.com)
(passed in through `baseFormComponent` and `baseFieldComponent`), 
the submission process works as follows:

 1. `<FormBuilder>` passes it's `handleSubmit` to `reduxForm()` as an `onSubmit` prop
 1. `reduxForm()` passes it's own `handleSubmit` to `<Form>`
 1. `<Form>` sets `<Form onSubmit={this.props.handleSubmit}>`
 1. `<Form>` calls `reduxForm()` own `handleSubmit()`, which does normalisation and validation
 1. `reduxForm()` calls its `onSubmit` prop, which is set to `<FormBuilder>` `handleSubmit()`
 1. `<FormBuilder>` either submits the form, or calls it's own overloaded `handleSubmit()` prop

See [handleSubmit](http://redux-form.com/6.0.5/docs/api/Props.md#-handlesubmit-eventorsubmit-function-) 
in the redux-form docs for more details.
