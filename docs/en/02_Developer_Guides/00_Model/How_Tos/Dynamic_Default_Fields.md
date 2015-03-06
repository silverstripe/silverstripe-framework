# Dynamic Default Values

The [api:DataObject::$defaults] array allows you to specify simple static values to be the default value for when a
record is created, but in many situations default values needs to be dynamically calculated. In order to do this, the
`[api:DataObject->populateDefaults()]` method will need to be overloaded.

This method is called whenever a new record is instantiated, and you must be sure to call the method on the parent
object!

A simple example is to set a field to the current date and time:

	:::php
	/**
	 * Sets the Date field to the current date.
	 */
	public function populateDefaults() {
		$this->Date = date('Y-m-d');
		parent::populateDefaults();
	}

It's also possible to get the data from any other source, or another object, just by using the usual data retrieval
methods. For example:

	:::php
	/**
	 * This method combines the Title of the parent object with the Title of this
	 * object in the FullTitle field.
	 */
	public function populateDefaults() {
		if($parent = $this->Parent()) {
			$this->FullTitle = $parent->Title . ': ' . $this->Title;
		} else {
			$this->FullTitle = $this->Title;
		}
		parent::populateDefaults();
	}