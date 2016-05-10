# Schema state

Manages state associated with the FormFieldSchema.

When dependency injection is implemented, this will be moved into either Framework or CMS. 
We can't move it sooner because there's no way of extending state.

Note form state is stored under the `form` _not_ the `schema` key.
