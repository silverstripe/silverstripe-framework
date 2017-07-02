title: Tabbed Forms
summary: Find out how CMS interfaces use jQuery UI tabs to provide nested FormFields.

# Tabbed Forms

SilverStripe's [api:SilverStripe\Forms\FormScaffolder] can automatically generate [api:SilverStripe\Forms\Form] instances for certain database models. In the
CMS and other scaffolded interfaces, it will output [api:SilverStripe\Forms\TabSet] and [api:SilverStripe\Forms\Tab] objects and use jQuery Tabs to split 
parts of the data model. 

<div class="notice" markdown="1">
All interfaces within the CMS such as [api:SilverStripe\Admin\ModelAdmin] and [api:SilverStripe\Admin\LeftAndMain] use tabbed interfaces by default.
</div>

When dealing with tabbed forms, modifying the fields in the form has a few differences. Each [api:SilverStripe\Forms\Tab] will be given a
name, and normally they all exist under the `Root` [api:SilverStripe\Forms\TabSet].

<div class="notice" markdown="1">
[api:SilverStripe\Forms\TabSet] instances can contain child [api:SilverStripe\Forms\Tab] and further [api:SilverStripe\Forms\TabSet] instances, however the CMS UI will only 
display up to two levels of tabs in the interface. If you want to group data further than that, try [api:ToggleField].
</div>

## Adding a field to a tab

	:::php
	$fields->addFieldToTab('Root.Main', new TextField(..));

## Removing a field from a tab
	
	:::php
	$fields->removeFieldFromTab('Root.Main', 'Content');

## Creating a new tab

	:::php
	$fields->addFieldToTab('Root.MyNewTab', new TextField(..));

## Moving a field between tabs

	:::php
	$content = $fields->dataFieldByName('Content');

	$fields->removeFieldFromTab('Root.Main', 'Content');
	$fields->addFieldToTab('Root.MyContent', $content);

## Add multiple fields at once

	:::php
	$fields->addFieldsToTab('Root.Content', array(
		TextField::create('Name'),
		TextField::create('Email')
	));

## API Documentation

* [api:SilverStripe\Forms\FormScaffolder]
