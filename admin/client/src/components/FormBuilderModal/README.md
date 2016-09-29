# FormBuilderModal Component

This is a Modal component to help display a form within a Modal/Dialog box

## Properties

 * `title` (string): The title that will appear for the Modal.
 * `show` (boolean): Tells the modal when to show and hide from the interface.
 * `handleHide` (function): Event handler when the modal is sending a hide request, this assumes the value of `show` that is passed will be changed when conditions are met.
 * `schemaUrl` (string): The url to call which is passed to the `FormBuilder` Component as a prop.
 * `handleSubmit` (function): Event handler when the form in the Modal is submitted.
 * `handleAction` (function): Event handler passed to the `FormBuilder` Component as a prop.
