title: i18n
summary: Display templates and PHP code in different languages based on the preferences of your website users.

# i18n

The i18n class (short for "internationalization") in SilverStripe enables you to display templates and PHP code in
different languages based on your global settings and the preferences of your website users. This process is also known
as l10n (short for "localization").

For translating any content managed through the CMS or stored in the database, we recommend using the 
[Fluent](https://github.com/tractorcow/silverstripe-fluent) module.

This page aims to describe the low-level functionality of the i18n API. It targets developers who:

*  Are involved in creating templates in different languages.
*  Want to build their own modules with i18n capabilities.
*  Want to make their PHP-code (e.g. form labels) i18n-ready

## Usage

### Enabling i18n

The i18n class is enabled by default.

### Setting the locale

To set the locale you just need to call [i18n::set_locale()](api:SilverStripe\i18n\i18n::set_locale()) passing, as a parameter, the name of the locale that 
you want to set.


```php
use SilverStripe\i18n\i18n;

// app/_config.php
i18n::set_locale('de_DE'); // Setting the locale to German (Germany)
i18n::set_locale('ca_AD'); // Setting to Catalan (Andorra)
```

Once we set a locale, all the calls to the translator function will return strings according to the set locale value, if
these translations are available. See [unicode.org](http://unicode.org/cldr/data/diff/supplemental/languages_and_territories.html) 
for a complete listing of available locales.

The `i18n` logic doesn't set the PHP locale via [setlocale()](http://php.net/setlocale).
Localisation methods in SilverStripe rely on explicit locale settings as documented below.
If you rely on PHP's built-in localisation such as [strftime()](http://php.net/strftime),
please only change locale information selectively. Setting `LC_ALL` or `LC_NUMERIC` will cause issues with SilverStripe
operations such as decimal separators in database queries.

### Getting the locale

As you set the locale you can also get the current value, just by calling [i18n::get_locale()](api:SilverStripe\i18n\i18n::get_locale()).

### Declaring the content language in HTML		{#declaring_the_content_language_in_html}

To let browsers know which language they're displaying a document in, you can declare a language in your template.


```html
//'Page.ss' (HTML)
<html lang="$ContentLocale">

//'Page.ss' (XHTML)
<html lang="$ContentLocale" xml:lang="$ContentLocale" xmlns="http://www.w3.org/1999/xhtml">
```

Setting the `<html>` attribute is the most commonly used technique. There are other ways to specify content languages
(meta tags, HTTP headers), explained in this [w3.org article](http://www.w3.org/International/tutorials/language-decl/).

You can also set the [script direction](http://www.w3.org/International/questions/qa-scripts),
which is determined by the current locale, in order to indicate the preferred flow of characters
and default alignment of paragraphs and tables to browsers.


```html
<html lang="$ContentLocale" dir="$i18nScriptDirection">
```

### Date and time formats

Formats can be set globally in the i18n class. 
You can use these settings for your own view logic.


```php
use SilverStripe\Core\Config\Config;
use SilverStripe\i18n\i18n;

i18n::config()
    ->set('date_format', 'dd.MM.yyyy')
    ->set('time_format', 'HH:mm');
```

Localization in SilverStripe uses PHP's [intl extension](http://php.net/intl).
Formats for it's [IntlDateFormatter](http://php.net/manual/en/class.intldateformatter.php)
are defined in [ICU format](http://www.icu-project.org/apiref/icu4c/classSimpleDateFormat.html#details),
not PHP's built-in [date()](http://nz.php.net/manual/en/function.date.php).

These settings are not used for CMS presentation.
Users can choose their own locale, which determines the date format
that gets presented to them. Currently this is a mix of PHP defaults (for readonly `DateField` and `TimeField`),
browser defaults (for `DateField` on browsers supporting HTML5), and [Moment.JS](http://momentjs.com/)
client-side logic (for `DateField` polyfills and other readonly dates and times).

### Language Names

SilverStripe comes with a built-in list of common languages, listed by locale and region.
They can be accessed via the `i18n.common_languages` and `i18n.common_locales` [config setting](/developer_guides/configuration).

In order to add a value, add the following to your `config.yml`:


```yml
SilverStripe\i18n\i18n:
  common_locales:
    de_CGN:
      name: German (Cologne)
      native: Kölsch
```

Similarly, to change an existing language label, you can overwrite one of these keys:


```yml
SilverStripe\i18n\i18n:
  common_locales:
    en_NZ:
      native: Niu Zillund
```

### i18n in URLs

By default, URLs for pages in SilverStripe (the `SiteTree->URLSegment` property)
are automatically reduced to the allowed allowed subset of ASCII characters.
If characters outside this subset are added, they are either removed or (if possible) "transliterated".
This describes the process of converting from one character set to another
while keeping characters recognizeable. For example, vowels with french accents
are replaced with their base characters, `pâté` becomes `pate`.

It is advisable to set the `SS_Transliterator.use_iconv` setting to true via config for systems
which have `iconv` extension enabled and configured.
See [the php documentation on iconv](http://php.net/manual/en/book.iconv.php) for more information.

In order to allow for so called "multibyte" characters outside of the ASCII subset,
limit the character filtering in the underlying configuration setting,
by setting `URLSegmentFilter.default_use_transliterator` to `false` in your YAML configuration.

Please refer to [W3C: Introduction to IDN and IRI](http://www.w3.org/International/articles/idn-and-iri/) for more details.

### i18n in Form Fields

Date and time related form fields are automatically localised ([DateField](api:SilverStripe\Forms\DateField), [TimeField](api:SilverStripe\Forms\TimeField), [DatetimeField](api:SilverStripe\Forms\DatetimeField)).
Since they use HTML5 `type=date` and `type=time` fields by default, these fields will present dates
in a localised format chosen by the browser and operating system.

Fields can be forced to use a certain locale and date/time format by calling `setHTML5(false)`,
followed by `setLocale()` or `setDateFormat()`/`setTimeFormat()`.


```php
use SilverStripe\Forms\DateField;

$field = new DateField();
$field->setLocale('de_AT'); // set Austrian/German locale, defaulting format to dd.MM.y
$field->setDateFormat('d.M.y'); // set a more specific date format (single digit day/month) 
```

## Translating text

Adapting a module to make it localizable is easy with SilverStripe. You just need to avoid hardcoding strings that are
language-dependent and use a translator function call instead.


```php
// without i18n
echo "This is a string";
// with i18n
echo _t("Namespace.Entity","This is a string");
```

All strings passed through the `_t()` function will be collected in a separate language table (see [Collecting text](#collecting-text)), which is the starting point for translations.

### The _t() function

The `_t()` function is the main gateway to localized text, and takes four parameters, all but the first being optional.
It can be used to translate strings in both PHP files and template files. The usage for each case is described below.

* **$entity:** Unique identifier, composed by a namespace and an entity name, with a dot
  separating them. Both are arbitrary names, although by convention we use the name of
  the containing class or template. Use this identifier to reference the same translation
  elsewhere in your code.
* **$default:** The original language string to be translated. This should be declared
  whenever used, and will get picked up the [text collector](#collecting-text).
* **$string:** (optional) Natural language comment (particularly short phrases and individual words)
  are very context dependent. This parameter allows the developer to convey this information
  to the translator.
* **$injection::** (optional) An array of injecting variables into the second parameter


## Pluralisation

i18n also supports locale-respective pluralisation rules. Many languages have more than two plural forms,
unlike English which has two only; One for the singular, and another for any other number.

More information on what forms these plurals can take for various locales can be found on the
[CLDR documentation](http://www.unicode.org/cldr/charts/latest/supplemental/language_plural_rules.html)

The ability to pluralise strings is provided through the `i18n::_t` method when supplied with a
`{count}` argument and `|` pipe-delimiter provided with the default string.

Plural forms can also be explicitly declared via the i18nEntityProvider interface in array-format
with both a 'one' and 'other' key (as per the CLDR for the default `en` language).

For instance, this is an example of how to correctly declare pluralisations for an object



```php
use SilverStripe\ORM\DataObject;

class MyObject extends DataObject implements i18nEntityProvider
{
    public function provideI18nEntities()
    {
        return [
            'MyObject.SINGULARNAME' => 'object',
            'MyObject.PLURALNAME' => 'objects',
            'MyObject.PLURALS' => [
                'one' => 'An object',
                'other' => '{count} objects',
            ],
        ];
    }
}
```

In YML format this will be expressed as the below. This follows the
[ruby i18n convention](guides.rubyonrails.org/i18n.html#pluralization) for plural forms.

```yaml
en:
  MyObject:
    SINGULARNAME: 'object'
    PLURALNAME: 'objects'
    PLURALS:
      one: 'An object',
      other: '{count} objects'
```

Note: i18nTextCollector support for pluralisation is not yet available.
Please ensure that any required plurals are exposed via provideI18nEntities.

#### Usage in PHP Files


```php
// Simple string translation
_t('LeftAndMain.FILESIMAGES','Files & Images');

// Using injection to add variables into the translated strings.
_t('CMSMain.RESTORED',
    "Restored {value} successfully",
    ['value' => $itemRestored]
);

// Plurals are invoked via a `|` pipe-delimeter with a {count} argument
_t('MyObject.PLURALS', 'An object|{count} objects', [ 'count' => $count ]);
```

#### Usage in Template Files

<div class="hint" markdown='1'>
The preferred template syntax has changed somewhat since [version 2.x](http://doc.silverstripe.org/framework/en/2.4/topics/i18n#usage-2).
</div>

In `.ss` template files, instead of `_t(params)` the syntax `<%t params %>` is used. The syntax for passing parameters to the function is quite different to
the PHP version of the function.

 * Parameters are space separated, not comma separated
 * The original language string and the natural language comment parameters are separated by ` on `.
 * The final parameter (which is an array in PHP) is passed as a space separated list of key/value pairs.


```ss
// Simple string translation
<%t Namespace.Entity "String to translate" %>

// Using injection to add variables into the translated strings (note that $Name and $Greeting must be available in the current template scope).
<%t Header.Greeting "Hello {name} {greeting}" name=$Name greeting=$Greeting %>

// Plurals follow the same convention, required a `|` and `{count}` in the default string
<%t MyObject.PLURALS 'An item|{count} items' count=$Count %>
```

#### Caching in Template Files with locale switching

When caching a `<% loop %>` or `<% with %>` with `<%t params %>`. It is important to add the Locale to the cache key 
otherwise it won't pick up locale changes.

```ss
<% cached 'MyIdentifier', $CurrentLocale %>
    <% loop $Students %>
        $Name
    <% end_loop %>
<% end_cached %>
```

## Collecting text

To collect all the text in code and template files we have just to visit: `http://localhost/dev/tasks/i18nTextCollectorTask`

Text collector will then read the files, build the master string table for each module where it finds calls to the
underscore function, and tell you about the created files and any possible entity redeclaration.

If you want to run the text collector for just one module you can use the 'module' parameter: 
`http://localhost/dev/tasks/i18nTextCollectorTask/?module=cms`

<div class="hint" markdown='1'>
You'll need to install PHPUnit to run the text collector (see [testing-guide](/developer_guides/testing)).
</div>

## Module Priority

The order in which i18n strings are loaded from modules can be quite important, as it is pretty common for a site
developer to want to override the default i18n strings from time to time.  Because of this, you will sometimes need to specify the loading priority of i18n modules.

By default, the language files are loaded from modules in this order:

 * Your project (as defined in the `$project` global)
 * admin
 * framework
 * All other modules

This default order is configured in `framework/_config/i18n.yml`.  This file specifies two blocks of module ordering: `basei18n`, listing admin, and framework, and `defaulti18n` listing all other modules.

To create a custom module order, you need to specify a config fragment that inserts itself either after or before those items.  For example, you may have a number of modules that have to come after the framework/admin, but before anyhting else.  To do that, you would use this

```yml
---
Name: customi18n
Before: '#defaulti18n'
---
SilverStripe\i18n\i18n:
  module_priority:
    - module1
    - module2
    - module3
```
The config option being set is `i18n.module_priority`, and it is a list of module names.

There are a few special cases:

 * If not explicitly mentioned, your project is put as the first module.
 * The module name `other_modules` can be used as a placeholder for all modules that aren't
   specifically mentioned.

## Language definitions

Each module can have one language table per locale, stored by convention in the `lang/` subfolder.
The translation is powered by [Zend_Translate](http://framework.zend.com/manual/current/en/modules/zend.i18n.translating.html),
which supports different translation adapters, dealing with different storage formats.

By default, SilverStripe uses a YAML format which is loaded via the
[symfony/translate](http://symfony.com/doc/current/translation.html)  library.

Example: framework/lang/en.yml (extract)

```yml
en:
  ImageUploader:
    Attach: 'Attach {title}'
  UploadField:
    NOTEADDFILES: 'You can add files once you have saved for the first time.'
```

Translation table: framework/lang/de.yml (extract)

```yml
de:
  ImageUploader:
    ATTACH: '{title} anhängen'
  UploadField:
    NOTEADDFILES: 'Sie können Dateien hinzufügen sobald Sie das erste mal gespeichert haben'
```

Note that translations are cached across requests.
The cache can be cleared through the `?flush=1` query parameter,
or explicitly through `Zend_Translate::getCache()->clean(Zend_Cache::CLEANING_MODE_ALL)`.

## Javascript Usage

The i18n system in JavaScript is similar to its PHP equivalent.
Languages are typically stored in `<my-module-dir>/javascript/lang`.
Unlike the PHP logic, these files aren't auto-discovered and have to be included manually.

### Requirements

Each language has its own language table in a separate file.
To save bandwidth, only two files are actually loaded by
the browser: The current locale, and the default locale as a fallback.
The `Requirements` class has a special method to determine these includes:
Just point it to a directory instead of a file, and the class will figure out the includes.

If using this on the frontend, it's also necessary to include the stand-alone i18n
js file.

```php
use SilverStripe\View\Requirements;

Requirements::javascript('silverstripe/admin:client/dist/js/i18n.js');
Requirements::add_i18n_javascript('<my-module-dir>/javascript/lang');
```

###  Translation Tables in JavaScript

Translation tables are automatically included as required, depending on the configured locale in `i18n::get_locale()`.
As a fallback for partially translated tables we always include the master table (`en.js`) as well.

Master Table (`<my-module-dir>/javascript/lang/en.js`)


```js
if(typeof(ss) == 'undefined' || typeof(ss.i18n) == 'undefined') {
  console.error('Class ss.i18n not defined');
} else {
  ss.i18n.addDictionary('en', {
    'MYMODULE.MYENTITY' : "Really delete these articles?"
  });
}
```

Example Translation Table (`<my-module-dir>/javascript/lang/de.js`)


```js
ss.i18n.addDictionary('de', {
  'MYMODULE.MYENTITY' : "Artikel wirklich löschen?"
});
```

For most core modules, these files are generated by a
[build task](https://github.com/silverstripe/silverstripe-buildtools/blob/master/src/GenerateJavascriptI18nTask.php),
with the actual source files in a JSON
format which can be processed more easily by external translation providers (see `javascript/lang/src`).

### Basic Usage


```js
alert(ss.i18n._t('MYMODULE.MYENTITY'));
```

### Advanced Use

The `ss.i18n` object contain a couple functions to help and replace dynamic variable from within a string.

#### Legacy sequential replacement with sprintf()

`sprintf()` will substitute occurencies of `%s` in the main string with
each of the following arguments passed to the function. The substitution
is done sequentially.

```js
// MYMODULE.MYENTITY contains "Really delete %s articles by %s?"
alert(ss.i18n.sprintf(
    ss.i18n._t('MYMODULE.MYENTITY'),
    42,
    'Douglas Adams'
));
// Displays: "Really delete 42 articles by Douglas Adams?"
```

#### Variable injection with inject()

`inject()` will substitute variables in the main string like `{myVar}` by the
keys in the object passed as second argument. Each variable can be in any order
and appear multiple times.


```js
// MYMODULE.MYENTITY contains "Really delete {count} articles by {author}?"
alert(ss.i18n.inject(
    ss.i18n._t('MYMODULE.MYENTITY'),
    {count: 42, author: 'Douglas Adams'}
));
// Displays: "Really delete 42 articles by Douglas Adams?"
```

## Limitations

*  No detecting/conversion of character encodings (we rely fully on UTF-8)
*  Translation of graphics/assets
*  Usage of gettext (too clumsy, too many requirements)
*  Displaying multiple languages/encodings on the same page

## Links

 * [Help to translate](../../contributing/translations) - Instructions for online collaboration to translate core
 * [Help to translate](../../contributing/translation_process) - Instructions for adding translation to your own modules
 * [http://www.i18nguy.com/](http://www.i18nguy.com/)
 * [balbus.tk i18n notes](http://www.balbuss.com/internationalize/)
