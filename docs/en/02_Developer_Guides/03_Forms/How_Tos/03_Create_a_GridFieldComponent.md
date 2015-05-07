
A single component often uses a number of interfaces.

### GridField_HTMLProvider

Provides HTML for the header/footer rows in the table or before/after the template.

Examples:

 - A header html provider displays a header before the table
 - A pagination html provider displays pagination controls under the table
 - A filter html fields displays filter fields on top of the table
 - A summary html field displays sums of a field at the bottom of the table
 
### GridField_ColumnProvider

Add a new column to the table display body, or modify existing columns. Used once per record/row.

Examples:

 - A data columns provider that displays data from the list in rows and columns.
 - A delete button column provider that adds a delete button at the end of the row

### GridField_ActionProvider

Action providers runs actions, some examples are:

 - A delete action provider that deletes a DataObject.
 - An export action provider that will export the current list to a CSV file.

### GridField_DataManipulator

Modifies the data list. In general, the data manipulator will make use of `GridState` variables
to decide how to modify the data list.

Examples:

 - A paginating data manipulator can apply a limit to a list (show only 20 records)
 - A sorting data manipulator can sort the Title in a descending order.

### GridField_URLHandler

Sometimes an action isn't enough, we need to provide additional support URLs for the grid. It 
has a list of URL's that it can handle and the GridField passes request on to URLHandlers on matches.

Examples:

 - A pop-up form for editing a record's details.
 - JSON formatted data used for javascript control of the gridfield.