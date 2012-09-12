# Gridfield

Gridfield is SilverStripe's implementation of data grids. Its main purpose is to display tabular data
in a format that is easy to view and modify. It's a can be thought of as a HTML table with some tricks.

It's built in a way that provides developers with an extensible way to display tabular data in a 
table and minimise the amount of code that needs to be written.

In order to quickly get data-focused UIs up and running,
you might also be interested in the [/reference/modeladmin](ModelAdmin) class
which is driven largely by the `GridField` class explained here.

## Overview

The `GridField` is a flexible form field for creating tables of data. It was introduced in 
SilverStripe 3.0 and replaced the `ComplexTableField`, `TableListField`, and `TableField` from 
previous versions of SilverStripe.

Each GridField is built from a number of components. Without any components, a GridField has almost no 
functionality. The components are responsible for formatting data to be readable and also modifying it.

A gridfield with only the `GridFieldDataColumn` component will display a set of read-only columns 
taken from your list, without any headers or pagination. Large datasets don't fit to one 
page, so you could add a `GridFieldPaginator` to paginatate the data. Sorting is supported by adding
 a `GridFieldSortableHeader` that enables sorting on fields that can be sorted.

This document aims to explain the usage of GridFields with code examples.

<div class="hint" markdown='1'>
GridField can only be used with datasets that are of the type `SS_List` such as `DataList`
 or `ArrayList`
</div>

## Creating a base GridField

A gridfield is often setup from a `Controller` that will output a form to the user. Even if there 
are no other HTML input fields for gathering data from users, the gridfield itself must have a 
`Form` to support interactions with it.

Here is an example where we display a basic gridfield with the default settings:

	:::php
	class GridController extends Page_Controller {
		
		public function index(SS_HTTPRequest $request) {
			$this->Content = $this->AllPages();
			return $this->render();
		}
		
		public function AllPages() {
			$gridField = new GridField('pages', 'All pages', SiteTree::get()); 
			return new Form($this, "AllPages", new FieldList($gridField), new FieldList());
		}
	}

__Note:__ This is example code and the gridfield might not be styled nicely depending on the rest of
 the css included.

This gridfield will only contain a single column with the `Title` of each page. Gridfield by default
 uses the `DataObject::$display_fields` for guessing what fields to display.

Instead of modifying a core `DataObject` we can tell the gridfield which fields to display by 
setting the display fields on the `GridFieldDataColumns` component.

	:::php
	public function AllPages() {
		$gridField = new GridField('pages', 'All pages', SiteTree::get()); 
		$dataColumns = $gridField->getConfig()->getComponentByType('GridFieldDataColumns');
		$dataColumns->setDisplayFields(array(
			'Title' => 'Title',
			'URLSegment'=> 'URL',
			'LastEdited' => 'Changed'
		));
		return new Form($this, "AllPages", new FieldList($gridField), new FieldList());
	}
	
We will now move onto what the `GridFieldConfig`s are and how to use them.

----

## GridFieldConfig

A gridfields's behaviour and look all depends on what config we're giving it. In the above example 
we did not specify one, so it picked a default config called `GridFieldConfig_Base`.

A config object is a container for `GridFieldComponents` which contain the actual functionality and
view for the gridfield.

A config object can be either injected as the fourth argument of the GridField constructor, 
`$config` or set at a later stage by using a setter:

	:::php
	// On initialisation:
	$gridField = new GridField('pages', 'All pages', SiteTree::get(), GridFieldConfig_Base::create());
	// By a setter after initialisation:
	$gridField = new GridField('pages', 'All pages', SiteTree::get());
	$gridField->setConfig(GridFieldConfig_Base::create());

The framework comes shipped with some base GridFieldConfigs:

### GridFieldConfig_Base

A simple read-only and paginated view of records with sortable and searchable headers.

	:::php
	$gridField = new GridField('pages', 'All pages', SiteTree::get(), GridFieldConfig_Base::create());

The fields displayed are from `DataObject::getSummaryFields()`

### GridFieldConfig_RecordViewer

Similar to `GridFieldConfig_Base` with the addition support of:

 - View read-only details of individual records.

The fields displayed in the read-only view is from `DataObject::getCMSFields()`

	:::php
	$gridField = new GridField('pages', 'All pages', SiteTree::get(), GridFieldConfig_RecordViewer::create());

### GridFieldConfig_RecordEditor

Similar to `GridFieldConfig_RecordViewer` with the addition support of:

 - Viewing and changing an individual records data.
 - Deleting a record

	:::php
	$gridField = new GridField('pages', 'All pages', SiteTree::get(), GridFieldConfig_RecordEditor::create());

The fields displayed in the edit form are from `DataObject::getCMSFields()`
 
### GridFieldConfig_RelationEditor

Similar to `GridFieldConfig_RecordEditor`, but adds features to work on a record's has-many or 
many-many relationships.

The relations can be:

- Searched for existing records and add a relationship
- Detach records from the relationship (rather than removing them from the database)
- Create new related records and automatically add the relationship.

	:::php
	$gridField = new GridField('pages', 'All pages', SiteTree::get(), GridFieldConfig_RecordEditor::create());

The fields displayed in the edit form are from `DataObject::getCMSFields()`

## GridFieldComponents

GridFieldComponents the actual workers in a gridfield. They can be responsible for:

 - Output some HTML to be rendered
 - Manipulate data
 - Recieve actions
 - Display links

Components are added and removed from a config by setters and getters.

	:::php
	$config = GridFieldConfig::create();

	// Add the base data columns to the gridfield
	$config->addComponent(new GridFieldDataColumns());
	$gridField = new GridField('pages', 'All pages', SiteTree::get(), $config);

It's also possible to insert a component before another component.
	
	:::php
	$config->addComponent(new GridFieldFilterHeader(), 'GridFieldDataColumns');
	
Adding multiple components in one call:

	:::php
	$config->addComponents(new GridFieldDataColumns(), new GridFieldToolbarHeader());

Removing a component:

	:::php
	$config->removeComponentsByType('GridFieldToolbarHeader');

For more information, see the [API for GridFieldConfig](http://api.silverstripe.org/3.0/framework/GridFieldConfig.html).

Here is a list of components for generic use:

 - `[api:GridFieldToolbarHeader]`
 - `[api:GridFieldSortableHeader]`
 - `[api:GridFieldFilterHeader]`
 - `[api:GridFieldDataColumns]`
 - `[api:GridFieldDeleteAction]`
 - `[api:GridFieldViewButton]`
 - `[api:GridFieldEditButton]`
 - `[api:GridFieldPaginator]`
 - `[api:GridFieldDetailForm]`

## Creating a custom GridFieldComponent

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

## GridField_FormAction

This object is used for creating actions buttons, for example a delete button. When a user clicks on
a FormAction, the gridfield finds a `GridField_ActionProvider` that listens on that action. 
`GridFieldDeleteAction` have a pretty basic implementation of how to use a Form action.

### GridState

Gridstate is a class that is used to contain the current state and actions on the gridfield. It's 
transfered between page requests by being inserted as a hidden field in the form.

A GridFieldComponent sets and gets data from the GridState.

## Related

 * [/reference/modeladmin](ModelAdmin: A UI driven by GridField)
 * [/tutorials/5-dataobject-relationship-management](Tutorial 5: Dataobject Relationship Management)