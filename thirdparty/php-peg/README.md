# PHP PEG - A PEG compiler for parsing text in PHP

This is a Paring Expression Grammar compiler for PHP. PEG parsers are an alternative to other CFG grammars that includes both tokenization 
and lexing in a single top down grammar. For a basic overview of the subject, see http://en.wikipedia.org/wiki/Parsing_expression_grammar

## Quick start

- Write a parser. A parser is a PHP class with a grammar contained within it in a special syntax. The filetype is .peg.inc. See the examples directory.
- Compile the parser. php ./cli.php ExampleParser.peg.inc > ExampleParser.php 
- Use the parser (you can also include code to do this in the input parser - again see the examples directory):

<pre><code>
	$x = new ExampleParser( 'string to parse' ) ;
	$res = $x->match_Expr() ;
</code></pre>

### Parser Format

Parsers are contained within a PHP file, in one or more special comment blocks that start with `/*!* [name | !pragma]` (like a docblock, but with an
exclamation mark in the middle of the stars)

You can have multiple comment blocks, all of which are treated as contiguous for the purpose of compiling. During compilation these blocks will be replaced 
with a set of "matching" functions (functions which match a string against their rules) for each rule in the block.

The optional name marks the start of a new set of parser rules. This is currently unused, but might be used in future for opimization & debugging purposes.
If unspecified, it defaults to the same name as the previous parser comment block, or 'Anonymous Parser' if no name has ever been set.

If the name starts with an '!' symbol, that comment block is a pragma, and is treated not as some part of the parser, but as a special block of meta-data

Lexically, these blocks are a set of rules & comments. A rule can be a base rule or an extension rule

##### Base rules

Base rules consist of a name for the rule, some optional arguments, the matching rule itself, and an optional set of attached functions

NAME ( "(" ARGUMENT, ... ")" )? ":" MATCHING_RULE
  ATTACHED_FUNCTIONS?
 
Names must be the characters a-z, A-Z, 0-9 and _ only, and must not start with a number 
 
Base rules can be split over multiple lines as long as subsequent lines are indented

##### Extension rules

Extension rules are either the same as a base rule but with an addition name of the rule to extend, or as a replacing extension consist of 
a name for the rule, the name of the rule to extend, and optionally: some arguments, some replacements, and a set of attached functions

NAME extend BASENAME ( "(" ARGUMENT, ... ")" )? ":" MATCHING_RULE
  ATTACHED_FUNCTIONS?

NAME extends BASENAME ( "(" ARGUMENT, ... ")" )? ( ";" REPLACE "=>" REPLACE_WITH, ... )?
  ATTACHED_FUNCTIONS?

##### Tricks and traps

We allow indenting a parser block, but only in a consistant manner - whatever the indent of the /*** marker becomes the "base" indent, and needs to be used 
for all lines. You can mix tabs and spaces, but the indent must always be an exact match - if the "base" indent is a tab then two spaces, every line within the
block also needs indenting with a tab then two spaces, not two tabs (even if in your editor, that gives the same indent).

Any line with more than the "base" indent is considered a continuation of the previous rule

Any line with less than the "base" indent is an error

This might get looser if I get around to re-writing the internal "parser parser" in php-peg, bootstrapping the whole thing

### Rules

PEG matching rules try to follow standard PEG format, summarised thusly:

<pre><code>
	token* - Token is optionally repeated
	token+ - Token is repeated at least one
	token? - Token is optionally present

	tokena tokenb - Token tokenb follows tokena, both of which are present
	tokena | tokenb - One of tokena or tokenb are present, prefering tokena

	&token - Token is present next (but not consumed by parse)
	!token - Token is not present next (but not consumed by parse)

 	( expression ) - Grouping for priority
</code></pre>

But with these extensions:

<pre><code>
	< or > - Optionally match whitespace
	[ or ] - Require some whitespace
</code></pre>

### Tokens

Tokens may be

 - bare-words, which are recursive matchers - references to token rules defined elsewhere in the grammar,
 - literals, surrounded by `"` or `'` quote pairs. No escaping support is provided in literals.
 - regexs, surrounded by `/` pairs.
 - expressions - single words (match \w+) starting with `$` or more complex surrounded by `${ }` which call a user defined function to perform the match

##### Regular expression tokens

Automatically anchored to the current string start - do not include a string start anchor (`^`) anywhere. Always acts as when the 'x' flag is enabled in PHP - 
whitespace is ignored unless escaped, and '#' stats a comment.

Be careful when ending a regular expression token - the '*/' pattern (as in /foo\s*/) will end a PHP comment. Since the 'x' flag is always active,
just split with a space (as in / foo \s* /)

### Expressions

Expressions allow run-time calculated matching. You can embed an expression within a literal or regex token to
match against a calculated value, or simply specify the expression as a token to match against a dynamic rule.

#### Expression stack

When getting a value to use for an expression, the parser will travel up the stack looking for a set value. The expression
stack is a list of all the rules passed through to get to this point. For example, given the parser

<pre><code>
	A: $a
	B: A
	C: B
</code></pre>
	
The expression stack for finding $a will be C, B, A - in other words, the A rule will be checked first, followed by B, followed by C

#### In terminals (literals and regexes)

The token will be replaced by the looked up value. To find the value for the token, the expression stack will be
travelled up checking for one of the following:

  - A key / value pair in the result array node
  - A rule-attached method INCLUDING `$` ( i.e. `function $foo()` )
  
If no value is found it will then check if a method or a property excluding the $ exists on the parser. If neither of those is found
the expression will be replaced with an exmpty string/

#### As tokens

The token will be looked up to find a value, which must be the name of a matching rule. That rule will then be matched 
against as if the token was a recurse token for that rule.

