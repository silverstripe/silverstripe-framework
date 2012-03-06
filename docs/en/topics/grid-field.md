# Using and extending GridField

The `GridField` is a flexible form field for creating tables of data.  It's new in SilverStripe 3.0 and replaces `ComplexTableField`, `TableListField`, and `TableField`.  It's built as a lean core with a number of components that you plug into it.  By selecting from the components that we provide or writing your own, you can grid a wide variety of grid controls.

## Using GridField

A GridField is created like any other field: you create an instance of the GridField object and add it to the fields of a form. At its simplest, GridField takes 3 arguments: field name, field title, and an `SS_List` of records to display.

This example might come from a Controller designed to manage the members of a group:

	:::php
	/**
	 * Form to display all members in a group
	 */
	public function MemberForm() {
		$field = new GridField("Members", "Members of this group", $this->group->Members());
		return new Form("MemberForm", $this, new FieldSet($field), new FieldSet());
	}

Note that the only way to specify the data that is listed in a grid field is with `SS_List` argument.  If you want to customise the data displayed, you can do so by customising this object.

This will create a read-only grid field that will show the columns specified in the Member's `$summary_fields` setting, and will let you sort and/or filter by those columns, as well as show pagination controls with a handful of records per page.

## GridFieldConfig: Portable configuration

The example above a useful default case, but when developing applications you may need to control the behaviour of your grid more precisely than this.  To this end, the `GridField` constructor allows for fourth argument, `$config`, where you can pass a `GridFieldConfig` object.

This example creates exactly the same kind of grid as the previous example, but it creates the configuration manually:

	:::php
	$config = GridFieldConfig::create();
	// Provide a header row with filter controls
	$config->addComponent(new GridFieldFilter());
	// Provide a default set of columns based on $summary_fields
	$config->addComponent(new GridFieldDefaultColumns());
	// Provide a header row with sort controls
	$config->addComponent(new GridFieldSortableHeader());
	// Paginate results to 25 items per page, and show a footer with pagination controls
	$config->addComponent(new GridFieldPaginator(25));
	$field = new GridField("Members", "Members of this group", $this->group->Members(), $config);

If we wanted to make a simpler grid without pagination or filtering, we could do so like this:

	:::php
	$config = GridFieldConfig::create();
	// Provide a default set of columns based on $summary_fields
	$config->addComponent(new GridFieldDefaultColumns());
	// Provide a header row with sort controls
	$config->addComponent(new GridFieldPaginator(25));
	$field = new GridField("Members", "Members of this group", $this->group->Members(), $config);

A `GridFieldConfig` is made up of a new of `GridFieldComponent` objects, which are described in the next chapter.


## GridFieldComponent: Modular features

`GridFieldComponent` is a family of interfaces.
SilverStripe Framework comes with the following components that you can use out of the box.

### GridFieldDefaultColumns

This is the one component that, in most cases, you must include.  It provides the default columns, sourcing them from the underlying DataObject's `$summary_fields` if no specific configuration is provided.

Without GridFieldDefaultColumns added to a GridField, it would have no columns whatsoever.  Although this isn't particularly useful most of the time, we have allowed for this for two reasons:

 * You may have a grid whose fields are generated purely by another non-standard component.
 * It keeps the core of the GridField lean, focused solely on providing APIs to the components.

There are a number of methods that can be called on GridField to configure its behaviour.

You can choose which fields you wish to display:

	:::php
	$gridField->setDisplayFields(array(
		'ID' => 'ID',
		'FirstName' => 'First name',
		'Surname' => 'Surname',
		'Email' => 'Email',
		'LastVisited' => 'Last visited',
	));

You can specify formatting operations, for example choosing the format in which a date is displayed:

	:::php
	$gridField->setFieldCasting(array(
		'LastVisited' => 'Date->Ago',
	));

You can also specify formatting replacements, to replace column contents with HTML tags:

	:::php
	$gridField->setFieldFormatting(array(
		'Email' => '<strong>$Email</strong>',
	));
	
**EXPERIMENTAL API WARNING:** We will most likely refactor this so that this configuration methods are called on the component rather than the grid field. 

