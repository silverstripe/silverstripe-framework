# CompositeField

For containing groups of fields in a container element.

## Example

```
<CompositeField name="Container">
    <TextField name="FirstName" />
    <TextField name="LastName" />
</CompositeField>
```

## Properties

 * `data` (object): Extra data that helps define this field uniquely.
   * `tag` (string): The element type the composite field should use in HTML.
   * `legend` (boolean): A label/legend for the group of fields contained, check below NOTE about handling raw html.
 * `extraClass` (string): Extra classes the CompositeField should have.
