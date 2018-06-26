title: Data Types, Overloading and Casting
summary: Learn how how data is stored going in and coming out of the ORM and how to modify it.

# Data Types and Casting

Each model in a SilverStripe [DataObject](api:SilverStripe\ORM\DataObject) will handle data at some point. This includes database columns such as 
the ones defined in a `$db` array or simply a method that returns data for the template. 

A Data Type is represented in SilverStripe by a [DBField](api:SilverStripe\ORM\FieldType\DBField) subclass. The class is responsible for telling the ORM 
about how to store its data in the database and how to format the information coming out of the database, i.e. on a template.

In the `Player` example, we have four database columns each with a different data type (Int, Varchar).

**app/code/Player.php**

```php
use SilverStripe\ORM\DataObject;

class Player extends DataObject 
{
    private static $db = [
        'PlayerNumber' => 'Int',
        'FirstName' => 'Varchar(255)',
        'LastName' => 'Text',
        'Birthday' => 'Date'
    ];
}
```

## Available Types

*  [DBBoolean](api:SilverStripe\ORM\FieldType\DBBoolean): A boolean field.
*  [DBCurrency](api:SilverStripe\ORM\FieldType\DBCurrency): A number with 2 decimal points of precision, designed to store currency values.
*  [DBDate](api:SilverStripe\ORM\FieldType\DBDate): A date field
*  [DBDecimal](api:SilverStripe\ORM\FieldType\DBDecimal): A decimal number.
*  [DBEnum](api:SilverStripe\ORM\FieldType\DBEnum): An enumeration of a set of strings
*  [DBHTMLText](api:SilverStripe\ORM\FieldType\DBHTMLText): A variable-length string of up to 2MB, designed to store HTML
*  [DBHTMLVarchar](api:SilverStripe\ORM\FieldType\DBHTMLVarchar): A variable-length string of up to 255 characters, designed to store HTML
*  [DBInt](api:SilverStripe\ORM\FieldType\DBInt): An integer field.
*  [DBPercentage](api:SilverStripe\ORM\FieldType\DBPercentage): A decimal number between 0 and 1 that represents a percentage.
*  [DBDatetime](api:SilverStripe\ORM\FieldType\DBDatetime): A date / time field
*  [DBText](api:SilverStripe\ORM\FieldType\DBText): A variable-length string of up to 2MB, designed to store raw text
*  [DBTime](api:SilverStripe\ORM\FieldType\DBTime): A time field
*  [DBVarchar](api:SilverStripe\ORM\FieldType\DBVarchar): A variable-length string of up to 255 characters, designed to store raw text.

See the [API documentation](api:SilverStripe\ORM\FieldType\DBField) for a full list of available Data Types. You can define your own [DBField](api:SilverStripe\ORM\FieldType\DBField) instances if required as well. 

## Default Values

### Default values for new objects

For complex default values for newly instantiated objects see [Dynamic Default Values](how_tos/dynamic_default_fields). 
For simple values you can make use of the `$defaults` array. For example:

```php
use SilverStripe\ORM\DataObject;

class Car extends DataObject 
{   
    private static $db = [
        'Wheels' => 'Int',
        'Condition' => 'Enum(array("New","Fair","Junk"))'
    ];
    
    private static $defaults = [
        'Wheels' => 4,
        'Condition' => 'New'
    ];
}
```

### Default values for new database columns

When adding a new `$db` field to a DataObject you can specify a default value
to be applied to all existing records when the column is added in the database
for the first time. This will also be applied to any newly created objects
going forward. You do this be passing an argument for the default value in your 
`$db` items. 

For integer values, the default is the first parameter in the field specification.
For string values, you will need to declare this default using the options array.
For enum values, it's the second parameter.

For example:

```php
use SilverStripe\ORM\DataObject;

class Car extends DataObject 
{   
    private static $db = [
        'Wheels' => 'Int(4)',
        'Condition' => 'Enum(array("New","Fair","Junk"), "New")',
        'Make' => 'Varchar(["default" => "Honda"]),
    );
}
```

## Formatting Output

The Data Type does more than setup the correct database schema. They can also define methods and formatting helpers for
output. You can manually create instances of a Data Type and pass it through to the template. 

If this case, we'll create a new method for our `Player` that returns the full name. By wrapping this in a [DBVarchar](api:SilverStripe\ORM\FieldType\DBVarchar)
object we can control the formatting and it allows us to call methods defined from `Varchar` as `LimitCharacters`.

