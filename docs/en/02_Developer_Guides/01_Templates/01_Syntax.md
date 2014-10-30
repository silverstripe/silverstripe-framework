title: Template Syntax
summary: A look at the operations, variables and language controls you can use within templates.

# Template Syntax

SilverStripe templates are plain text files that have `.ss` extension and located within the `templates` directory of 
a module, theme, or your `mysite` folder. A template can contain any markup language (e.g HTML, CSV, JSON..) and before
being rendered to the user, they're processed through [api:SSViewer]. This process replaces placeholders such as `$Var` 
with real content from your [model](../model) and allows you to define logic controls like `<% if $Var %>`. 

An example of a SilverStripe template is below:

**mysite/templates/Page.ss**

	:::ss
	<html>
		<head>
			<% base_tag %>
			<title>$Title</title>
			<% require themedCSS("screen") %>
		</head>
		<body>
			<header>
				<h1>Bob's Chicken Shack</h1>
			</header>

			<% with $CurrentMember %>
				<p>Welcome $FirstName $Surname.</p>
			<% end_with %>

			<% if $Dishes %>
			<ul>
				<% loop $Dishes %>
					<li>$Title ($Price.Nice)</li>
				<% end_loop %>
			</ul>
			<% end_if %>

			<% include Footer %>
		</body>
	</html>

<div class="note">
Templates can be used for more than HTML output. You can use them to output your data as JSON, XML, CSV or any other 
text-based format.
</div>

## Variables

Variables are placeholders that will be replaced with data from the [DataModel](../model/) or the current 
[Controller](../controllers). Variables are prefixed with a `$` character. Variable names must start with an 
alphabetic character or underscore, with subsequent characters being alphanumeric or underscore:

	:::ss
	$Title

This inserts the value of the Title database field of the page being displayed in place of `$Title`. 

Variables can be chained together, and include arguments.

	:::ss
	$Foo
	$Foo(param)
	$Foo.Bar

These variables will call a method / field on the object and insert the returned value as a string into the template.

*  `$Foo` will call `$obj->Foo()` (or the field `$obj->Foo`)
*  `$Foo(param)` will call `$obj->Foo("param")`
*  `$Foo.Bar` will call `$obj->Foo()->Bar()` 

If a variable returns a string, that string will be inserted into the template. If the variable returns an object, then
the system will attempt to render the object through its' `forTemplate()` method. If the `forTemplate()` method has not 
been defined, the system will return an error.

<div class="note" markdown="1">
For more detail around how variables are inserted and formatted into a template see 
[Formating, Modifying and Casting Variables](casting)
</div>

Variables can come from your database fields, or custom methods you define on your objects.

**mysite/code/Page.php**

	:::php
	public function UsersIpAddress() {
		return $this->request->getIP();
	}

**mysite/code/Page.ss**

	:::html
	<p>You are coming from $UsersIpAddress.</p>

<div class="node" markdown="1">
	Method names that begin with `get` will automatically be resolved when their prefix is excluded. For example, the above method call `$UsersIpAddress` would also invoke a method named `getUsersIpAddress()`.
</div>

The variable's that can be used in a template vary based on the object currently in [scope](#scope). Scope defines what
object the methods get called on. For the standard `Page.ss` template the scope is the current [api:Page_Controller] 
class. This object gives you access to all the database fields on [api:Page_Controller], its corresponding [api:Page]
record and any subclasses of those two.

**mysite/code/Layout/Page.ss**

	:::ss
	$Title
	// returns the page `Title` property

	$Content
	// returns the page `Content` property


## Conditional Logic

The simplest conditional block is to check for the presence of a value (does not equal 0, null, false).

	:::ss
	<% if $CurrentMember %>
		<p>You are logged in as $CurrentMember.FirstName $CurrentMember.Surname.</p>
	<% end_if %>

A conditional can also check for a value other than falsy.

	:::ss
	<% if $MyDinner == "kipper" %>
		Yummy, kipper for tea.
	<% end_if %>

<div class="notice" markdown="1">
When inside template tags variables should have a '$' prefix, and literals should have quotes. 
</div>

