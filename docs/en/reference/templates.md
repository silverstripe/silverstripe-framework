# Templates

## Introduction

SilverStripe templates consist of HTML code augmented with special control codes, described below.  Because of this, you
can have as much control of your site's HTML code as you like.

Because the SilverStripe templating language is a string processing language it can therefore be used to make other
text-based data formats, such as XML or RTF.

Here is a very simple template:

	:::ss
	<html>
		<%-- This is my first template --%>
		<head>
			<% base_tag %>
			<title>$Title</title>
			$MetaTags
		</head>
		<body>
		<div id="Container">
			<div id="Header">
				<h1>Bob's Chicken Shack</h1>
				<% with $CurrentMember %>
				<p>You are logged in as $FirstName $Surname.</p>
				<% end_if %>
			</div>
			<div id="Navigation">
				<% if $Menu(1) %>
				<ul>
					<% loop $Menu(1) %>	  
					<li><a href="$Link" title="Go to the $Title page" class="$LinkingMode">$MenuTitle</a></li>
					<% end_loop %>
				</ul>
				<% end_if %>
			</div>
			<div class="typography">
				$Layout
			</div>
			<div id="Footer">
				<p>Copyright $Now.Year</p>
			</div>
		</div>
		</body>
	</html>

# Template elements

### Base Tag

The `<% base_tag %>` placeholder is replaced with the HTML base element. Relative links within a document (such as `<img
src="someimage.jpg" />`) will become relative to the URI specified in the base tag. This ensures the browser knows where
to locate your site’s images and css files. So it is a must for templates!

It renders in the template as `<base href="http://www.mydomain.com" /><!--[if lte IE 6]></base><![endif]-->`

### Layout Tag

In every SilverStripe theme there is a default `Page.ss` file in the `/templates` folder. `$Layout` appears in this file
and is a core variable which includes a Layout template inside the `/templates/Layout` folder once the page is rendered. 
By default the `/templates/Layout/Page.ss` file is included in the html template.

## Variables

Variables are things you can use in a template that grab data from the page and put in the HTML document.  For example:

	:::ss
	$Title
	

This inserts the value of the Title field of the page being displayed in place of `$Title`. This type of variable is called a **property**. It is often something that can be edited in the CMS.  Variables can be chained together, and include arguments.

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

SilverStripe provides lots of properties and methods. For more details on built-in page controls and variables, see http://doc.silverstripe.org/framework/en/reference/built-in-page-controls

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

## Includes

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

Includes can't directly access the parent scope of the scope active when the include is included. However you can
pass arguments to the include, which are available on the scope top within the include

	:::ss
	<% with CurrentMember %>
		<% include MemberDetails PageTitle=$Top.Title, PageID=$Top.ID %>
	<% end_with %>

