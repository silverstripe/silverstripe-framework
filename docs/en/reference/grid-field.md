# GridField

Gridfield is SilverStripe's implementation of data grids. Its main purpose is to display tabular data
in a format that is easy to view and modify. It can be thought of as a HTML table with some tricks.

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

		private static $allowed_actions = array('index', 'AllPages');
		
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

## Configuration

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

By default the `[api:GridFieldConfig_Base]` constructor takes a single parameter to specify the number
of items displayed on each page.

	:::php
	// I have lots of items, so increase the page size
	$myConfig = GridFieldConfig_Base::create(40);

The default page size can also be tweaked via the config. (put in your mysite/_config/config.yml)

	:::yaml
	// For updating all gridfield defaults system wide
	GridFieldPaginator:
		default_items_per_page: 40

Note that for [/reference/modeladmin](ModelAdmin) sections the default 30 number of pages can be
controlled either by setting the base `ModelAdmin.page_length` config to the desired number, or
by overriding this value in a custom subclass.

The framework comes shipped with some base GridFieldConfigs:

### Table listing with GridFieldConfig_Base

A simple read-only and paginated view of records with sortable and searchable headers.

	:::php
	$gridField = new GridField('pages', 'All pages', SiteTree::get(), GridFieldConfig_Base::create());

The fields displayed are from `DataObject::getSummaryFields()`

### Viewing records with GridFieldConfig_RecordViewer

Similar to `GridFieldConfig_Base` with the addition support of:

 - View read-only details of individual records.

The fields displayed in the read-only view is from `DataObject::getCMSFields()`

	:::php
	$gridField = new GridField('pages', 'All pages', SiteTree::get(), GridFieldConfig_RecordViewer::create());

### Editing records with GridFieldConfig_RecordEditor

Similar to `GridFieldConfig_RecordViewer` with the addition support of:

 - Viewing and changing an individual records data.
 - Deleting a record

	:::php
	$gridField = new GridField('pages', 'All pages', SiteTree::get(), GridFieldConfig_RecordEditor::create());

The fields displayed in the edit form are from `DataObject::getCMSFields()`
 
### Editing relations with GridFieldConfig_RelationEditor

Similar to `GridFieldConfig_RecordEditor`, but adds features to work on a record's has-many or 
many-many relationships. As such, it expects the list used with the `GridField` to be a
`RelationList`. That is, the list returned by a has-many or many-many getter.

The relations can be:

- Searched for existing records and add a relationship
- Detach records from the relationship (rather than removing them from the database)
- Create new related records and automatically add them to the relationship.

	:::php
	$gridField = new GridField('images', 'Linked images', $this->Images(), GridFieldConfig_RelationEditor::create());

The fields displayed in the edit form are from `DataObject::getCMSFields()`

## Customizing Detail Forms

The `GridFieldDetailForm` component drives the record editing form which is usually configured
through the configs `GridFieldConfig_RecordEditor` and `GridFieldConfig_RelationEditor`
described above. It takes its fields from `DataObject->getCMSFields()`,
but can be customized to accept different fields via its `[api:GridFieldDetailForm->setFields()]` method.

The component also has the ability to load and save data stored on join tables
when two records are related via a "many_many" relationship, as defined through
`[api:DataObject::$many_many_extraFields]`. While loading and saving works transparently,
you need to add the necessary fields manually, they're not included in the `getCMSFields()` scaffolding.

These extra fields act like usual form fields, but need to be "namespaced"
in order for the gridfield logic to detect them as fields for relation extradata,
and to avoid clashes with the other form fields.
The namespace notation is `ManyMany[<extradata-field-name>]`, so for example
`ManyMany[MyExtraField]`.