### GridFieldSortableHeader

This component will add a header to the grid with sort buttons.  It will detect which columns are sortable and only provide sort controls on those columns.

### GridFieldFilter

This component will add a header row with a text field filter for each column, letting you filter the results with text searches.  It will detect which columns are filterable and only provide sort controls on those columns.

### GridFieldPaginator

This component will limit output to a fixed number of items per page add a footer row with pagination controls. The constructor takes 1 argument: the number of items per page.

### GridFieldAction

TODO Describe component, including GridFieldEditAction/GridFieldDeleteAction

### GridFieldRelationAdd

This class is is responsible for adding objects to another object's has_many and many_many relation,
as defined by the `[api:RelationList]` passed to the GridField constructor.
Objects can be searched through an input field (partially matching one or more fields).
Selecting from the results will add the object to the relation.
Often used alongside `[api:GridFieldRelationDelete]` for detaching existing records from a relatinship.
For easier setup, have a look at a sample configuration in `[api:GridFieldConfig_RelationEditor]`.

### GridFieldRelationDelete

Allows to detach an item from an existing has_many or many_many relationship.
Similar to {@link GridFieldDeleteAction}, but allows to distinguish between 
a "delete" and "detach" action in the UI - and to use both in parallel, if required.
Requires the GridField to be populated with a `[api:RelationList]` rather than a plain DataList.
Often used alongside `[api:GridFieldRelationAdd]` to add existing records to the relationship.

### GridFieldPopupForms

TODO Describe component, including how it relates to GridFieldEditAction. Point to GridFieldConfig_RelationEditor for easier defaults.

### GridFieldTitle

TODO

### GridFieldExporter

TODO

## Extending GridField with custom components

You can create a custom component by building a class that implements one or more of the following interfaces: `GridField_HTMLProvider`, `GridField_ColumnProvider`, `GridField_ActionProvider`, or `GridField_DataManipulator`.

All of the methods expected by these interfaces take `$gridField` as their first argument.  The gridField related to the component isn't set as a property of the component instance.  This means that you can re-use the same component object across multiple `GridField`s, if that is appropriate.

It's common for a component to implement several of these interfaces in order to provide the complete implementation of a feature.  For example, `GridFieldSortableHeader` implements the following:

 * `GridField_HTMLProvider`, to generate the header row including the GridField_Action buttons
 * `GridField_ActionProvider`, to define the sortasc and sortdesc actions that add sort column and direction to the state.
 * `GridField_DataManipulator`, to alter the sorting of the data list based on the sort column and direction values in the state.

 ### GridFieldRelationAdd

