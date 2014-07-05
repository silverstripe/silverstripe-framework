title: Model Validation and Constraints
summary: Validate your data at the model level

# Validation and Constraints

Traditionally, validation in SilverStripe has been mostly handled on the controller through [form validation](../forms).

While this is a useful approach, it can lead to data inconsistencies if the record is modified outside of the 
controller and form context.

Most validation constraints are actually data constraints which belong on the model. SilverStripe provides the 
[api:DataObject->validate] method for this purpose.

By default, there is no validation - objects are always valid! However, you can overload this method in your DataObject 
sub-classes to specify custom validation, or use the `validate` hook through a [api:DataExtension].

Invalid objects won't be able to be written - a [api:ValidationException] will be thrown and no write will occur.

It is expected that you call `validate()` in your own application to test that an object is valid before attempting a 
write, and respond appropriately if it isn't.

The return value of `validate()` is a [api:ValidationResult] object.

	:::php
	<?php

	class MyObject extends DataObject {

		private static $db = array(
			'Country' => 'Varchar',
			'Postcode' => 'Varchar'
		);

		public function validate() {
			$result = parent::validate();

			if($this->Country == 'DE' && $this->Postcode && strlen($this->Postcode) != 5) {
				$result->error('Need five digits for German postcodes');
			}

			return $result;
		}
	}

## API Documentation

* [api:DataObject]
* [api:ValidationResult];
