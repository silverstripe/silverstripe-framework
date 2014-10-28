title: Data Types, Overloading and Casting
summary: Learn how how data is stored going in and coming out of the ORM and how to modify it.

# Data Types and Casting

Each model in a SilverStripe [api:DataObject] will handle data at some point. This includes database columns such as 
the ones defined in a `$db` array or simply a method that returns data for the template. 

A Data Type is represented in SilverStripe by a [api:DBField] subclass. The class is responsible for telling the ORM 
about how to store its data in the database and how to format the information coming out of the database, i.e. on a template.

In the `Player` example, we have four database columns each with a different data type (Int, Varchar).

**mysite/code/Player.php**

	:::php
	<?php

	class Player extends DataObject {

		private static $db = array(
			'PlayerNumber' => 'Int',
			'FirstName' => 'Varchar(255)',
			'LastName' => 'Text',
			'Birthday' => 'Date'
		);
	}

## Available Types

*  [api:Boolean]: A boolean field.
*  [api:Currency]: A number with 2 decimal points of precision, designed to store currency values.
*  [api:Date]: A date field
*  [api:Decimal]: A decimal number.
*  [api:Enum]: An enumeration of a set of strings
*  [api:HTMLText]: A variable-length string of up to 2MB, designed to store HTML
*  [api:HTMLVarchar]: A variable-length string of up to 255 characters, designed to store HTML
*  [api:Int]: An integer field.
*  [api:Percentage]: A decimal number between 0 and 1 that represents a percentage.
*  [api:SS_Datetime]: A date / time field
*  [api:Text]: A variable-length string of up to 2MB, designed to store raw text
*  [api:Time]: A time field
*  [api:Varchar]: A variable-length string of up to 255 characters, designed to store raw text.

You can define your own [api:DBField] instances if required as well. See the API documentation for a list of all the
available subclasses.

## Formatting Output

The Data Type does more than setup the correct database schema. They can also define methods and formatting helpers for
output. You can manually create instances of a Data Type and pass it through to the template. 

If this case, we'll create a new method for our `Player` that returns the full name. By wrapping this in a [api:Varchar]
object we can control the formatting and it allows us to call methods defined from `Varchar` as `LimitCharacters`.

**mysite/code/Player.php**
	
	:::php
	<?php

	class Player extends DataObject {

		..

		public function getName() {
			return DBField::create_field('Varchar', $this->FirstName . ' '. $this->LastName);
		}
	}

Then we can refer to a new `Name` column on our `Player` instances. In templates we don't need to use the `get` prefix.

	:::php
	$player = Player::get()->byId(1);

	echo $player->Name;
	// returns "Sam Minnée"

	echo $player->getName();
	// returns "Sam Minnée";

	echo $player->getName()->LimitCharacters(2);
	// returns "Sa.."

### Casting

Rather than manually returning objects from your custom functions. You can use the `$casting` property.

	:::php
	<?php

	class Player extends DataObject {

	  private static $casting = array(
	    "Name" => 'Varchar',
	  );
	
	  public function getName() {
	  	return $this->FirstName . ' '. $this->LastName;
	  }
	}

The properties on any SilverStripe object can be type casted automatically, by transforming its scalar value into an 
instance of the [api:DBField] class, providing additional helpers. For example, a string can be cast as a [api:Text] 
type, which has a `FirstSentence()` method to retrieve the first sentence in a longer piece of text.

On the most basic level, the class can be used as simple conversion class from one value to another, e.g. to round a 
number.

	:::php
	DBField::create_field('Double', 1.23456)->Round(2); // results in 1.23

Of course that's much more verbose than the equivalent PHP call. The power of [api:DBField] comes with its more 
sophisticated helpers, like showing the time difference to the current date:

	:::php
	DBField::create_field('Date', '1982-01-01')->TimeDiff(); // shows "30 years ago"

## Casting ViewableData

Most objects in SilverStripe extend from [api:ViewableData], which means they know how to present themselves in a view 
context. Through a `$casting` array, arbitrary properties and getters can be casted:

	:::php
	<?php

	class MyObject extends ViewableData {
		
		private static $casting = array(
			'MyDate' => 'Date'
		);

		public function getMyDate() {
			return '1982-01-01';
		}
	}

	$obj = new MyObject;
	$obj->getMyDate(); // returns string
	$obj->MyDate; // returns string
	$obj->obj('MyDate'); // returns object
	$obj->obj('MyDate')->InPast(); // returns boolean


## Casting HTML Text

The database field types [api:HTMLVarchar]/[api:HTMLText] and [api:Varchar]/[api:Text] are exactly the same in 
the database.  However, the template engine knows to escape fields without the `HTML` prefix automatically in templates,
to prevent them from rendering HTML interpreted by browsers. This escaping prevents attacks like CSRF or XSS (see 
"[security](../security)"), which is important if these fields store user-provided data.

<div class="hint" markdown="1">
You can disable this auto-escaping by using the `$MyField.RAW` escaping hints, or explicitly request escaping of HTML 
content via `$MyHtmlField.XML`.
</div>

## Overloading

"Getters" and "Setters" are functions that help us save fields to our [api:DataObject] instances. By default, the 
methods `getField()` and `setField()` are used to set column data.  They save to the protected array, `$obj->record`. 
We can overload the default behavior by making a function called "get`<fieldname>`" or "set`<fieldname>`".

The following example will use the result of `getStatus` instead of the 'Status' database column. We can refer to the
database column using `dbObject`.

	:::php
	<?php

	class Player extends DataObject {

	  private static $db = array(
	    "Status" => "Enum(array('Active', 'Injured', 'Retired'))"
	  );

	  public function getStatus() {
	      return (!$this->obj("Birthday")->InPast()) ? "Unborn" : $this->dbObject('Status')->Value();
	  }


## API Documentation

* [api:DataObject]
* [api:DBField]