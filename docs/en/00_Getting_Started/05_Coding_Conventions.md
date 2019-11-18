---
title: Coding conventions
summary: The style guidelines we follow in all of our open source code
iconBrand: php
---
 
# Coding Conventions

This document provides guidelines for code formatting and documentation
to developers contributing to SilverStripe. It applies to all PHP files
in the framework/ and cms/ modules, as well as any supported additional modules.

Coding standards are an important aspect for every software project,
and facilitate collaboration by making code more consistent and readable.

If you are unsure about a specific standard, imitate existing SilverStripe code.

## File Formatting

### Indentation

Always use hard tabs rather then spaces for indentation, with one tab per nesting level.

### Maximum Line Length

The target line length is 100 columns with tabs being treated as four columns,
meaning developers should strive keep each line of their code 
under 80 columns where possible and practical. 
However, longer lines are acceptable in some circumstances. 
The maximum length of any line of PHP code is 120 columns.

### Line Termination

Line termination follows the Unix text file convention. Lines must end with a single linefeed (LF) character. 
Linefeed characters are represented as ordinal 10, or hexadecimal 0x0A.
Note: Do not use carriage returns (CR) as is the convention in Apple OS's (0x0D) or the carriage return - 
linefeed combination (CRLF) as is standard for the Windows OS (0x0D, 0x0A).

## Naming Conventions

Class, function, variable and constant names may only contain alphanumeric characters and underscores.

### Classes

Class and filenames are in `UpperCamelCase` format:

```php
	class MyClass {}

```
new word must be capitalized. Successive capitalized letters are used in 
acronyms, e.g. a class `XMLImporter` is used while `XmlImporter` is not.

### Methods

Static methods should be in `lowercase_with_underscores()` format:

```php
	public static function my_static_method() {}

```
This is because they go into the controller URL in the same format (eg, `home/successfullyinstalled`).
Method names are allowed to contain underscores here, in order to allow URL parts with dashes
(`mypage\my-action` gets translated to `my_action()` automatically).

```php
	public function mycontrolleraction() {}

```
Alternatively, `$this->getUpperCamelCase()` will work the same way in templates -
you can access both coding styles as `$UpperCamelCase`.

Other instance methods should be in `$this->lowerCamelCase()` format:

```php
	public function myInstanceMethod() {}

```

### Variables

Static variables should be `self::$lowercase_with_underscores`

```php
	self::$my_static_variable = 'foo';

```

```php
	$this->myMemberVariable = 'foo';

```

### Constants

All letters used in a constant name must be capitalized, 
while all words in a constant name must be separated by underscore characters.

```php
	const INTEREST_RATE = 0.19;
	
	define('INTEREST_RATE', 0.19);

```
Defining constants in the global scope with the `define` function is permitted but strongly discouraged.

### File Naming and Directory Structure

Classes need to be in a file of the same name. Multiple classes are allowed to be contained in one file,
as long as the prefix of the class equals the filename, and is separated by an underscore from the remaining name.
For example `MyClass` and `MyClass_Controller` will both need to be placed into `MyClass.php`.

Example: `mysite/code/MyClass.php`

```php
	<?php
	
	class MyClass {}
	
	class MyClass_Controller {}
	
	class MyClass_OtherRelatedClass {}

```

See [directory structure](directory_structure) for more information.

## Coding Style

### PHP Code Declaration

PHP code must always be delimited by the full-form, standard PHP tags:

```php
	<?php

```
Short tags are never allowed. For files containing only PHP code, the closing tag must always be omitted.
It is not required by PHP, and omitting it prevents the accidental injection of trailing white space into the response.

Files must end with an empty new line. This prevents problems arising from the end-of-file marker appearing where other
white space is expected.

### Strings

#### String Literals

When a string is literal (contains no variable substitutions), the apostrophe or "single quote" should always be used to demarcate the string:

```php
	$a = 'Example String';

```

When a literal string itself contains apostrophes, it is permitted to demarcate the string with quotation marks or "double quotes". 

```php
	$greeting = "They said 'hello'";

```

#### String Substitution

Variable substitution is permitted using either of these forms:

```php
	$greeting = "Hello $name, welcome back!";
	$greeting = "Hello {$name}, welcome back!";

```

```php
	$greeting = "Hello ${name}, welcome back!";

```

Strings must be concatenated using the "." operator. A space must always be added before and after the "." operator to improve readability:

```php
	$copyright = 'SilverStripe Ltd (' . $year . ')';

```
In these cases, each successive line should be padded with white space such that the "."; operator is aligned under the "=" operator:

```php
	$sql = 'SELECT "ID", "Name" FROM "Person" '
	     . 'WHERE "Name" = \'Susan\' '
	     . 'ORDER BY "Name" ASC ';

```

#### Numerically Indexed Arrays

Negative numbers are not permitted as indices.

An indexed array may start with any non-negative number, however all base indices besides 0 are discouraged.
When declaring indexed arrays with the Array function, a trailing space must be added after each comma delimiter to improve readability:

```php
	$sampleArray = array(1, 2, 3, 'Zend', 'Studio');

```
In this case, each successive line must be padded with spaces such that beginning of each line is aligned:

```php
	$sampleArray = array(1, 2, 3, 'Zend', 'Studio',
	                     $a, $b, $c,
	                     56.44, $d, 500);

```
If so, it should be padded at one indentation level greater than the line containing the array declaration, 
and all successive lines should have the same indentation; 
the closing paren should be on a line by itself at the same indentation level as the line containing the array declaration:

