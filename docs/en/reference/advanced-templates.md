# Advanced Template Syntax

The following control codes are available. For a more details list see [built-in-page-controls](/reference/built-in-page-controls):

### Variables

	:::ss
	$Property
	$Property(param)
	$Property.SubProperty


These **variables** will call a method/field on the object and insert the returned value as a string into the template.

*  `$Property` will call `$obj->Property()` (or the field `$obj->Property`)
*  `$Property(param)` will call `$obj->Property("param")`
*  `$Property.SubProperty` will call `$obj->Property()->SubProperty()` (or field equivalents)

If a variable returns a string, that string will be inserted into the template. If the variable returns an object, then
the system will attempt to render the object through its forTemplate() method. If the `forTemplate()` method has not been
defined, the system will return an error.

Note you also cannot past a variable into a variable, so using `$Property($Value)` within your template will not work

###  Includes

Within SilverStripe templates we have the ability to include other templates from the Includes directory using the SS
'include' tag. For example, the following code would include the `Includes/SideBar.ss` code:

	:::ss
	<% include SideBar %>

The "include" tag can be particularly helpful for nested functionality. In this example, the include only happens if
a variable is true

	:::ss
	<% if CurrentMember %>
		<% include MembersOnlyInclude %>
	<% end_if %>

You can also perform includes using the Requirements Class via the template controls. See the section on
[Includes in Templates](requirements#including_inside_template_files) for more details and examples.

	:::ss
	<% require themedCSS(LeftNavMenu) %>


###  Controls

	:::ss
	<% control Property %>
	... content ...
	<% end_control %>
	
	<% control Property.SubProperty %>
	... content ...
	<% end_control %>
	
	<% control Property(param) %>
	... content ...
	<% end_control %>


Control blocks reference the same methods / fields as variables. Think of it as a foreach loop in PHP or other template
languages. `<% control Property %>` gets the same data as `$Property`.  However, instead of interpreting the result as a
string, control blocks interpret the result as an object or a array of objects.  The content between `<% control %>` and
`<% end_control %>` acts as a sub-template that is used to render the object returned.

In this example, `$A` and `$B` refer to `$obj->Property()->A()` and `$obj->Property()->B()`.

	:::ss
	<% control Property %>
		<span>$A</span>
		<span>$B</span>
	<% end_control %>


If the method/field returned is an iterator such as a `[api:DataObject]`, then the control block will be repeated for
each element of that iterator.  This is the cornerstone of all menu and list generation in SilverStripe.  

In this example, `Menu(1)` returns a `[api:DataObjectSet]` listing each top level main menu item (for more info on `Menu(1)`:
[Making a Navigation System](/tutorials/1-building-a-basic-site#Making-a-Navigation-System)).  The `<a>`
tag is repeated once for each main menu item, and the `$Link` and `$Title` values for each menu item is substituted in.

	:::ss
	<% control Menu(1) %>
		<a href="$Link">$Title</a>
	<% end_control %>



### If blocks

	:::ss
	<% if Property %>
	... optional content ...
	<% else_if OtherProperty %>
	... alternative content ...
	<% else %>
	... alternative content ...
	<% end_if %>
	
	<% if Property == value %>
	<% else %>
	<% end_if %>
	<% if Property != value %>
	<% end_if %>
	
	<% if Property && Property2 %>
	<% end_if %>
	
	<% if Property || Property2 %>
	<% end_if %>


If blocks let you mark off optional content in your template.  The optional content will only be shown if the requested
field / method returns a nonzero value.  In the second syntax, the optional content will only be shown if the requested
field / method returns the value you specify.  You should **not** include quotes around the value.

The `<% else %>` blocks perform as you would expect - content between `<% else %>` and `<% end_if %>` is shown if the first
block fails.  `<% else %>` is an optional part of the syntax - you can just use `<% if %>` and `<% end_if %>` if that's
appropriate.

### Modulus and MultipleOf

New in 2.4 you can use 2 new controls $Modulus and $MultipleOf to help build column layouts.

	:::ss
	$Modulus(value, offset) // returns an int
	$MultipleOf(factor, offset) // returns a boolean.

The following example demonstrates how you can use $Modulus(4) to generate custom column names based on your control statement. Note that this works for any control statement (not just children)

	:::ss
	<% control Children %>
	<div class="column-{$Modulus(4)}">
		...
	</div>
	<% end_control %>

Will return you column-3, column-2, column-1, column-0, column-3 etc. You can use these as styling hooks to float, position as you need.

You can also use $MultipleOf(value, offset) to help build columned layouts. In this case we want to add a <br> after every 3th item

	:::ss
	<% control Children %>
		<% if MultipleOf(3) %>
			<br>
		<% end_if %>
	<% end_control %>

### Comments

Using standard HTML comments is supported. These comments will be included in the published site. 

	:::ss
	$EditForm <!-- Some Comment About the Edit Form -->


However you can also use special SilverStripe comments which will be stripped out of the published site. This is useful
for adding notes for other developers but for things you don't want published in the public html.

	:::ss
	$EditForm <%-- This is Located in MemberEditForm.php --%>

### Formatting Template Values

The following example takes the Title field of our object, casts it to a `[api:Varchar]` object, and then calls
the `$XML` object on that Varchar object.

	:::ss
	<% control Title %>
	$XML
	<% end_control %>


Note that this code can be more concisely represented as follows:

	:::ss
	$Title.XML


See [data-types](/topics/data-types) for more information.

### Escaping

Sometimes you will have template tags which need to roll into one another. This can often result in SilverStripe looking
for a "FooBar" value rather than a "Foo" and then "Bar" value or when you have a string directly before or after the
variable you will need to escape the specific variable. In the following example `$Foo` is `3`.

	:::ss
	$Foopx // returns "" (as it looks for a Foopx value)
	{$Foo}px  // returns "3px" (CORRECT)


Or when having a `$` sign in front of the variable

	:::ss
	$$Foo // returns ""
	${$Foo} // returns "$3"


### Partial Caching

From SilverStripe 2.4 you can specify a block to cache between requests

	:::ss
	<% cacheblock 'slowoperation', LastEdited %>
	$SlowOperation
	<% end_cacheblock %>


See [partial-caching](/reference/partial-caching) for more information.


## Built In Template Variables and Controls

Out of the box, the template engine gives you lots of neat little variables and controls which you will find useful. For
a list of all the controls see [built-in-page-controls](/reference/built-in-page-controls).

## Creating your own Template Variables and Controls

There are two ways you can extend the template variables you have available. You can create a new database field in your
`$db` or if you do not need the variable to be editable in the cms you can create a function which returns a value in your
`Page.php` class.

	:::php
	
	**mysite/code/Page.php**
	...
	function MyCustomValue() {
	    return "Hi, this is my site";
	}


Will give you the ability to call `$MyCustomValue` from anywhere in your template. 

	:::ss
	I've got one thing to say to you: <i>$MyCustomValue</i>
	
	// output "I've got one thing to say to you: <i>Hi, this is my site</i>" 


Your function could return a single value as above or it could be a subclass of `[api:ArrayData]` for example a
`[api:DataObject]` with many values then each of these could be accessible via a control loop

	:::php
	..
	function MyCustomValues() {
	    return new ArrayData(array("Hi" => "Kia Ora", "Name" => "John Smith"));
	}


And now you could call these values by using

	:::ss
	<% control MyCustomValues %>
	$Hi , $Name
	<% end_control %>
	
	// output "Kia Ora , John Smith" 


Or by using the dot notation you would have

	:::ss
	$MyCustomValues.Hi , $MyCustomValues.Name
	
	// output "Kia Ora , John Smith"


### Side effects

All functions that provide data to templates must have no side effects, as the value is cached after first access.

For example, this Controller method

	:::php
	private $counter = 0;
	
	function Counter() {
	    $this->counter += 1;
	    return $this->counter;
	}


and this template

	:::ss
	$Counter, $Counter, $Counter


will give "1, 1, 1", not "1, 2, 3"

## Calling templates from PHP code

This is all very well and good, but how do the templates actually get called?  

Templates do nothing on their own.  Rather, they are used to render *a particular object*.  All of the `<% if %>`, `<%control %>`, 
and variable codes are methods or parameters that are called *on that object*.  All that is necessary is
that the object is an instance of `[api:ViewableData]` (or one of its subclasses).

The key is `[api:ViewableData::renderWith()]`.  This method is passed a For example, within the controller's default action,
there is an instruction of the following sort:

	:::php
	$controller->renderWith("TemplateName");


Here's what this line does:

*  First `renderWith()` constructs a new object: `$template = new SSViewer("TemplateName");`
*  `[api:SSViewer]` will take the content of `TemplateName.ss`, and turn it into PHP code.
*  Then `renderWith()` passes the controller to `$template->process($controller);`
*  `SSViewer::process()` will execute the PHP code generated from `TemplateName.ss` and return the results.

`renderWith()` returns a string - the populated template.  In essence, it uses a template to cast an object to a string.

`renderWith()` can also be passed an array of template names.  If this is done, then `renderWith()` will use the first
available template name.

Below is an example of how to implement renderWith.  In the example below the page is rendered using the myAjaxTemplate
if the page is called by an ajax function (using `[api:Director::is_ajax()]`).  Note that the index function is called by
default if it exists and there is no action in the url parameters.

	:::php
	class MyPage_Controller extends Page_Controller {
	
		function init(){
			parent::init();  
		}
	 
		function index() {
			if(Director::is_ajax()) {
				return $this->renderWith("myAjaxTemplate");
			}
			else {
				return Array();// execution as usual in this case...
			}
		}
	}


### How does ViewableData work?

ViewableData provides two methods that perform the casting necessary for templates to work as we have described.

*  `obj("Parameter")` - Return the given field / method as an object, casting if necessary 
*  `XML_val("Parameter)` - Return the given field / method as a scalar, converting to an XML-safe format and casting if
necessary

These methods work as described in the syntax section above.  SSViewer calls these methods when processing templates. 
However, if you want, you can call `obj()` and `val()` yourself.

## Fragment Link rewriting

Fragment links are links with a "#" in them.  A frequent use-case is to use fragment links to point to different
sections of the current page.  For example, we might have this in our template.

For, example, we might have this on http://www.example.com/my-long-page/

	:::ss
	<ul>
		<li><a href="#section1">Section 1</a></li>
		<li><a href="#section2">Section 2</a></li>
	</ul>


So far, so obvious.  However, things get tricky because of we have set our `<base>` tag to point to the root of your
site.  So, when you click the first link you will be sent to http://www.example.com/#section1 instead of
http://www.example.com/my-long-page/#section1

In order to prevent this situation, the SSViewer template renderer will automatically rewrite any fragment link that
doesn't specify a URL before the fragment, prefixing the URL of the current page.  For our example above, the following
would be created:

	:::ss
	<ul>
		<li><a href="my-long-page/#section1">Section 1</a></li>
		<li><a href="my-long-page/#section2">Section 2</a></li>
	</ul>


There are cases where this can be unhelpful.  HTML fragments created from Ajax responses are the most common.  In these
situations, you can disable fragment link rewriting like so:

	:::php
	SSViewer::setOption('rewriteHashlinks', false);


## Casting and Escaping

Method and variables names that deal with strings or arrays of strings should have one of the following 5 prefixes:

*  **RAW_** Raw plain text, as a user would like to see it, without any HTML tags
*  **XML_** Text suitable for insertion into an HTML or XML data-set.  This may contain HTML content, for example if the
content came from a WYSIWYG editor.
*  **JS_** Data that can safely be inserted into JavaScript code.
*  **ATT_** Data that can safely be inserted into an XML or HTML attribute.

The same prefixes are used for both strings and arrays of strings.  We did this to keep things simple: passing a string
with the wrong encoding is a far subtler a problem than passing an array instead of a string, and therefore much harder
to debug.


## Related
* [Templates](/topics/templates)
* [Themes](/topics/themes)
* [Developing Themes](/topics/theme-development)
* [Widgets](/topics/widgets)
* [Images](/reference/image)
* [Built in page controls](/reference/built-in-page-controls)
* [Including Templates](/reference/requirements)