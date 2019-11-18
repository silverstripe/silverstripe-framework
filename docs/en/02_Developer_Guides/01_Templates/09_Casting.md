---
title: Formatting, Modifying and Casting Variables
summary: Information on casting, security, modifying data before it's displayed to the user and how to format data within the template.
icon: code
---
# Formatting and Casting

All objects that are being rendered in a template should be a [api:ViewableData] instance such as `DataObject`, 
`DBField` or `Controller`. From these objects, the template can include any method from the object in 
[scope](syntax#scope).

For instance, if we provide a [api:HtmlText] instance to the template we can call the `FirstParagraph` method. This will 
output the result of the [api:HtmlText::FirstParagraph()] method to the template.

**mysite/code/Page.ss**

```ss
	$Content.FirstParagraph
	<!-- returns the result of HtmlText::FirstParagragh() -->

	$LastEdited.Format("d/m/Y")
	<!-- returns the result of SS_Datetime::Format("d/m/Y") -->

```
`ViewableData` instance, you can chain the method calls.

```ss
	$Content.FirstParagraph.NoHTML
	<!-- "First Paragraph" -->

	<p>Copyright {$Now.Year}</p>
	<!-- "Copyright 2014" -->

	<div class="$URLSegment.LowerCase">
	<!-- <div class="about-us"> -->

```
See the API documentation for [api:HtmlText], [api:StringField], [api:Text] for all the methods you can use to format 
your text instances. For other objects such as [api:SS_Datetime] objects see their respective API documentation pages.
[/notice]

## forTemplate

When rendering an object to the template such as `$Me` the `forTemplate` method is called. This method can be used to 
provide default template for an object.

**mysite/code/Page.php**
	
```php
	<?php

	class Page extends SiteTree {

		public function forTemplate() {
			return "Page: ". $this->Title;
		}
	}

```
	
```ss
	$Me
	<!-- returns Page: Home -->

```

Methods which return data to the template should either return an explicit object instance describing the type of 
content that method sends back, or, provide a type in the `$casting` array for the object. When rendering that method 
to a template, SilverStripe will ensure that the object is wrapped in the correct type and values are safely escaped.

```php
	<?php

	class Page extends SiteTree {

		private static $casting = array(
			'MyCustomMethod' => 'HTMLText' 
		);

		public function MyCustomMethod() {
			return "<h1>This is my header</h1>";
		}
	}

```
accordingly. 

[note]
By default, all content without a type explicitly defined in a `$casting` array will be assumed to be `Text` content 
and HTML characters encoded.
[/note]

## Escaping

Properties are usually auto-escaped in templates to ensure consistent representation, and avoid format clashes like 
displaying un-escaped ampersands in HTML. By default, values are escaped as `XML`, which is equivalent to `HTML` for 
this purpose. 

[note]
There's some exceptions to this rule, see the ["security" guide](../security).
[/note]

In case you want to explicitly allow un-escaped HTML input, the property can be cast as [api:HTMLText]. The following 
example takes the `Content` field in a `SiteTree` class, which is of this type. It forces the content into an explicitly 
escaped format.
