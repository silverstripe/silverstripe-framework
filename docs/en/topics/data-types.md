# Data Types

These are the data-types that you can use when defining your data objects.  They are all subclasses of `[api:DBField]`
for introducing their usage.
 

## Types

*  `[api:Varchar]`: A variable-length string of up to 255 characters, designed to store raw text
*  `[api:Text]`: A variable-length string of up to 2 megabytes, designed to store raw text
*  `[api:HTMLVarchar]`: A variable-length string of up to 255 characters, designed to store HTML
*  `[api:HTMLText]`: A variable-length string of up to 2 megabytes, designed to store HTML
*  `[api:Enum]`: An enumeration of a set of strings
*  `[api:Boolean]`: A boolean field.
*  `[api:Int]`: An integer field.
*  `[api:Decimal]`: A decimal number.
*  `[api:Currency]`: A number with 2 decimal points of precision, designed to store currency values.
*  `[api:Percentage]`: A decimal number between 0 and 1 that represents a percentage.
*  `[api:Date]`: A date field
*  `[api:SS_Datetime]`: A date / time field
*  `[api:Time]`: A time field

## HTMLText vs. Text, and HTMLVarchar vs. Varchar

The database field types `[api:HTMLVarchar]` and `[api:Varchar]` are exactly the same in the database.  However, the 
templating engine knows to escape the `[api:Varchar]` field and not the `[api:HTMLVarchar]` field.  So, it's important you
use the right field if you don't want to be putting $FieldType.XML everywhere.

If you're going to put HTML content into the field, please use the field type with the HTML prefix.  Otherwise, you're
going to risk double-escaping your data, forgetting to escape your data, and generally creating a confusing situation.

## Usage

*  See [datamodel](/topics/datamodel) for information about **database schemas** implementing these types
