---
title: Dynamic Default Fields
summary: Learn how to add default values to your models
---

# Dynamic Default Values

The [api:DataObject::$defaults] array allows you to specify simple static values to be the default values when a
record is created, but in many situations default values need to be dynamically calculated. In order to do this, the
[api:DataObject::populateDefaults()] method will need to be overloaded.

This method is called whenever a new record is instantiated, and you must be sure to call the method on the parent
object!

A simple example is to set a field to the current date and time:

```php
	/**
	 * Sets the Date field to the current date.
	 */
	public function populateDefaults() {
		$this->Date = date('Y-m-d');
		parent::populateDefaults();
	}

```
methods. For example:
