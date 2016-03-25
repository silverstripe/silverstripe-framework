# GridField

General purpose component for tabular data.

## GridFieldTableComponent

This component is used to display structured data in an extendible table layout.

**Required Props**

 * **Headings** - (array) The column headings.
 * **Rows** - (array) The table rows.

## GridFieldAction

This component renders a button within a grid-field to handle actions.

**Required Props**

 * **handleClick** - Function for when a button is clicked

## GridFieldCell

This component represents a data cell in a GridFieldRow.

**Optional Props**

 * **width** - Set a width relative to the other cells if required. Accepts a number from 1-10 (defaults to 5).

## GridFieldHeader

This component is used to display a table header row on a GridFieldComponent.

## GridFieldHeaderCell

This component is a cell in a GridFieldHeader component.

**Optional Props**

 * **width** - Set a width relative to the other cells if required. Accepts a number from 1-10 (defaults to 5).

# GridFieldRow

Represents a row in a GridField.

**Optional Props**

 * **cells** - (array) The table data to display in the row.
