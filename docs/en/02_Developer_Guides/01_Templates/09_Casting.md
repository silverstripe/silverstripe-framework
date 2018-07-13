title: Formatting, Modifying and Casting Variables
summary: Information on casting, security, modifying data before it's displayed to the user and how to format data within the template.

# Formatting and Casting

All objects that are being rendered in a template should be a [ViewableData](api:SilverStripe\View\ViewableData) instance such as `DataObject`, 
`DBField` or `Controller`. From these objects, the template can include any method from the object in 
[scope](syntax#scope).

For instance, if we provide a [DBHtmlText](api:SilverStripe\ORM\FieldType\DBHtmlText) instance to the template we can call the `FirstParagraph` method. This will 
output the result of the [DBHtmlText::FirstParagraph()](api:SilverStripe\ORM\FieldType\DBHtmlText::FirstParagraph()) method to the template.

**app/code/Page.ss**

```ss
$Content.FirstParagraph
<!-- returns the result of HtmlText::FirstParagragh() -->

$LastEdited.Format("d/m/Y")
<!-- returns the result of SS_Datetime::Format("d/m/Y") -->
```

Any public method from the object in scope can be called within the template. If that method returns another 
`ViewableData` instance, you can chain the method calls.

```ss
$Content.FirstParagraph.NoHTML
<!-- "First Paragraph" -->

<p>Copyright {$Now.Year}</p>
<!-- "Copyright 2014" -->

<div class="$URLSegment.LowerCase">
<!-- <div class="about-us"> -->
```

<div class="notice" markdown="1">
See the API documentation for [DBHtmlText](api:SilverStripe\ORM\FieldType\DBHtmlText), [FieldType](api:SilverStripe\ORM\FieldType), [DBText](api:SilverStripe\ORM\FieldType\DBText) for all the methods you can use to format 
your text instances. For other objects such as [DBDatetime](api:SilverStripe\ORM\FieldType\DBDatetime) objects see their respective API documentation pages.
</div>

## forTemplate

When rendering an object to the template such as `$Me` the `forTemplate` method is called. This method can be used to 
provide default template for an object.

**app/code/Page.php**

```php
use SilverStripe\CMS\Model\SiteTree;

class Page extends SiteTree 
{

    public function forTemplate() 
    {
        return "Page: ". $this->Title;
    }
}
```

**app/templates/Page.ss**

```ss
$Me
<!-- returns Page: Home -->
```

## Casting

Methods which return data to the template should either return an explicit object instance describing the type of 
content that method sends back, or, provide a type in the `$casting` array for the object. When rendering that method 
to a template, SilverStripe will ensure that the object is wrapped in the correct type and values are safely escaped.

```php
use SilverStripe\CMS\Model\SiteTree;

class Page extends SiteTree 
{

    private static $casting = [
        'MyCustomMethod' => 'HTMLText' 
    ];

    public function MyCustomMethod() 
    {
        return "<h1>This is my header</h1>";
    }
}
```

When calling `$MyCustomMethod` SilverStripe now has the context that this method will contain HTML and escape the data
accordingly. 

<div class="note" markdown="1">
By default, all content without a type explicitly defined in a `$casting` array will be assumed to be `Text` content 
and HTML characters encoded.
</div>

## Escaping

Properties are usually auto-escaped in templates to ensure consistent representation, and avoid format clashes like 
displaying un-escaped ampersands in HTML. By default, values are escaped as `XML`, which is equivalent to `HTML` for 
this purpose. 

<div class="note" markdown="1">
There's some exceptions to this rule, see the ["security" guide](../security).
</div>

For every field used in templates, a casting helper will be applied. This will first check for any
`casting` helper on your model specific to that field, and will fall back to the `default_cast` config
in case none are specified.

By default, `ViewableData.default_cast` is set to `Text`, which will ensure all fields have special
characters HTML escaped by default.

The most common casting types are:

 * `Text` Which is a plain text string, and will be safely encoded via HTML entities when placed into
 a template.
 * `Varchar` which is the same as `Text` but for single-line text that should not have line breaks.
 * `HTMLFragment` is a block of raw HTML, which should not be escaped. Take care to sanitise any HTML
 value saved into the database.
 * `HTMLText` is a `HTMLFragment`, but has shortcodes enabled. This should only be used for content
 that is modified via a TinyMCE editor, which will insert shortcodes.
 * `Int` for integers.
 * `Decimal` for floating point values.
 * `Boolean` For boolean values.
 * `Datetime` for date and time.
 
See the [Model data types and casting](/developer_guides/model/data_types_and_casting) section for
instructions on configuring your model to declare casting types for fields.

## Escape methods in templates

Within the template, fields can have their encoding customised at a certain level with format methods.
See [DBField](api:SilverStripe\ORM\FieldType\DBField) for the specific implementation, but they will generally follow the below rules:

* `$Field` with no format method supplied will correctly cast itself for the HTML template, as defined
  by the casting helper for that field. In most cases this is the best method to use for templates.
* `$Field.XML` Will invoke `htmlentities` on special characters in the value, even if it's already
  cast as HTML.
* `$Field.ATT` will ensure the field is XML encoded for placement inside a HTML element property.
  This will invoke `htmlentities` on the value (even if already cast as HTML) and will escape quotes.
* `Field.JS` will cast this value as a javascript string. E.g. `var fieldVal = '$Field.JS';` can
  be used in javascript defined in templates to encode values safely.
* `$Field.CDATA` will cast this value safely for insertion as a literal string in an XML file.
  E.g. `<element>$Field.CDATA</element>` will ensure that the `<element>` body is safely escaped
  as a string.

<div class="warning" markdown="1">
Note: Take care when using `.XML` on `HTMLText` fields, as this will result in double-encoded
html. To ensure that the correct encoding is used for that field in a template, simply use
`$Field` by itself to allow the casting helper to determine the best encoding itself.
</div>

## Cast summary methods

Certain subclasses of DBField also have additional summary or manipulations methods, each of
which can be chained in order to perform more complicated manipulations.

For instance, The following class methods can be used in templates for the below types:

Text / HTMLText methods:

* `$Plain` Will convert any HTML to plain text version. For example, could be used for plain-text
  version of emails.
* `$LimitSentences(<num>)` Will limit to the first `<num>` sentences in the content. If called on
  HTML content this will have all HTML stripped and converted to plain text.

## Related Lessons
* [Dealing with arbitrary template data](https://www.silverstripe.org/learn/lessons/v4/dealing-with-arbitrary-template-data-1)
  
