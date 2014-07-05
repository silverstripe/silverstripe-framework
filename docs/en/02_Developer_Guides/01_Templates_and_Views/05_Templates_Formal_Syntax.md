# Formal Treatment of the Template Language

This document gives you the maximum level of detail of how the template engine works.  If you just want to know how to write templates, you probably want to read [this page](/reference/templates) instead.

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

 * TODO: document operator precedence, and include a descripton of what the operators do. || and && are short-circuit? Diff
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