You can also perform includes using the Requirements Class via the template controls. See the section on
[Includes in Templates](requirements#including_inside_template_files) for more details and examples.

	:::ss
	<% require themedCSS(LeftNavMenu) %>

### Including CSS and JavaScript files (a.k.a "Requirements")

See [CSS](/topics/css) and [Javascript](/topics/javascript) topics for individual including of files and
[requirements](reference/requirements) for good examples of including both Javascript and CSS files.

## Conditional Logic
You can conditionally include markup in the output. That is, test for something that is true or false, and based on that test, control what gets output.

The simplest if block is to check for the presence of a value.

    <% if $CurrentMember %>
        <p>You are logged in as $CurrentMember.FirstName $CurrentMember.Surname.</p>
    <% end_if %>

The following compares a page property called `MyDinner` with the value in quotes, `kipper`, which is a **literal**. If true, the text inside the if-block is output.

    <% if $MyDinner="kipper" %>
        Yummy, kipper for tea.
    <% end_if %>

Note that inside a tag like this, variables should have a '$' prefix, and literals should have quotes.  SilverStripe 2.4 didn't include the quotes or $ prefix, and while this still works, we recommend the new syntax as it is less ambiguous.

This example shows the use of the `else` option. The markup after `else` is output if the tested condition is *not* true.

    <% if $MyDinner="kipper" %>
        Yummy, kipper for tea
    <% else %>
        I wish I could have kipper :-(
    <% end_if %>

This example shows the user of `else\_if`. There can be any number of `else\_if` clauses. The conditions are tested from first to last, until one of them is true, and the markup for that condition is used. If none of the conditions are true, the markup in the `else` clause is used, if that clause is present.

    <% if $MyDinner="quiche" %>
        Real men don't eat quiche
    <% else_if $MyDinner=$YourDinner %>
        We both have good taste
    <% else %>
        Can I have some of your chips?
    <% end_if %>

This example shows the use of `not` to negate the test.

    <% if not $DinnerInOven %>
        I'm going out for dinner tonight.
    <% end_if %>

You can combine two or more conditions with `||` ("or"). The markup is used if *either* of the conditions is true.

    <% if $MyDinner=="kipper" || $MyDinner=="salmon" %>
        yummy, fish for tea
    <% end_if %>

You can combine two or more conditions with `&&` ("and"). The markup is used if *both* of the conditions are true.

    <% if $MyDinner=="quiche" && $YourDinner=="kipper" %>
        Lets swap dinners
    <% end_if %>

As you'd expect, these can be nested:

    <% if $MyDinner=="chicken" %>
        <% if $Wine=="red" %>
            You're doing it wrong
        <% else %>
            Perfect
        <% end_if %>
    <% end_if %>

## Looping Over Datasets

The `<% loop %>...<% end_loop %>` tag is used to **iterate** or loop over a collection of items. For example:

    <ul>
    <% loop $Children %>
      <li>$Title</li>
    <% end_loop %>
    </ul>

This loops over the children of a page, and generates an unordered list showing the Title property from each one. Note that $Title <i>inside</i> the loop refers to the Title property on each object that is looped over, not the current page. (To refer to the current page's Title property inside the loop, you can do `$Up.Title`. More about `Up` later.

The value that given in the `<% loop %>` tags should be a collection variable.

### Modulus and MultipleOf

$Modulus and $MultipleOf can help to build column layouts.

	:::ss
	$Modulus(value, offset) // returns an int
	$MultipleOf(factor, offset) // returns a boolean.

The following example demonstrates how you can use $Modulus(4) to generate custom column names based on your loop statement. Note that this works for any control statement (not just children)

	:::ss
	<% loop Children %>
	<div class="column-{$Modulus(4)}">
		...
	</div>
	<% end_loop %>

Will return you column-3, column-2, column-1, column-0, column-3 etc. You can use these as styling hooks to float, position as you need.

You can also use $MultipleOf(value, offset) to help build columned layouts. In this case we want to add a <br> after every 3th item

	:::ss
	<% loop Children %>
		<% if MultipleOf(3) %>
			<br>
		<% end_if %>
	<% end_loop %>

## Scope

In the `<% loop %>` section, we saw an example of two **scopes**. Outside the `<% loop %>...<% end_loop %>`, we were in the scope of the page. But inside the loop, we were in the scope of an item in the list. The scope determines where the value comes from when you refer to a variable. Typically the outer scope of a page type's layout template is the page that is currently being rendered. The outer scope of an included template is the scope that it was included into.

### With

The `<% with %>...<% end_with %>` tag lets you introduce a new scope. Consider the following example:

    <% with $CurrentMember %>
        Hello $FirstName, welcome back. Your current balance is $Balance.
    <% end_with %>

Outside the `<% with %>...<% end_with %>`, we are in the page scope. Inside it, we are in the scope of `$CurrentMember`. We can refer directly to properties and methods of that member. So $FirstName is equivalent to $CurrentMember.FirstName. This keeps the markup clean, and if the scope is a complicated expression we don't have to repeat it on each reference of a property.

`<% with %>` also lets us use a collection as a scope, so we can access properties of the collection itself, instead of iterating over it. For example:

    $Children.Length

returns the number of items in the $Children collection.

### Top

    $Top.Title

### Up

When we are in a scope, we sometimes want to refer to the scope outside the <% loop %> or <% with %>. We can do that easily by using $Up.

    $Up.Owner


## Formatting Template Values

The following example takes the Title field of our object, casts it to a `[api:Varchar]` object, and then calls
the `$XML` object on that Varchar object.

	:::ss
	<% with Title %>
	$XML
	<% end_with %>

Note that this code can be more concisely represented as follows:

	:::ss
	$Title.XML

See [data-types](/topics/data-types) for more information.

## Translations

Translations are easy to use with a template, and give access to SilverStripe's translation facilities. Here is an example:

    <%t Member.WELCOME 'Welcome {name} to {site}' name=$Member.Name site="Foobar.com" %>

Pulling apart this example we see:

 * `Member.WELCOME` is an identifier in the translation system, for which different translations may be available. This string may include named placeholders, in braces.
 * `'Welcome {name} to {site}'` is the default string used, if there is no translation for Member.WELCOME in the current locale. This contains named placeholders.
 * `name=$Member.Name` assigns a value to the named placeholder `name`. This value is substituted into the translation string wherever `{name}` appears in that string. In this case, it is assigning a value from a property `Member.Name`
 * `site="Foobar.com"` assigns a literal value to another named placeholder, `site`.

## Comments

Using standard HTML comments is supported. These comments will be included in the published site. 

	:::ss
	$EditForm <!-- Some Comment About the Edit Form -->


However you can also use special SilverStripe comments which will be stripped out of the published site. This is useful
for adding notes for other developers but for things you don't want published in the public html.

	:::ss
	$EditForm <%-- This is Located in MemberEditForm.php --%>

## Partial Caching

Partial caching lets you define blocks of your template that are cached for better performance.  See [Partial Caching](/reference/partial-caching.md) for more information.

## Creating your own Template Variables and Controls

There are two ways you can extend the template variables you have available. You can create a new database field in your
`$db` or if you do not need the variable to be editable in the cms you can create a function which returns a value in your
`Page.php` class.

	:::php
	
	**mysite/code/Page.php**
	...
	public function MyCustomValue() {
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
	public function MyCustomValues() {
	    return new ArrayData(array("Hi" => "Kia Ora", "Name" => "John Smith"));
	}


And now you could call these values by using

	:::ss
	<% with MyCustomValues %>
	$Hi , $Name
	<% end_with %>
	
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
	
	public function Counter() {
	    $this->counter += 1;
	    return $this->counter;
	}


and this template

	:::ss
	$Counter, $Counter, $Counter


will give "1, 1, 1", not "1, 2, 3"

### Casting and Escaping

Method and variables names that deal with strings or arrays of strings should have one of the following 5 prefixes:

*  **RAW_** Raw plain text, as a user would like to see it, without any HTML tags
*  **XML_** Text suitable for insertion into an HTML or XML data-set.  This may contain HTML content, for example if the
content came from a WYSIWYG editor.
*  **JS_** Data that can safely be inserted into JavaScript code.
*  **ATT_** Data that can safely be inserted into an XML or HTML attribute.

The same prefixes are used for both strings and arrays of strings.  We did this to keep things simple: passing a string
with the wrong encoding is a far subtler a problem than passing an array instead of a string, and therefore much harder
to debug.


## .typography style

By default, SilverStripe includes the `theme/css/typography.css` file into the Content area. So you should always include the
typography style around the main body of the site so both styles appear in the CMS and on the template. Where the main body of
the site is can vary, but usually it is included in the /Layout files. These files are included into the main Page.ss template
by using the `$Layout` variable so it makes sense to add the .typography style around $Layout.

	:::ss
	<div class="typography">
		$Layout
	</div>

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
	
		public function init(){
			parent::init();  
		}
	 
		public function index() {
			if(Director::is_ajax()) {
				return $this->renderWith("myAjaxTemplate");
			}
			else {
				return Array();// execution as usual in this case...
			}
		}
	}

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



## Designing reusable templates

Although SilverStripe is ultimately flexible in how you create your templates, there's a couple of best practices. These
will help you to design templates for modules, and make it easier for other site developers to integrate them into their
own base templates.

* Most of your templates should be Layout templates
* Build your templates as a [Theme](/topics/themes) so you can easily re-use and exchange them
* Your layout template should include a standard markup structure (`<div id="Layout">$Layout</div>`)
* Layout templates only include content that could be completely replaced by another module (e.g. a forum thread). It
might be infeasible to do this 100%, but remember that every piece of navigation that needs to appear inside `$Layout`
will mean that you have to customise templates when integrating the module.
*  Any CSS applied to layout templates should be flexible width. This means the surrounding root template can set its
width independently.
*  Don't include any navigation elements in your Layout templates, they should be contained in the root template.
*  Break down your templates into groups of includes.  Site integrators would then have the power to override individual
includes, rather than entire templates.

For more information about templates go to the [Advanced Templates](/reference/advanced-templates) page.

## Related

 * [Built in page controls](/reference/built-in-page-controls)
 * [Page Type Templates](/topics/page-type-templates)
 * [Typography](/reference/typography)
 * [Themes](/topics/themes)
 * [Widgets](/topics/widgets)
 * [Images](/reference/image)
 * [Tutorial 1: Building a basic site](/tutorials/1-building-a-basic-site)
 * [Tutorial 2: Extending a basic site](/tutorials/2-extending-a-basic-site)
 * [Developing Themes](/topics/theme-development)
 * [Templates: formal syntax description](/reference/templates-formal-syntax)
