# PopoverField Component

Creates a popup box that may contain other nested fields. The activator for this popup
is represented by a button.

## Example
```js
<PopoverField>
  <button>My first button</button>
  <button>My other button</button>
</PopoverField>
```

## Properties

 * `id` (string): The ID for the component.
 * `title` (any): The title to display on the button to open the popover, if left blank it will display an ellipsis icon.
 * `data` (object) (required): Extra data that helps define this field uniquely.
   * `popoverTitle` (string): The title to appear for the popover.
   * `buttonTooltip` (string): Title for button tooltip.
   * `placement` (string): Where the popover will appear in relation to the button, options available are:
     * top
     * right
     * bottom
     * left
