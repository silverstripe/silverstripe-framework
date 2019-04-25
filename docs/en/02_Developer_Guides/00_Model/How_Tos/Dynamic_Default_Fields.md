# Default Values and Records

## Static Default Values
The [DataObject::$defaults](api:SilverStripe\ORM\DataObject::$defaults) array allows you to specify simple static values to be the default values when a record is created

A simple example is if you have a dog and by default it's bark is "Woof"
```php
use SilverStripe\ORM\DataObject;

class Dog extends DataObject 
{
    private static $db = [
        'Bark' => 'Varchar(10)',
    ];
    
    private static $defaults = [
        'Bark' => 'Woof',
    ];
}
```

## Dynamic Default Values

In many situations default values need to be dynamically calculated. In order to do this, the
[DataObject::populateDefaults()](api:SilverStripe\ORM\DataObject::populateDefaults()) method will need to be overloaded.

This method is called whenever a new record is instantiated, and you must be sure to call the method on the parent
object!

A simple example is to set a field to the current date and time:

```php
/**
 * Sets the Date field to the current date.
 */
public function populateDefaults() 
{
    $this->Date = date('Y-m-d');
    parent::populateDefaults();
}
```

It's also possible to get the data from any other source, or another object, just by using the usual data retrieval
methods. For example:

```php
/**
 * This method combines the Title of the parent object with the Title of this
 * object in the FullTitle field.
 */
public function populateDefaults() 
{
    if($parent = $this->Parent()) {
        $this->FullTitle = $parent->Title . ': ' . $this->Title;
    } else {
        $this->FullTitle = $this->Title;
    }
    parent::populateDefaults();
}
```

## Static Default Records
The [DataObject::$default_records](api:SilverStripe\ORM\DataObject::$default_records) array allows you to specify default records created on dev/build.

A simple example of this is having a region model and wanting a list of regions created when the site is built
```php
use SilverStripe\ORM\DataObject;

class Region extends DataObject 
{
    private static $db = [
        'Title' => 'Varchar(45)',
    ];
    
    private static $default_records = [
        ['Title' => 'Auckland'],
        ['Title' => 'Coromandel'],
        ['Title' => 'Waikato']
    ];
}
```