```php
	$sampleArray = array(
		1, 2, 3, 'Zend', 'Studio',
		$a, $b, $c,
		56.44, $d, 500,
	);

```
this minimizes the impact of adding new items on successive lines, and helps to ensure no parse errors occur due to a missing comma.

#### Associative Arrays

When declaring associative arrays with the `array` construct, breaking the statement into multiple lines is encouraged. 
In this case, each successive line must be padded with white space such that both the keys and the values are aligned:

```php
	$sampleArray = array('firstKey'  => 'firstValue',
	                     'secondKey' => 'secondValue');

```
If so, it should be padded at one indentation level greater than the line containing the array declaration, 
and all successive lines should have the same indentation; the closing paren should be on a line by itself at the 
same indentation level as the line containing the array declaration. 
For readability, the various "=>" assignment operators should be padded such that they align.

```php
	$sampleArray = array(
		'firstKey'  => 'firstValue',
		'secondKey' => 'secondValue',
	);

```

No method or function invocation is allowed to have spaces directly
before or after the opening parathesis, as well as no space before the closing parenthesis.

```php
	public function foo($arg1, $arg2) {} // good
	public function foo ( $arg1, $arg2 ) {} // bad

```

```php
	// good
	public function foo() {
		// ...
	}

	// bad
	public function bar() 
	{
		// ...
	}

```
Additional arguments to the function or method must be indented one additional level beyond the function or method declaration. 
A line break should then occur before the closing argument paren, 
which should then be placed on the same line as the opening brace of the function 
or method with one space separating the two, and at the same indentation level as the function or method declaration. 

```php
	public function bar($arg1, $arg2, $arg3,
		$arg4, $arg5, $arg6
	) {
		// indented code
	}

```
apart from the last argument.

### Control Structures

#### if/else/elseif

No control structure is allowed to have spaces directly
before or after the opening parenthesis, as well as no space before the closing parenthesis.

The opening brace and closing brace are written on the same line as the conditional statement. 
Any content within the braces must be indented using a tab.

```php
	if($a != 2) {
	    $a = 2;
	}

```
you may break the conditional into multiple lines. In such a case, break the line prior to a logic operator, 
and pad the line such that it aligns under the first character of the conditional clause. 
The closing paren in the conditional will then be placed on a line with the opening brace, 
with one space separating the two, at an indentation level equivalent to the opening control statement.

```php
	if(($a == $b)
	    && ($b == $c)
	    || (Foo::CONST == $d)
	) {
	    $a = $d;
	}

```
from the conditional during later revisions. For `if` statements that include `elseif` or `else`, 
the formatting conventions are similar to the `if` construct. 
The following examples demonstrate proper formatting for `if` statements with `else` and/or `elseif` constructs:

```php
	if($a != 2) {
	    $a = 2;
	} elseif($a == 3) {
	    $a = 4;
	} else {
	    $a = 7;
	}

```

```php
	// good
	if($a == $b) doThis();
	
	// bad
	if($a == $b) doThis();
	else doThat();

```

All content within the "switch" statement must be indented using tabs. 
Content under each "case" statement must be indented using an additional tab.

```php
	switch($numPeople) {
		case 1:
			break;
		case 2:
			break;
		default:
			break;
	}

```

#### for/foreach/while

Loop constructs follow the same principles as "Control Structures: if/else/elseif".

### Separation of Logic and Presentation

Try to avoid using PHP's ability to mix HTML into the code.

```php
	// PHP code
	public function getTitle() {
		return "<h2>Bad Example</h2>"; 
	}
	
	// Template code
	$Title

```

```php
	// PHP code
	public function getTitle() {
		return "Better Example";
	}
	
	// Template code
	<h2>$Title</h2>

```

Use [phpdoc](http://phpdoc.org/) syntax before each definition (see [tutorial](http://manual.phpdoc.org/HTMLSmartyConverter/HandS/phpDocumentor/tutorial_phpDocumentor.quickstart.pkg.html)
and [tag overview](http://manual.phpdoc.org/HTMLSmartyConverter/HandS/phpDocumentor/tutorial_tags.pkg.html)).

 * All class definitions and PHP files should have `@package` and `@subpackage`.
 * Methods should include at least `@param` and `@return`.
 * Include a blank line after the description. 
 * Use `{@link MyOtherClass}` and `{@link MyOtherClass->otherMethod}` for inline references.
 * Denote preformatted code examples in `<code></code>` blocks.
 * Always start block-level comments containing phpdoc with two asterisks (`/** ... */`).

Example:

```php
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
	class MyClass extends Class {
	
		/**
		 * My Method.
		 * This method returns something cool. {@link MyParentMethod} has other cool stuff in it.
		 * 
		 * @param string $colour The colour of cool things that you want
		 * @return DataList A list of everything cool
		 */
		public function myMethod($foo) {}
		
	}
	
```
### Class Member Ordering

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

### SQL Format

If you have to use raw SQL, make sure your code works across databases. Make sure you escape your queries like below, 
with the column or table name escaped with double quotes as below.

```php
	MyClass::get()->where(array("\"Score\" > ?" => 50));

```
to a SQL query, where values placeholders are each replaced with a single unquoted question mark.
If it's absolutely necessary to use literal values in a query make sure that values
are single quoted.

```php
	MyClass::get()->where("\"Title\" = 'my title'");

```

### Secure Development 

See [security](/developer_guides/security) for conventions related to handing security permissions.

## License

Parts of these coding conventions were adapted from [Zend Framework](http://framework.zend.com/manual/en/coding-standard.overview.html),
which are licensed under BSD (see [license](http://framework.zend.com/license)).

## Related

 * [Reference: CMS Architecture](/developer_guides/customising_the_admin_interface/cms_architecture)