To find the name of the rule to match against, the expression stack will be travelled up checking for one of the following:

  - A key / value pair in the result array node
  - A rule-attached method INCLUDING `$` ( i.e. `function $foo()` )
  
If no value is found it will then check if a method or a property excluding the $ exists on the parser. If neither of those if found
the rule will fail to match.

#### Tricks and traps

Be careful against using a token expression when you meant to use a terminal expression

<pre><code>
	quoted_good: q:/['"]/ string "$q"
	quoted_bad:  q:/['"]/ string $q
</code></pre>

`"$q"` matches against the value of q again. `$q` tries to match against a rule named `"` or `'` (both of which are illegal rule
names, and will therefore fail)

### Named matching rules

Tokens and groups can be given names by prepending name and `:`, e.g.,

<pre><code>
	rulea: "'" name:( tokena tokenb )* "'"
</code></pre>

There must be no space betweeen the name and the `:`

<pre><code>
	badrule: "'" name : ( tokena tokenb )* "'"
</code></pre>

Recursive matchers can be given a name the same as their rule name by prepending with just a `:`. These next two rules are equivilent

<pre><code>
	rulea: tokena tokenb:tokenb
	rulea: tokena :tokenb
</code></pre>

### Rule-attached functions

Each rule can have a set of functions attached to it. These functions can be defined

- in-grammar by indenting the function body after the rule
- in-class after close of grammar comment by defining a regular method who's name is `{$rulename}_{$functionname}`, or `{$rulename}{$functionname}` if function name starts with `_`
- in a sub class

All functions that are not in-grammar must have PHP compatible names  (see PHP name mapping). In-grammar functions will have their names converted if needed.

All these definitions define the same rule-attached function

<pre><code>
	class A extends Parser {
		/*!* Parser
		foo: bar baz
			function bar() {}
		*/

		function foo_bar() {}
	}

	class B extends A {
		function foo_bar() {}
	}
</code></pre>

### PHP name mapping

Rules in the grammar map to php functions named `match_{$rulename}`. However rule names can contain characters that php functions can't.
These characters are remapped:

<pre><code>
	'-' => '_'
	'$' => 'DLR'
	'*' => 'STR'
</code></pre>

Other dis-allowed characters are removed.

## Results

Results are a tree of nested arrays.

Without any specific control, each rules result will just be the text it matched against in a `['text']` member. This member must always exist.

Marking a subexpression, literal, regex or recursive match with a name (see Named matching rules) will insert a member into the
result array named that name. If there is only one match it will be a single result array. If there is more than one match it will be an array of arrays.

You can override result storing by specifying a rule-attached function with the given name. It will be called with a reference to the current result array
and the sub-match - in this case the default storage action will not occur.

If you specify a rule-attached function for a recursive match, you do not need to name that token at all - it will be call automatically. E.g.

<pre><code>
	rulea: tokena tokenb
	  function tokenb ( &$res, $sub ) { print 'Will be called, even though tokenb is not named or marked with a :' ; }
</code></pre>

You can also specify a rule-attached function called `*`, which will be called with every recursive match made

<pre><code>
	rulea: tokena tokenb
	  function * ( &$res, $sub ) { print 'Will be called for both tokena and tokenb' ; }
</code></pre>

### Silent matches

By default all matches are added to the 'text' property of a result. By prepending a member with `.` that match will not be added to the ['text'] member. This
doesn't affect the other result properties that named rules' add.

### Inheritance

Rules can inherit off other rules using the keyword extends. There are several ways to change the matching of the rule, but
they all share a common feature - when building a result set the rule will also check the inherited-from rule's rule-attached 
functions for storage handlers. This lets you do something like

<pre><code>
A: Foo Bar Baz
  function *(){ /* Generic store handler */ }
  
B extends A
  function Bar(){ /* Custom handling for Bar - Foo and Baz will still fall through to the A#* function defined above */ }
</code></pre>

The actual matching rule can be specified in three ways:

#### Duplication

If you don't specify a new rule or a replacement set the matching rule is copied as is. This is useful when you want to
override some storage logic but not the rule itself

#### Text replacement

You can replace some parts of the inherited rule using test replacement by using a ';' instead of an ':' after the name
 of the extended rule. You can then put replacements in a comma seperated list. An example might help

<pre><code>
A: Foo | Bar | Baz

# Makes B the equivalent of Foo | Bar | (Baz | Qux)
B extends A: Baz => (Baz | Qux)
</code></pre>

Note that the replacements are not quoted. The exception is when you want to replace with the empty string, e.g.

<pre><code>
A: Foo | Bar | Baz

# Makes B the equivalent of Foo | Bar
B extends A: | Baz => ""
</code></pre>

Currently there is no escaping supported - if you want to replace "," or "=>" characters you'll have to use full replacement

#### Full replacement

You can specify an entirely new rule in the same format as a non-inheriting rule, eg.

<pre><code>
A: Foo | Bar | Baz

B extends A: Foo | Bar | (Baz Qux)
</code></pre>

This is useful is the rule changes too much for text replacement to be readable, but want to keep the storage logic

### Pragmas

When opening a parser comment block, if instead of a name (or no name) you put a word starting with '!', that comment block is treated as a pragma - not
part of the parser language itself, but some other instruction to the compiler. These pragmas are currently understood:

  !silent

    This is a comment that should only appear in the source code. Don't output it in the generated code

  !insert_autogen_warning

    Insert a warning comment into the generated code at this point, warning that the file is autogenerated and not to edit it

## TODO

- Allow configuration of whitespace - specify what matches, and wether it should be injected into results as-is, collapsed, or not at all
- Allow inline-ing of rules into other rules for speed
- More optimisation
- Make Parser-parser be self-generated, instead of a bad hand rolled parser like it is now.
- PHP token parser, and other token streams, instead of strings only like now