**app/code/Player.php**

```php
use SilverStripe\ORM\FieldType\DBField;
use SilverStripe\ORM\DataObject;

class Player extends DataObject 
{
    public function getName() 
    {
        return DBField::create_field('Varchar', $this->FirstName . ' '. $this->LastName);
    }
}
```

Then we can refer to a new `Name` column on our `Player` instances. In templates we don't need to use the `get` prefix.

```php
$player = Player::get()->byId(1);

echo $player->Name;
// returns "Sam Minnée"

echo $player->getName();
// returns "Sam Minnée";

echo $player->getName()->LimitCharacters(2);
// returns "Sa.."
```

## Casting

Rather than manually returning objects from your custom functions. You can use the `$casting` property.

```php
use SilverStripe\ORM\DataObject;

class Player extends DataObject 
{
    private static $casting = [
        "Name" => 'Varchar',
    ];
    
    public function getName() 
    {
        return $this->FirstName . ' '. $this->LastName;
    }
}
```

The properties on any SilverStripe object can be type casted automatically, by transforming its scalar value into an 
instance of the [DBField](api:SilverStripe\ORM\FieldType\DBField) class, providing additional helpers. For example, a string can be cast as a [DBText](api:SilverStripe\ORM\FieldType\DBText) 
type, which has a `FirstSentence()` method to retrieve the first sentence in a longer piece of text.

On the most basic level, the class can be used as simple conversion class from one value to another, e.g. to round a 
number.

```php
use SilverStripe\ORM\FieldType\DBField;
DBField::create_field('Double', 1.23456)->Round(2); // results in 1.23
```

Of course that's much more verbose than the equivalent PHP call. The power of [DBField](api:SilverStripe\ORM\FieldType\DBField) comes with its more 
sophisticated helpers, like showing the time difference to the current date:

```php
use SilverStripe\ORM\FieldType\DBField;
DBField::create_field('Date', '1982-01-01')->TimeDiff(); // shows "30 years ago"
```

## Casting ViewableData

Most objects in SilverStripe extend from [ViewableData](api:SilverStripe\View\ViewableData), which means they know how to present themselves in a view 
context. Through a `$casting` array, arbitrary properties and getters can be casted:

```php
use SilverStripe\View\ViewableData;

class MyObject extends ViewableData 
{
    
    private static $casting = [
        'MyDate' => 'Date'
    ];

    public function getMyDate() 
    {
        return '1982-01-01';
    }
}

$obj = new MyObject;
$obj->getMyDate(); // returns string
$obj->MyDate; // returns string
$obj->obj('MyDate'); // returns object
$obj->obj('MyDate')->InPast(); // returns boolean
```

## Casting HTML Text

The database field types [DBHTMLVarchar](api:SilverStripe\ORM\FieldType\DBHTMLVarchar)/[DBHTMLText](api:SilverStripe\ORM\FieldType\DBHTMLText) and [DBVarchar](api:SilverStripe\ORM\FieldType\DBVarchar)/[DBText](api:SilverStripe\ORM\FieldType\DBText) are exactly the same in 
the database.  However, the template engine knows to escape fields without the `HTML` prefix automatically in templates,
to prevent them from rendering HTML interpreted by browsers. This escaping prevents attacks like CSRF or XSS (see 
"[security](../security)"), which is important if these fields store user-provided data.

See the [Template casting](/developer_guides/templates/casting) section for controlling casting in your templates.

## Overloading

"Getters" and "Setters" are functions that help us save fields to our [DataObject](api:SilverStripe\ORM\DataObject) instances. By default, the 
methods `getField()` and `setField()` are used to set column data.  They save to the protected array, `$obj->record`. 
We can overload the default behavior by making a function called "get`<fieldname>`" or "set`<fieldname>`".

The following example will use the result of `getStatus` instead of the 'Status' database column. We can refer to the
database column using `dbObject`.

```php
use SilverStripe\ORM\DataObject;

class Player extends DataObject 
{
    private static $db = [
        "Status" => "Enum(array('Active', 'Injured', 'Retired'))"
    ];
    
    public function getStatus() 
    {
        return (!$this->obj("Birthday")->InPast()) ? "Unborn" : $this->dbObject('Status')->Value();
    }
}
```

## API Documentation

* [DataObject](api:SilverStripe\ORM\DataObject)
* [DBField](api:SilverStripe\ORM\FieldType\DBField)
