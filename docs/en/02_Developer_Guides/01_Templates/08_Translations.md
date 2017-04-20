title: Translations
summary: Definition of the syntax for writing i18n compatible templates.

# Translations

Translations are easy to use with a template, and give access to SilverStripe's translation facilities. Here is an 
example:

	<%t Foo.BAR 'Bar' %>

    <%t Member.WELCOME 'Welcome {name} to {site}' name=$Member.Name site="Foobar.com" %>

`Member.WELCOME` is an identifier in the translation system, for which different translations may be available. This 
string may include named placeholders, in braces.

`'Welcome {name} to {site}'` is the default string used, if there is no translation for Member.WELCOME in the current 
locale. This contains named placeholders.

`name=$Member.Name` assigns a value to the named placeholder `name`. This value is substituted into the translation 
string wherever `{name}` appears in that string. In this case, it is assigning a value from a property `Member.Name`

`site="Foobar.com"` assigns a literal value to another named placeholder, `site`.

## Related

* [i18n](../i18n)

## API Documentation

* [api:i18n]