Conditionals can also provide the `else` case.

	:::ss
	<% if $MyDinner == "kipper" %>
		Yummy, kipper for tea
	<% else %>
		I wish I could have kipper :-(
	<% end_if %>

`else_if` commands can be used to handle multiple `if` statements.

	:::ss
	<% if $MyDinner == "quiche" %>
		Real men don't eat quiche
	<% else_if $MyDinner == $YourDinner %>
		We both have good taste
	<% else %>
		Can I have some of your chips?
	<% end_if %>

### Negation

The inverse of `<% if %>` is `<% if not %>`.

	:::ss
	<% if not $DinnerInOven %>
		I'm going out for dinner tonight.
	<% end_if %>

### Boolean Logic

Multiple checks can be done using `||`, `or`, `&&` or `and`. 

If *either* of the conditions is true.

	:::ss
	<% if $MyDinner == "kipper" || $MyDinner == "salmon" %>
		yummy, fish for tea
	<% end_if %>

If *both* of the conditions are true.

	:::ss
	<% if $MyDinner == "quiche" && $YourDinner == "kipper" %>
		Lets swap dinners
	<% end_if %>

### Inequalities

You can use inequalities like `<`, `<=`, `>`, `>=` to compare numbers.

	:::ss
	<% if $Number >= "5" && $Number <= "10" %>
		Number between 5 and 10
	<% end_if %>


## Includes

Within SilverStripe templates we have the ability to include other templates from the `template/Includes` directory 
using the `<% include %>` tag.

	:::ss
	<% include SideBar %>

The `include` tag can be particularly helpful for nested functionality and breaking large templates up. In this example, 
the include only happens if the user is logged in.

	:::ss
	<% if $CurrentMember %>
		<% include MembersOnlyInclude %>
	<% end_if %>

Includes can't directly access the parent scope when the include is included. However you can pass arguments to the 
include.

	:::ss
	<% with $CurrentMember %>
		<% include MemberDetails Top=$Top, Name=$Name %>
	<% end_with %>


## Looping Over Lists

The `<% loop %>` tag is used to iterate or loop over a collection of items such as [api:DataList] or a [api:ArrayList] 
collection.

	:::ss
	<h1>Children of $Title</h1>

	<ul>
		<% loop $Children %>
			<li>$Title</li>
		<% end_loop %>
	</ul>

This snippet loops over the children of a page, and generates an unordered list showing the `Title` property from each 
page. 

<div class="notice" markdown="1">
$Title inside the loop refers to the Title property on each object that is looped over, not the current page like
the reference of `$Title` outside the loop. 

This demonstrates the concept of [Scope](#scope). When inside a <% loop %> the scope of the template has changed to the 
object that is being looped over.
</div>

### Altering the list

`<% loop %>` statements iterate over a [api:DataList] instance. As the template has access to the list object, 
templates can call [api:DataList] methods. 

Sort the list by a given field.

	:::ss
	<ul>
		<% loop $Children.Sort(Title, ASC) %>
			<li>$Title</li>
		<% end_loop %>
	</ul>

Limiting the number of items displayed.

	:::ss
	<ul>
		<% loop $Children.Limit(10) %>
			<li>$Title</li>
		<% end_loop %>
	</ul>

Reversing the loop.

	:::ss
	<ul>
		<% loop $Children.Reverse %>
			<li>$Title</li>
		<% end_loop %>
	</ul>

Filtering the loop

	:::ss
	<ul>
		<% loop $Children.Filter('School', 'College') %>
			<li>$Title</li>
		<% end_loop %>
	</ul>

Methods can also be chained

	:::ss
	<ul>
		<% loop $Children.Filter('School', 'College').Sort(Score, DESC) %>
			<li>$Title</li>
		<% end_loop %>
	</ul>

### Position Indicators

Inside the loop scope, there are many variables at your disposal to determine the current position in the list and 
iteration.

 * `$Even`, `$Odd`: Returns boolean, handy for zebra striping
 * `$EvenOdd`: Returns a string, either 'even' or 'odd'. Useful for CSS classes.
 * `$First`, `$Last`, `$Middle`: Booleans about the position in the list
 * `$FirstLast`: Returns a string, "first", "last", or "". Useful for CSS classes.
 * `$Pos`: The current position in the list (integer). Will start at 1.
 * `$TotalItems`: Number of items in the list (integer)

	:::ss
	<ul>
		<% loop $Children.Reverse %>
			<% if First %>
				<li>My Favourite</li>
			<% end_if %>

			<li class="$EvenOdd">Child $Pos of $TotalItems - $Title</li>
		<% end_loop %>
	</ul>

<div class="info" markdown="1">
A common task is to paginate your lists. See the [Pagination](how_tos/pagination) how to for a tutorial on adding 
pagination.
</div>

### Modulus and MultipleOf

$Modulus and $MultipleOf can help to build column and grid layouts.

	:::ss
	// returns an int
	$Modulus(value, offset)

	// returns a boolean.
	$MultipleOf(factor, offset) 

	<% loop $Children %>
	<div class="column-{$Modulus(4)}">
		...
	</div>
	<% end_loop %>

	// returns <div class="column-3">, <div class="column-2">,

<div class="hint" markdown="1">
`$Modulus` is useful for floated grid CSS layouts. If you want 3 rows across, put $Modulus(3) as a class and add a 
`clear: both` to `.column-1`.
</div>

$MultipleOf(value, offset) can also be utilized to build column and grid layouts. In this case we want to add a `<br>` 
after every 3th item.

	:::ss
	<% loop $Children %>
		<% if $MultipleOf(3) %>
			<br>
		<% end_if %>
	<% end_loop %>

### Escaping

Sometimes you will have template tags which need to roll into one another. Use `{}` to contain variables.

	:::ss
	$Foopx // will returns "" (as it looks for a `Foopx` value)
	{$Foo}px  // returns "3px" (CORRECT)


Or when having a `$` sign in front of the variable such as displaying money.

	:::ss
	$$Foo // returns ""
	${$Foo} // returns "$3"

You can also use a backslash to escape the name of the variable, such as:

	:::ss
	$Foo // returns "3"
	\$Foo // returns "$Foo"

<div class="hint" markdown="1">
For more information on formatting and casting variables see [Formating, Modifying and Casting Variables](casting)
</div>

## Scope

In the `<% loop %>` section, we saw an example of two **scopes**. Outside the `<% loop %>...<% end_loop %>`, we were in 
the scope of the top level `Page`. But inside the loop, we were in the scope of an item in the list (i.e the `Child`) 

The scope determines where the value comes from when you refer to a variable. Typically the outer scope of a `Page.ss` 
layout template is the [api:Page_Controller] that is currently being rendered. 

When the scope is a `Page_Controller` it will automatically also look up any methods in the corresponding `Page` data
record. In the case of `$Title` the flow looks like

	$Title --> [Looks up: Current Page_Controller and parent classes] --> [Looks up: Current Page and parent classes].

The list of variables you could use in your template is the total of all the methods in the current scope object, parent
classes of the current scope object, and any [api:Extension] instances you have.

### Navigating Scope

#### Up

When in a particular scope, `$Up` takes the scope back to the previous level.

	:::ss
	<h1>Children of '$Title'</h1>

	<% loop $Children %>
		<p>Page '$Title' is a child of '$Up.Title'</p>
	
		<% loop $Children %>
			<p>Page '$Title' is a grandchild of '$Up.Up.Title'</p>
		<% end_loop %>
	<% end_loop %>

Given the following structure, it will output the text.
	
	My Page
	|
	+-+ Child 1
 	| 	|
 	| 	+- Grandchild 1
 	|
 	+-+ Child 2

	Children of 'My Page'

	Page 'Child 1' is a child of 'My Page'
	Page 'Grandchild 1' is a grandchild of 'My Page'
	Page 'Child 2' is a child of 'MyPage'


#### Top

While `$Up` provides us a way to go up one level of scope, `$Top` is a shortcut to jump to the top most scope of the 
page. The  previous example could be rewritten to use the following syntax.

	:::ss
	<h1>Children of '$Title'</h1>

	<% loop $Children %>
		<p>Page '$Title' is a child of '$Top.Title'</p>
	
		<% loop $Children %>
			<p>Page '$Title' is a grandchild of '$Top.Title'</p>
		<% end_loop %>
	<% end_loop %>



### With

The `<% with %>` tag lets you change into a new scope. Consider the following example:

	:::ss
	<% with $CurrentMember %>
		Hello, $FirstName, welcome back. Your current balance is $Balance.
	<% end_with %>

This is functionalty the same as the following:

	:::ss
	Hello, $CurrentMember.FirstName, welcome back. Yout current balance is $CurrentMember.Balance

Notice that the first example is much tidier, as it removes the repeated use of the `$CurrentMember` accessor.

Outside the `<% with %>.`, we are in the page scope. Inside it, we are in the scope of `$CurrentMember` object. We can 
refer directly to properties and methods of the [api:Member] object. `$FirstName` inside the scope is equivalent to 
`$CurrentMember.FirstName`.



## Comments

Using standard HTML comments is supported. These comments will be included in the published site.

	:::ss
	$EditForm <!-- Some public comment about the form -->


However you can also use special SilverStripe comments which will be stripped out of the published site. This is useful
for adding notes for other developers but for things you don't want published in the public html.

	:::ss
	$EditForm <%-- Some hidden comment about the form --%>

## Related

[CHILDREN]

## How to's

[CHILDREN How_Tos]

## API Documentation

* [api:SSViewer]
* [api:SS_TemplateManifest]