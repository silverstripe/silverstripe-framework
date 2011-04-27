Template language in SilverStripe 3

# Learn with Examples
This section shows examples that you can use in your templates.

## Variables
Variables are things you can use in a template that grab data from the page and put in the HTML document.

Here is an example using a variable:

    $Title

This inserts the value of the Title field of the page being displayed in place of <code>$Title</code>. This type of variable is called a <b>property</b>. It is often something that can be edited in the CMS.

Sometimes a variable will reference an <b>object</b>. For example, $CurrentMember returns an object representing the currently logged in user. You can use the "dot syntax" to access properties or methods on the object. For example:

    Hi $CurrentMember.FirstName, welcome back!

references the FirstName property in the current member object.

Another type of variable is called a <b>method</b>. Methods behave in a similar way, but they can take parameters:

<code>example</code>

SilverStripe provides lots more properties and methods. For more details on built-in page controls and variables, see http://doc.silverstripe.org/sapphire/en/reference/built-in-page-controls

## Includes

    <% include Sidebar %>

## Requirements

## Conditional Logic
You can conditionally include markup in the output. That is, test for something that is true or false, and based on that test, control what gets output.

The following compares a page property called <code>MyDinner</code> with the value in quotes, <code>kipper</code>, which is a <b>literal</b>. If true, the text inside the if-block is output.

    <% if $MyDinner="kipper" %>
        Yummy, kipper for tea.
    <% end_if %>

Note that inside a tag like this, variables should have a '$' prefix, and literals should have quotes.

