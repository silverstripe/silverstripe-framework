# Tabs Component

For separating content into tabs without the need for separate pages.

This extends from `react-bootstrap` with similar expected behaviours, only difference is that when
there is only one tab (or none) in the Tabset, then only the content will show without the
clickable tab.

## Example

```js
<Tabs defaultActiveKey="Main" id="Root">
    <TabItem name="Main" title="Main">
        My first tab content
    </TabItem>
    <TabItem name="Settings" title="Settings">
        My settings tab here
    </TabItem>
</Tabs>
```

## Tabs Properties

 * `id` (string) (required): The ID for the component.
 * `extraClass` (string): Extra classes the component should have.
 * `defaultActiveKey` (string): The default tab to open, should match the name of a child `TabItem`, will default to the first Tab child.

## TabItem Properties

 * `name` (string) (required): A key to match the `activeKey` or `defaultActiveKey` property in the `Tabs` component to show the content. This replaces the `eventKey` property.
 * `title` (string): The label to display for the tab, can be set `null` to hide the tab.
 * `extraClass` (string): Extra classes the component should have.
 * `tabClassName` (string): Class to use for the tab.

 _NOTE:_ For other properties, please refer to the [react-bootstrap Tabs](https://react-bootstrap.github.io/components.html#tabs-props) documentation.
