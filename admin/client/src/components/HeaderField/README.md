# HeaderField Component

Generates a header field for displaying as a title.

## Example
```js
<HeaderField id="my-hidden" data={{ title: 'My heading' }} />
```

## Properties

 * `id` (string): The ID for the component.
 * `extraClass` (string): Extra classes the component should have.
 * `data` (string) (required): Extra data that helps define this field uniquely.
   * headingLevel (number): The level depth for heading.
   * title (string) (required): Title to display.
