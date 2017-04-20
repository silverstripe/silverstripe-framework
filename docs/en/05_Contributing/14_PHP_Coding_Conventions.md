# PHP Coding Conventions

This document provides guidelines for code formatting and documentation
to developers contributing to SilverStripe. It applies to all PHP files
in the `framework/` and `cms/` modules, as well as any supported additional modules.

Coding standards are an important aspect for every software project,
and facilitate collaboration by making code more consistent and readable.

If you are unsure about a specific standard, imitate existing SilverStripe code.

## PSR-2

Starting with SilverStripe 4.x, our goal is [PSR-2 Coding Standards](http://www.php-fig.org/psr/psr-2/) compliance.
Since this affects existing APIs, some details like method casing will be iterated on in the next releases.
For example, many static methods will need to be changed from lower underscore to lower camel casing. 
 
## Spelling

All symbols and documentation should use UK-English spelling (e.g. "behaviour" instead of "behavior"),
except when necessitated by third party conventions (e.g using PHP's `Serializable` interface).

## Configuration Variables

SilverStripe's [Config API]() can read its defaults from variables declared as `private static` on classes.
As opposed to other variables, these should be declared as lower case with underscores.

	:::php
	class MyClass
	{
	    private static $my_config_variable = 'foo';
	}


## Prefer identical (===) comparisons over equality (==)

Where possible, use type-strict identical comparisons instead of loosely typed equality comparisons.
Read more in the PHP documentation for [comparison operators](http://php.net/manual/en/language.operators.comparison.php) and [object comparison](http://php.net/manual/en/language.oop5.object-comparison.php).

	:::php
	// good - only need to cast to (int) if $a might not already be an int
	if ((int)$a === 100) {
	    doThis();
	}
	
	// bad
	if ($a == 100) {
	    doThis();
	}


## Separation of Logic and Presentation

Try to avoid using PHP's ability to mix HTML into the code.

	:::php
	// PHP code
	public function getTitle() {
		return "<h2>Bad Example</h2>";
	}

	// Template code
	$Title

Better: Keep HTML in template files:

	:::php
	// PHP code
	public function getTitle() {
		return "Better Example";
	}

	// Template code
	<h2>$Title</h2>

## Comments

Use [phpdoc](http://phpdoc.org/) syntax before each definition (see [tutorial](http://manual.phpdoc.org/HTMLSmartyConverter/HandS/phpDocumentor/tutorial_phpDocumentor.quickstart.pkg.html)
and [tag overview](http://manual.phpdoc.org/HTMLSmartyConverter/HandS/phpDocumentor/tutorial_tags.pkg.html)).

 * All class definitions and PHP files should have `@package` and `@subpackage`.
 * Methods should include at least `@param` and `@return`.
 * Include a blank line after the description.
 * Use `{@link MyOtherClass}` and `{@link MyOtherClass->otherMethod}` for inline references.
 * Denote preformatted code examples in `<code></code>` blocks.
 * Always start block-level comments containing phpdoc with two asterisks (`/** ... */`).

Example:

	:::php
	/**
	 * My short description for this class.
	 * My longer description with
	 * multiple lines and richer formatting.
	 *
	 * Usage:
	 * <code>
	 * $c = new MyClass();
	 * $c->myMethod();
	 * </code>
	 *
	 * @package custom
	 */
	class MyClass extends Class
	{

        /**
         * My Method.
         * This method returns something cool. {@link MyParentMethod} has other cool stuff in it.
         *
         * @param string $colour The colour of cool things that you want
         * @return DataList A list of everything cool
         */
        public function myMethod($foo)
        {
            // ...
        }

	}

## Class Member Ordering

Put code into the classes in the following order (where applicable).

 *  Static variables
 *  Member variables
 *  Static methods
 *  Data-model definition static variables.  (`$db`, `$has_one`, `$many_many`, etc)
 *  Commonly used methods like `getCMSFields()`
 *  Accessor methods (`getMyField()` and `setMyField()`)
 *  Controller action methods
 *  Template data-access methods (methods that will be called by a `$MethodName` or `<% loop $MethodName %>` construct in a template somewhere)
 *  Object methods

## SQL Format

If you have to use raw SQL, make sure your code works across databases. Make sure you escape your queries like below,
with the column or table name escaped with double quotes as below.

	:::php
	MyClass::get()->where(array("\"Score\" > ?" => 50));

It is preferable to use parameterised queries whenever necessary to provide conditions
to a SQL query, where values placeholders are each replaced with a single unquoted question mark.
If it's absolutely necessary to use literal values in a query make sure that values
are single quoted.

	:::php
	MyClass::get()->where("\"Title\" = 'my title'");

Use [ANSI SQL](http://en.wikipedia.org/wiki/SQL#Standardization) format where possible.

## Secure Development

See [security](/developer_guides/security) for conventions related to handing security permissions.

## Related

 * [JavaScript Coding Conventions](/contributing/javascript_coding_conventions)
 * [Reference: CMS Architecture](/developer_guides/customising_the_admin_interface/cms_architecture)
