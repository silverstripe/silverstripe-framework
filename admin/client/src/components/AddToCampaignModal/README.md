# AddToCampaignModal

This is a Modal component to help Add a target object to Campaigns (ChangeSets)

## Props

### title (string)

The title that will appear for the Modal, defaults to "Add to campaign".

### show (bool)

Tells the modal when to show and hide from the interface.

### handleHide (func)

Event handler when the modal is sending a hide request, this assumes the value of `show` that is passed will be changed when conditions are met.

### schemaUrl (string)

The url to call which is passed to the `FormBuilder` Component as a prop.

### handleSubmit (func)

Event handler when the form in the Modal is submitted.

### handleAction (func)

Event handler passed to the `FormBuilder` Component as a prop.