This example shows the use of the <code>else</code> option. The markup after <code>else</code> is output if the tested condition is *not* true.

    <% if $MyDinner="kipper" %>
        Yummy, kipper for tea
    <% else %>
        I wish I could have kipper :-(
    <% end_if %>

This example shows the user of <code>else\_if</code>. There can be any number of <code>else\_if</code> clauses. The conditions are tested from first to last, until one of them is true, and the markup for that condition is used. If none of the conditions are true, the markup in the <code>else</code> clause is used, if that clause is present.

    <% if $MyDinner="quiche" %>
        Real men don't eat quiche
    <% else_if $MyDinner=$YourDinner %>
        We both have good taste
    <% else %>
        Can I have some of your chips?
    <% end_if %>

This example shows the use of <code>not</code> to negate the test.

    <% if not $DinnerInOven %>
        I'm going out for dinner tonight.
    <% end_if %>

You can combine two or more conditions with <code>||</code> ("or"). The markup is used if *either* of the conditions is true.

    <% if $MyDinner=="kipper" || $MyDinner=="salmon" %>
        yummy, fish for tea
    <% end_if %>

You can combine two or more conditions with <code>&&</code> ("and"). The markup is used if *both* of the conditions are true.

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

The <code><% loop %>...<% end_loop %></code> tag is used to <b>iterate</b> or loop over a collection of items. For example:

    <ul>
    <% loop $Children %>
      <li>$Title</li>
    <% end_loop %>
    </ul>

This loops over the children of a page, and generates an unordered list showing the Title property from each one. Note that $Title <i>inside</i> the loop refers to the Title property on each object that is looped over, not the current page. (To refer to the current page's Title property inside the loop, you can do <code>$Up.Title</code>. More about <code>Up</code> later.

The value that given in the <code><% loop %></code> tags should be a collection variable.

## Scope

In the <code><% loop %></code> section, we saw an example of two <b>scopes</b>. Outside the <code><% loop %>...<% end_loop %></code>, we were in the scope of the page. But inside the loop, we were in the scope of an item in the list. The scope determines where the value comes from when you refer to a variable. Typically the outer scope of a page type's layout template is the page that is currently being rendered. The outer scope of an included template is the scope that it was included into.

### With

The <code><% with %>...<% end_with %></code> tag lets you introduce a new scope. Consider the following example:

    <% with $CurrentMember %>
        Hello $FirstName, welcome back. Your current balance is $Balance.
    <% end_with %>

Outside the <code><% with %>...<% end_with %></code>, we are in the page scope. Inside it, we are in the scope of <code>$CurrentMember</code>. We can refer directly to properties and methods of that member. So $FirstName is equivalent to $CurrentMember.FirstName. This keeps the markup clean, and if the scope is a complicated expression we don't have to repeat it on each reference of a property.

<code><% with %></code> also lets us use a collection as a scope, so we can access properties of the collection itself, instead of iterating over it. For example:

    $Children.Length

returns the number of items in the $Children collection.

### Top

    $Top.Title

### Up

When we are in a scope, we sometimes want to refer to the scope outside the <% loop %> or <% with %>. We can do that easily by using $Up.

    $Up.Owner

## Translations

Translations are easy to use with a template, and give access to SilverStripe's translation facilities. Here is an example:

    <%t Member.WELCOME 'Welcome {name} to {site}' name=$Member.Name site="Foobar.com" %>

Pulling apart this example we see:

 * <code>Member.WELCOME</code> is an identifier in the translation system, for which different translations may be available. This string may include named placeholders, in braces.
 * <code>'Welcome {name} to {site}'</code> is the default string used, if there is no translation for Member.WELCOME in the current locale. This contains named placeholders.
 * <code>name=$Member.Name</code> assigns a value to the named placeholder <code>name</code>. This value is substituted into the translation string wherever <code>{name}</code> appears in that string. In this case, it is assigning a value from a property <code>Member.Name</code>
 * <code>site="Foobar.com"</code> assigns a literal value to another named placeholder, <code>site</code>.

## Comments

    <%-- this is a comment --%>

Unlike HTML comments (e.g. <code>&lt;!-- foo --&gt;</code>), template comments are not rendered to the output at all.

## Partial Caching


# Formal Treatment of the Template Language

## White space

## Expressions

### Literals

Definition:
    literal := number | stringLiteral
    number := digit { digit }*
    stringLiteral := "\"" { character } * "\"" |
                     "'" { character } * "'"

Notes:

* digits in a number comprise a single token, so cannot have spaces
* any printable character can be included inside a string literal except the opening quote, unless
  prefixed by a backslash (TODO: check this assumption with Hamish - its probably not right)

### Words

A word is used to identify a name of something, e.g. a property or method name. Words must start
with an alphabetic character or underscore, with subsequent characters being alphanumeric or underscore:

    word :=  A-Za-z_ { A-Za-z0-9_ }*

### Properties and methods (variables)

Examples:
	$PropertyName.Name
	$MethodName
	$MethodName("foo").SomeProperty
	$MethodName("foo", "bar")
	$MethodName("foo", $PropertyName)

Definition:
    injection := "$" lookup | "{" "$" lookup "}"
	call := word [ "(" argument { "," argument } ")" ]
	lookup := call { "." call }*
	argument := literal | 
	            lookup |
	            "$" lookup

Notes:

* A word encountered with no parameter can be parsed as either a property or a method. TODO:
  document the exact rules for resolution.
* TODO: include Up and Top in here. Not syntactic elements, but worth mentioning their semantics.
* TODO: consider the 2.4 syntax literals. These have been excluded as we don't want to encourage their
  use.

### Operators

    <exp> == <exp>
    <exp> = <exp>
    <exp> != <exp>
    <exp> || <exp>
    <exp> && <exp>

TODO: document operator precedence, and include a descripton of what the operators do. || and && are short-circuit? Diff
between = and == or are they equivalent.

## Comments

## If

    if := ifPart elseIfPart* elsePart endIfPart
    ifPart := "<%" "if" ifCondition "%>" template
    elseIfPart := "<%" "else_if" ifCondition "%>" template
    elsePart := "<%" "else" "%>" template
    endIfPart := "<%" "end_if" "%>"

    ifCondition := TODO
    template := TODO

## Require

## Loop

## With

## Translation

## Cache block

TODO to be elaborated

    <cached arguments>...<end_cached>
    <cacheblock arguments>...<end_cacheblock>
    <uncached>...<uncached>

## Template


# Moving from SilverStripe 2.4 to SilverStripe 3
## Control
The <code><% control var %>...<% end_control %></code> in SilverStripe prior to version 3 has two overloaded meanings. Firstly, if the control variable is a collection (e.g. DataObjectSet), then <code><% control %></code> iterates over that set. If it's a non-iteratable object, however, <code><% control %></code> introduces a new scope, which is used to render the inner template code. This dual-use is confusing to some people, and doesn't allow a collection of objects to be used as a scope.
In SilverStripe 3, the first usage (iteration) is replaced by <code><% loop var %></code>. The second usage (scoping) is replaced by <code><% with var %></code>
## Literals in Expressions
Prior to SilverStripe 3, literal values can appear in certain parts of an expression. For example, in the expression <code><% if mydinner=kipper %></code>, <code>mydinner</code> is treated as a property or method on the page or controller, and <code>kipper</code> is treated as a literal. This is fairly limited in use.
Literals can now be quoted, so that both literals and non-literals can be used in contexts where only literals were allowed before. This makes it possible to write the following:

* <code><% if $mydinner=="kipper" %>...</code> which compares to the literal "kipper"
* <code><% if $mydinner==$yourdinner %>...</code> which compares to another property or method on the page called <code>yourdinner</code>

Certain forms that are currently used in SilverStripe 2.x are still supported in SilverStripe 3 for backwards compatibility:

* <code><% if mydinner==yourdinner %>...</code> is still interpreted as <code>mydinner</code> being a property or method, and <code>yourdinner</code> being a literal. It is strongly recommended to change to the new syntax in new implementations. The 2.x syntax is likely to be deprecated in the future.

Similarly, in SilverStripe 2.x, method parameters are treated as literals: <code>MyMethod(foo)</code> is now equivalent to <code>$MyMethod("foo")</code>. <code>$MyMethod($foo)</code> passes a variable to the method, which is only supported in SilverStripe 3.

## Method Parameters

Methods can now take an arbitrary number of parameters:

    $MyMethod($foo,"active", $CurrentMember.FirstName)

Parameter values can be arbitrary expressions, including a mix of literals, variables, and even other method calls.

## Less sensitivity around spaces

Within a tag, a single space is equivalent to multiple consequetive spaces. e.g.

    <% if $Foo %>

is equivalent to

    <%   if   $Foo  %>

## Lint checker

