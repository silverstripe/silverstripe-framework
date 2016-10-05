# LiteralField Component

Generates a block of raw HTML content.

## Example
```js
<LiteralField name="my-raw-content" data={{
	content: '<span>My custom <b>html</b></span>'
}} />
```

## Properties

 * `id` (string): The ID for the component.
 * `extraClass` (string): Extra classes the component should have.
 * `name` (string) (required): The name for the component.
 * `data` (object) (required): Extra data that helps define this field uniquely.
   * `content` (string): The raw HTML content to generate.
 
