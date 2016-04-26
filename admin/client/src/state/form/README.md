# form

This state key holds form and form field data. Forms built using the `FormBuilder` component 
have their state stored in child keys of `form` (keyed by form ID) automatically.

```js
{
  form: {
    DetailEditForm: {
      fields: [
        {
          data: [],
          id: "Form_DetailEditForm_Name",
          messages: [],
          valid: true,
          value: "My Campaign"
        }
      ]
    }
  }
}
```

Forms built using `FormBuilder` will tidy up their state when unmounted.