A GridFieldRelationAdd is responsible for adding objects to another object's `has_many` and `many_many` relation,
as defined by the `[api:RelationList]` passed to the GridField constructor.
Objects can be searched through an input field (partially matching one or more fields).
Selecting from the results will add the object to the relation.

 	:::php
 	$group = DataObject::get_one('Group');
 	$config = GridFieldConfig::create()->addComponent(new GridFieldRelationAdd(array('FirstName', 'Surname', 'Email'));
 	$gridField = new GridField('Members', 'Members', $group->Members(), $config);

## Component interfaces

### GridField_HTMLProvider

The core GridField provides the following basic HTML:

 * A `<table>`, with an empty `<thead>` and `<tfoot>`
 * A collection of `<tr>`s, based on the grid's data list, each of which will contain a collection or `<td>`s based on the grid's columns.

The `GridField_HTMLProvider` component can provide HTML that goes into the `<thead>` or `<tfoot>`, or that appears before or after the table itself.

It should define the getHTMLFragments() method, which should return a map.  The map keys are can be 'header', 'footer', 'before', or 'after'.  The map values should be strings containing the HTML content to put into each of these spots.  Only the keys for which you wish to provide content need to be defined.

For example, this components will add a footer row to the grid field, thanking the user for their patronage.  You can see that we make use of `$gridField->getColumnCount()` to ensure that the single-cell row takes up the full width of the grid.

	:::php
	class ThankYouForUsingSilverStripe implements GridField_HTMLProvider {
		public function getHTMLFragments($gridField) {
			$colSpan = $gridField->getColumnCount();
			return array(
				'footer' => '<tr><td colspan="' . $colSpan . '">Thank you for using SilverStripe!</td></tr>',
			);
		}
	}
	
If you wish to add CSS or JavaScript for your component, you may also make `Requirements` calls in this method.

### GridField_ColumnProvider

By default, a grid contains no columns.  All the columns displayed in a grid will need to be added by an appropriate component.

For example, you may create a grid field with several components providing columns:

 * `GridFieldDefaultColumns` could provide basic data columns.
 * An editor component could provide a column containing action buttons on the right.
 * A multiselect component clould provide a column showing a checkbox on the left.

In order to provide additional columns, your component must implement `GridField_ColumnProvider`.

First you need to define 2 methods that specify which columns need to be added:

 * **`function augmentColumns($gridField, &$columns)`:** Update the `$columns` variable (passed by reference) to include the names of the additional columns that this component provides.  You can insert the values at any point you wish, for example if you need to add a column to the left of the grid, rather than the right.
 * **`function getColumnsHandled($gridField)`:** Return an array of the column names.  This overlaps with the function of `augmentColumns()` but leaves out any information about the order in which the columns are added.

Then you define 3 methods that specify what should be shown in these columns:

 * **`function getColumnContent($gridField, $record, $columnName)`:** Return the HTML content of this column for the given record.  Like `GridField_HTMLProvider`, you may make `Requirements` calls in this method.
 * **`function getColumnAttributes($gridField, $record, $columnName)`:** Return a map of the HTML attributes to add to this column's `<td>` for this record.  Most commonly, this is used to specify a colspan.
 * **`function getColumnMetadata($gridField, $columnName)`:** Return a map of the metadata about this column.  Right now, only one piece of meta-data is specified, "title".  Other components (such as those responsible for generating headers) may fetch the column meta-data for their own purposes.

### GridField_ActionProvider

Most grid fields worthy of the name are interactive in some way.  Users might able to page between results, sort by different columns, filter the results or delete records.  Where this interaction necessitates an action on the server side, the following generally happens:

 * The user triggers an action.
 * That action updates the state, database, or something else.
 * The GridField is re-rendered with that new state.

These actions can be provided by components that implement the `GridField_ActionProvider` interface.

An action is defined by two things: an action name, and zero or more named arguments.  There is no built-in notion of a record-specific or column-specific action, but you may choose to define an argument such as ColumnName or RecordID in order to implement these.

To provide your actions, define the following two functions:

 * **`function getActions($gridField)`:** Return a list of actions that this component provides.  There is no namespacing on these actions, so you need to ensure that they don't conflict with other components.
 * **`function handleAction(GridField $gridField, $actionName, $arguments, $data)`:** Handle the action defined by `$actionName` and `$arguments`.  `$data` will contain the full data from the form, if you need to access that.

To call your actions, you need to create `GridField_FormAction` elsewhere in your component.  Read more about them below.

**EXPERIMENTAL API WARNING:** handleAction implementations often contain a big switch statement and this interface might be amended on, such that each action is defined in a separate method.  If we do this, it will be done before 3.0 stable so that we can lock down the API, but early adopters should be aware of this potential for change!

### GridField_DataManipulator

A `GridField_DataManipulator` component can modify the data list.  For example, a paginating component can apply a limit, or a sorting component can apply a sort.  Generally, the data manipulator will make use of to `GridState` variables to decide how to modify the data list (see GridState below).

 * **`getManipulatedData(GridField $gridField, SS_List $dataList)`:** Given this grid's data list, return an updated list to be used with this grid.

### GridField_URLHandler

Sometimes an action isn't enough: you need to provide additional support URLs for the grid.  These URLs may return user-visible content, for example a pop-up form for editing a record's details, or they may be support URLs for front-end functionality, for example a URL that will return JSON-formatted data for a javascript grid control.

To build these components, you should implement the `GridField_URLHandler` interface.  It only specifies one method: `getURLHandlers($gridField)`.  This method should return an array similar to the `RequestHandler::$url_handlers` static.  The action handlers should also be defined on the component; they will be passed `$gridField` and `$request`.

Here is an example in full.  The actual implementation of the view and edit forms isn't included.

	:::php
	/**
	 * Provides view and edit forms at GridField-specific URLs.  These can be placed into pop-ups by an appropriate front-end.
	 * 
	 * The URLs provided will be off the following form:
	 *  - <FormURL>/field/<GridFieldName>/item/<RecordID>
	 *  - <FormURL>/field/<GridFieldName>/item/<RecordID>/edit
	 */
	class GridFieldPopupForms implements GridField_URLHandler {
		public function getURLHandlers($gridField) {
			return array(
				'item/$ID' => 'handleItem',
			);
		}

		public function handleItem($gridField, $request) {
			$record = $gridField->getList()->byId($request->param("ID"));
			return new GridFieldPopupForm_ItemRequest($gridField, $this, $record);
		}
	}

	class GridFieldPopupForm_ItemRequest extends RequestHandler {
		protected $gridField;
		protected $component;
		protected $record;

		public function __construct($gridField, $component, $record) {
			$this->gridField = $gridField;
			$this->component = $gridField;
			$this->record = $record;
			parent::__construct();
		}

		public function index() {
			echo "view form for record #" . $record->ID;
		}

		public function edit() {
			echo "edit form for record #" . $record->ID;
		}
	}

## Other tools

### GridState

Each `GridField` object has a key-store available handled by the `GridState` class.  You can call `$gridField->State` to get access to this key-store.  You may reference any key name you like, and do so recursively to any depth you like:

	:::php
	$gridField->State->Foo->Bar->Something = "hello";

Because there is no schema for the grid state, its good practice to keep your state within a namespace, by first accessing a state key that has the same name as your component class.  For example, this is how the `GridFieldSortableHeader` component manages its sort state.

	:::php
	$state = $gridField->State->GridFieldSortableHeader;
	$state->SortColumn = $arguments['SortColumn'];
	$state->SortDirection = 'asc';
	
	...
	
	$state = $gridField->State->GridFieldSortableHeader;
	if ($state->SortColumn == "") {
		return $dataList;
	} else {
		return $dataList->sort($state->SortColumn, $state->SortDirection)
	}

When checking for empty values in the state, you should compare the state value to the empty string.  This is because state values always return a `GridState_Data` object, and comparing to an empty string will call its `__toString()` method.

	:::php
	// Good
	if ($state->SortColumn == "") { ... }
	// Bad
	if (!$state->SortColumn) { ... }
	
**NOTE:** Under the hood, `GridState` is a subclass of hidden field that provides a `getData()` method that returns a `GridState_Data` object.  `$gridField->getState()` returns that `GridState_Data` object.

### GridField_Action

The `GridField_Action` class is a subclass of `FormAction` that will provide a button designed to trigger a grid field action.  This is how you can link user-interface controls to the actions defined in `GridField_ActionProvider` components.

To create the action button, instantiate the object with the following arguments to your constructor:

 * grid field
 * button name
 * button label
 * action name
 * action arguments (an array of named arguments)

For example, this could be used to create a sort button:

	:::php
	$field = new GridField_Action(
		$gridField, 'SetOrder'.$columnField, $title, 
		"sortasc", array('SortColumn' => $columnField));

Once you have created your button, you need to render it somewhere.  You can include the `GridField_Action` object in a template that is being rendered, or you can call its `Field()` method to generate the HTML content.

	:::php
	$output .= $field->Field();
	
Most likely, you will do this in `GridField_HTMLProvider::getHTMLFragments()` or `GridField_ColumnProvider::getColumnContent()`.

### GridField Helper Methods

The GridField class provides a number of methods that are useful for components.  See [the API documentation](api:GridField) for the full list, but here are a few:

 * **`getList()`:** Returns the data list for this grid, without the state modifications applied.
 * **`getState()`:** Also called as `$gridField->State`, returns the `GridState_Data` object storing the current state.
 * **`getColumnMetadata($column)`:** Return the metadata of the given column.
 * **`getColumnCount()`:** Returns the number of columns