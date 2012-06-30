# Moving from SilverStripe 2 to SilverStripe 3

These are the main changes to the SiverStripe 3 template language.

## Control blocks: Loops vs. Scope

The `<% control var %>...<% end_control %>` in SilverStripe prior to version 3 has two different meanings. Firstly, if the control variable is a collection (e.g. DataList), then `<% control %>` iterates over that set. If it's a non-iteratable object, however, `<% control %>` introduces a new scope, which is used to render the inner template code. This dual-use is confusing to some people, and doesn't allow a collection of objects to be used as a scope.

In SilverStripe 3, the first usage (iteration) is replaced by `<% loop var %>`. The second usage (scoping) is replaced by `<% with var %>`

## Literals in Expressions

Prior to SilverStripe 3, literal values can appear in certain parts of an expression. For example, in the expression `<% if mydinner=kipper %>`, `mydinner` is treated as a property or method on the page or controller, and `kipper` is treated as a literal. This is fairly limited in use.

Literals can now be quoted, so that both literals and non-literals can be used in contexts where only literals were allowed before. This makes it possible to write the following:

 * `<% if $mydinner=="kipper" %>...` which compares to the literal "kipper"
 * `<% if $mydinner==$yourdinner %>...` which compares to another property or method on the page called `yourdinner`

Certain forms that are currently used in SilverStripe 2.x are still supported in SilverStripe 3 for backwards compatibility:

 * `<% if mydinner==yourdinner %>...` is still interpreted as `mydinner` being a property or method, and `yourdinner` being a literal. It is strongly recommended to change to the new syntax in new implementations. The 2.x syntax is likely to be deprecated in the future.

Similarly, in SilverStripe 2.x, method parameters are treated as literals: `MyMethod(foo)` is now equivalent to `$MyMethod("foo")`. `$MyMethod($foo)` passes a variable to the method, which is only supported in SilverStripe 3.

## Method Parameters

Methods can now take an arbitrary number of parameters:

    $MyMethod($foo,"active", $CurrentMember.FirstName)

Parameter values can be arbitrary expressions, including a mix of literals, variables, and even other method calls.

## Less sensitivity around spaces

Within a tag, a single space is equivalent to multiple consequetive spaces. e.g.

    <% if $Foo %>

is equivalent to

    <%   if   $Foo  %>


## Removed view-specific accessors

Several methods in ViewableData that were originally added to expose values to the template language were moved,
in order to stop polluting the namespace. These were sometimes called by project-specific PHP code too, and that code
will need re-working.

#### Globals

Some of these methods were wrappers which simply called other static methods. These can simply be replaced with a call
to the wrapped method. The list of these methods is:

 - CurrentMember() -> Member::currentUser()
 - getSecurityID() -> SecurityToken::inst()->getValue()
 - HasPerm($code) -> Permission::check($code)
 - BaseHref() -> Director::absoluteBaseURL()
 - AbsoluteBaseURL() -> Director::absoluteBaseURL()
 - IsAjax() -> Director::is_ajax()
 - i18nLocale() -> i18n::get_locale()
 - CurrentPage() -> Controller::curr()

#### Scope-exposing

Some of the removed methods exposed access to the various scopes. These currently have no replacement. The list of
these methods is:

 - Top