Example:

	:::php

	class Player extends DataObject {
		private static $db = array('Name' => 'Text');
		public static $many_many = array('Teams' => 'Team');
		public static $many_many_extraFields = array(
			'Teams' => array('Position' => 'Text')
		);
		public function getCMSFields() {
			$fields = parent::getCMSFields();

			if($this->ID) {
				$teamFields = singleton('Team')->getCMSFields();
				$teamFields->addFieldToTab(
					'Root.Main',
					// Please follow the "ManyMany[<extradata-name>]" convention
					new TextField('ManyMany[Position]', 'Current Position')
				);
				$config = GridFieldConfig_RelationEditor::create();
				$config->getComponentByType('GridFieldDetailForm')->setFields($teamFields);
				$gridField = new GridField('Teams', 'Teams', $this->Teams(), $config);
				$fields->findOrMakeTab('Root.Teams')->replaceField('Teams', $gridField);
			}

			return $fields;
		}
	}

	class Team extends DataObject {
		private static $db = array('Name' => 'Text');
		public static $many_many = array('Players' => 'Player');
	}

## GridFieldComponents

The `GridFieldComponent` classes are the actual workers in a gridfield. They can be responsible for:

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
 - `[api:GridFieldExportButton]`
 - `[api:GridFieldPrintButton]`
 - `[api:GridFieldPaginator]`
 - `[api:GridFieldDetailForm]`

## Flexible Area Assignment through Fragments

GridField layouts can contain many components other than the table itself,
for example a search bar to find existing relations, a button to add those,
and buttons to export and print the current data. The GridField has certain
defined areas called "fragments" where these components can be placed.
The goal is for multiple components to share the same space, for example a header row.

Built-in components:

 - `header`/`footer`: Renders in a `<thead>`/`<tfoot>`, should contain table markup
 - `before`/`after`: Renders before/after the actual `<table>`
 - `buttons-before-left`/`buttons-before-right`/`buttons-after-left`/`buttons-after-right`: 
    Renders in a shared row before the table. Requires [api:GridFieldButtonRow].

These built-ins can be used by passing the fragment names into the constructor
of various components. Note that some [api:GridFieldConfig] classes
will already have rows added to them. The following example will add a print button
at the bottom right of the table.

	:::php
	$config->addComponent(new GridFieldButtonRow('after'));
	$config->addComponent(new GridFieldPrintButton('buttons-after-right'));

Further down we'll explain how to write your own components using fragments.

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

## GridField_SaveHandler

This is used to create a handler that is called when a form containing the grid
field is saved into a record. This is useful for performing actions when saving
the record.

### GridState

Gridstate is a class that is used to contain the current state and actions on the gridfield. It's 
transfered between page requests by being inserted as a hidden field in the form.

A GridFieldComponent sets and gets data from the GridState.

## Permissions

Since GridField is mostly used in the CMS, the controller managing a GridField instance
will already do some permission checks for you, and can decline display or executing
any logic on your field. 

If you need more granular control, e.g. to consistently deny non-admins from deleting
records, use the `DataObject->can...()` methods 
(see [DataObject permissions](/reference/dataobject#permissions)).

## Creating your own Fragments

Fragments are designated areas within a GridField which can be shared between component templates.
You can define your own fragments by using a `\$DefineFragment' placeholder in your components' template.
This example will simply create an area rendered before the table wrapped in a simple `<div>`.

	:::php
	class MyAreaComponent implements GridField_HTMLProvider {
		public function getHTMLFragments( $gridField) {
			return array(
				'before' => '<div class="my-area">$DefineFragment(my-area)</div>'
			);
		}
	}

We're returning raw HTML from the component, usually this would be handled by a SilverStripe template.
Please note that in templates, you'll need to escape the dollar sign on `$DefineFragment`: 
These are specially processed placeholders as opposed to native template syntax.

Now you can add other components into this area by returning them as an array from
your [api:GridFieldComponent->getHTMLFragments()] implementation:

	:::php
	class MyShareLinkComponent implements GridField_HTMLProvider {
		public function getHTMLFragments( $gridField) {		
			return array(
				'my-area' => '<a href>...</a>'
			);
		}
	}

Your new area can also be used by existing components, e.g. the [api:GridFieldPrintButton]

	:::php
	new GridFieldPrintButton('my-component-area')

## Related

 * [ModelAdmin: A UI driven by GridField](/reference/modeladmin)
 * [Tutorial 5: Dataobject Relationship Management](/tutorials/5-dataobject-relationship-management)
 * [How to add a custom action to a GridField row](/howto/gridfield-rowaction)
