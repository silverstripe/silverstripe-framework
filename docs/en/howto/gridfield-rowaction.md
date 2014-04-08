# How to add a custom action to a GridField row

In a [GridField](/reference/grid-field) instance each table row can have a
number of actions located the end of the row such as edit or delete actions.
Each action is represented as a instance of a specific class
(e.g [api:GridFieldEditButton]) which has been added to the `GridFieldConfig`
for that `GridField`

As a developer, you can create your own custom actions to be located alongside
the built in buttons.

For example let's create a custom action on the GridField to allow the user to
perform custom operations on a row.

## Basic GridFieldCustomAction boilerplate

A basic outline of our new `GridFieldCustomAction.php` will look like something
below:

	:::php
	<?php

	class GridFieldCustomAction implements GridField_ColumnProvider, GridField_ActionProvider {

		public function augmentColumns($gridField, &$columns) {
			if(!in_array('Actions', $columns)) {
				$columns[] = 'Actions';
			}
		}

		public function getColumnAttributes($gridField, $record, $columnName) {
			return array('class' => 'col-buttons');
		}


		public function getColumnMetadata($gridField, $columnName) {
			if($columnName == 'Actions') {
				return array('title' => '');
			}
		}

		public function getColumnsHandled($gridField) {
			return array('Actions');
		}

		public function getColumnContent($gridField, $record, $columnName) {
			if(!$record->canEdit()) return;

			$field = GridField_FormAction::create(
				$gridField,
				'CustomAction'.$record->ID,
				'Do Action',
				"docustomaction",
				array('RecordID' => $record->ID)
			);


			return $field->Field();
		}

		public function getActions($gridField) {
			return array('docustomaction');
		}

		public function handleAction(GridField $gridField, $actionName, $arguments, $data) {
			if($actionName == 'docustomaction') {
				// perform your action here

				// output a success message to the user
				Controller::curr()->getResponse()->setStatusCode(
					200,
					'Do Custom Action Done.'
				);
			}
		}
	}

## Add the GridFieldCustomAction to the current `GridFieldConfig`

While we're working on the code, to add this new action to the `GridField`, add
a new instance of the class to the [api:GridFieldConfig] object. The `GridField`
[Reference](/reference/grid-field) documentation has more information about
manipulating the `GridFieldConfig` instance if required.

	:::php
	// option 1: creating a new GridField with the CustomAction
	$config = GridFieldConfig::create();
	$config->addComponent(new GridFieldCustomAction());

	$gridField = new GridField('Teams', 'Teams', $this->Teams(), $config);
	
	// option 2: adding the CustomAction to an exisitng GridField
	$gridField->getConfig()->addComponent(new GridFieldCustomAction());
	
For documentation on adding a Component to a `GridField` created by `ModelAdmin` 
please view the [ModelAdmin Reference](/reference/modeladmin#gridfield-customization) section `GridField Customization`

Now let's go back and dive through the `GridFieldCustomAction` class in more
detail.

First thing to note is that our new class implements two interfaces,
[api:GridField_ColumnProvider] and [api:GridField_ActionProvider].

Each interface allows our class to define particular behaviors and is a core
concept of the modular `GridFieldConfig` system.

The `GridField_ColumnProvider` implementation tells SilverStripe that this class
will provide the `GridField` with an additional column of information. By
implementing this interface we're required to define several methods to explain
where we want the column to exist and how we need it to be formatted. This is
done via the following methods:

 * `augmentColumns`
 * `getColumnAttributes`
 * `getColumnMetadata`
 * `getColumnsHandled`
 * `getColumnContent`

In this example, we simply add the new column to the existing `Actions` column
located at the end of the table. Our `getColumnContent` implementation produces
a custom button for the user to click on the page.

The second interface we add is `GridField_ActionProvider`. This interface is
used as we're providing a custom action for the user to take (`docustomaction`).
This action is triggered when a user clicks on the button defined in
`getColumnContent`. As with the `GridField_ColumnProvider` interface, by adding
this interface we have to define two methods to describe the behavior of the
action:

 * `getActions` returns an array of all the custom actions we want this class to
 handle (i.e `docustomaction`) .
 * `handleAction` method which will contain the logic for performing the
 specific action (e.g publishing the row to a thirdparty service).

Inside `handleAction` we have access to the current GridField and GridField row
through the `$arguments`. If your column provides more than one action (e.g two
links) both actions will be handled through the one `handleAction` method. The
called method is available as a parameter.

To finish off our basic example, the `handleAction` method simply returns a
message to the user interface indicating a successful message.

## Related

 * [GridField Reference](/reference/grid-field)
 * [ModelAdmin: A UI driven by GridField](/reference/modeladmin)
 * [Tutorial 5: Dataobject Relationship Management](/tutorials/5-dataobject-relationship-management)
