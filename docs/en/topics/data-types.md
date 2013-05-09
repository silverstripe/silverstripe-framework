# Data Types and Casting

Properties on any SilverStripe object can be type casted automatically,
by transforming its scalar value into an instance of the `[api:DBField]` class,
providing additional helpers. For example, a string can be cast as
a `[api:Text]` type, which has a `FirstSentence()` method to retrieve the first
sentence in a longer piece of text.

## Available Types

*  `[api:Boolean]`: A boolean field.
*  `[api:Currency]`: A number with 2 decimal points of precision, designed to store currency values.
*  `[api:Date]`: A date field
*  `[api:Decimal]`: A decimal number.
*  `[api:Enum]`: An enumeration of a set of strings
*  `[api:HTMLText]`: A variable-length string of up to 2MB, designed to store HTML
*  `[api:HTMLVarchar]`: A variable-length string of up to 255 characters, designed to store HTML
*  `[api:Int]`: An integer field.
*  `[api:Percentage]`: A decimal number between 0 and 1 that represents a percentage.
*  `[api:SS_Datetime]`: A date / time field
*  `[api:Text]`: A variable-length string of up to 2MB, designed to store raw text
*  `[api:Time]`: A time field
*  `[api:Varchar]`: A variable-length string of up to 255 characters, designed to store raw text

## Casting arbitrary values

On the most basic level, the class can be used as simple conversion class
from one value to another, e.g. to round a number.

	:::php
	DBField::create_field('Double', 1.23456)->Round(2); // results in 1.23

Of course that's much more verbose than the equivalent PHP call.
The power of `[api:DBField]` comes with its more sophisticated helpers,
like showing the time difference to the current date:

	:::php
	DBField::create_field('Date', '1982-01-01')->TimeDiff(); // shows "30 years ago"

## Casting ViewableData

Most objects in SilverStripe extend from `[api:ViewableData]`,
which means they know how to present themselves in a view context.
Through a `$casting` array, arbitrary properties and getters can be casted:

	:::php
	class MyObject extends ViewableData {
		static $casting = array(
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

## Casting DataObject

The `[api:DataObject]` class uses `DBField` to describe the types of its
properties which are persisted in database columns, through the `[$db](api:DataObject::$db)` property.
In addition to type information, the `DBField` class also knows how to
define itself as a database column. See the ["datamodel" topic](/topics/datamodel#casting) for more details.

<div class="warning" markdown="1">
Since we're dealing with a loosely typed language (PHP)
as well as varying type support by the different database drivers,
type conversions between the two systems are not guaranteed to be lossless.
Please take particular care when casting booleans, null values, and on float precisions.
</div>

## Casting in templates

In templates, casting helpers are available without the need for an `obj()` call.

Example: Flagging an object of type `MyObject` (see above) if it's date is in the past.
	
	:::ss
	<% if $MyObjectInstance.MyDate.InPast %>Outdated!<% end_if %>

## Casting HTML Text

The database field types `[api:HTMLVarchar]`/`[api:HTMLText]` and `[api:Varchar]`/`[api:Text]` 
are exactly the same in the database.  However, the  templating engine knows to escape 
fields without the `HTML` prefix automatically in templates,
to prevent them from rendering HTML interpreted by browsers.
This escaping prevents attacks like CSRF or XSS (see "[security](/topics/security)"),
which is important if these fields store user-provided data.

You can disable this auto-escaping by using the `$MyField.RAW` escaping hints,
or explicitly request escaping of HTML content via `$MyHtmlField.XML`.

## Related

 * ["datamodel" topic](/topics/datamodel)
 * ["security" topic](/topics/security)