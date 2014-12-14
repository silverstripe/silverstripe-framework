title: i18n
summary: Display templates and PHP code in different languages based on the preferences of your website users.

# i18n

The i18n class (short for "internationalization") in SilverStripe enables you to display templates and PHP code in
different languages based on your global settings and the preferences of your website users. This process is also known
as l10n (short for "localization").

For translating any content managed through the CMS or stored in the database, please use the 
[translatable](http://github.com/silverstripe/silverstripe-translatable) module.

This page aims to describe the low-level functionality of the i18n API. It targets developers who:

*  Are involved in creating templates in different languages.
*  Want to build their own modules with i18n capabilities.
*  Want to make their PHP-code (e.g. form labels) i18n-ready

## Usage

### Enabling i18n

The i18n class is enabled by default.

### Setting the locale

To set the locale you just need to call `[api:i18n::set_locale()]` passing, as a parameter, the name of the locale that 
you want to set.

	:::php
	// mysite/_config.php
	i18n::set_locale('de_DE'); // Setting the locale to German (Germany)
	i18n::set_locale('ca_AD'); // Setting to Catalan (Andorra)


Once we set a locale, all the calls to the translator function will return strings according to the set locale value, if
these translations are available. See [unicode.org](http://unicode.org/cldr/data/diff/supplemental/languages_and_territories.html) 
for a complete listing of available locales.

### Getting the locale

As you set the locale you can also get the current value, just by calling `[api:i18n::get_locale()]`.

### Declaring the content language in HTML		{#declaring_the_content_language_in_html}

To let browsers know which language they're displaying a document in, you can declare a language in your template.

	:::html
	//'Page.ss' (HTML)
	<html lang="$ContentLocale">

	//'Page.ss' (XHTML)
	<html lang="$ContentLocale" xml:lang="$ContentLocale" xmlns="http://www.w3.org/1999/xhtml">


Setting the `<html>` attribute is the most commonly used technique. There are other ways to specify content languages
(meta tags, HTTP headers), explained in this [w3.org article](http://www.w3.org/International/tutorials/language-decl/).

You can also set the [script direction](http://www.w3.org/International/questions/qa-scripts),
which is determined by the current locale, in order to indicate the preferred flow of characters
and default alignment of paragraphs and tables to browsers.

	:::html
	<html lang="$ContentLocale" dir="$i18nScriptDirection">

### Date and time formats

Formats can be set globally in the i18n class. These settings are currently only picked up by the CMS, you'll need
to write your own logic for any frontend output.

	:::php
	Config::inst()->update('i18n', 'date_format', 'dd.MM.YYYY');
	Config::inst()->update('i18n', 'time_format', 'HH:mm');

Most localization routines in SilverStripe use the [Zend_Date API](http://framework.zend.com/manual/en/zend.date.html).
This means all formats are defined in
[ISO date format](http://framework.zend.com/manual/en/zend.date.constants.html#zend.date.constants.selfdefinedformats),
not PHP's built-in [date()](http://nz.php.net/manual/en/function.date.php).

### Language Names

SilverStripe comes with a built-in list of common languages, listed by locale and region.
They can be accessed via the `i18n.common_languages` and `i18n.common_locales` [config setting](/topics/configuration).

In order to add a value, add the following to your `config.yml`:

	:::yml
	i18n:
	  common_locales:
	    de_CGN:
	      name: German (Cologne)
	      native: Kölsch

Similarly, to change an existing language label, you can overwrite one of these keys:

	:::yml
	i18n:
	  common_locales:
	    en_NZ:
	      native: Niu Zillund

### i18n in URLs

By default, URLs for pages in SilverStripe (the `SiteTree->URLSegment` property)
are automatically reduced to the allowed allowed subset of ASCII characters.
If characters outside this subset are added, they are either removed or (if possible) "transliterated".
This describes the process of converting from one character set to another
while keeping characters recognizeable. For example, vowels with french accents
are replaced with their base characters, `pâté` becomes `pate`.

In order to allow for so called "multibyte" characters outside of the ASCII subset,
limit the character filtering in the underlying configuration setting,
by setting `URLSegmentFilter.default_use_transliterator` to `false` in your YAML configuration.

Please refer to [W3C: Introduction to IDN and IRI](http://www.w3.org/International/articles/idn-and-iri/) for more details.

### i18n in Form Fields

Date- and time related form fields support i18n ([api:DateField], [api:TimeField], [api:DatetimeField]).

	:::php
	i18n::set_locale('ca_AD');
	$field = new DateField(); // will automatically set date format defaults for 'ca_AD'
	$field->setLocale('de_DE'); // will not update the date formats
	$field->setConfig('dateformat', 'dd. MMMM YYYY'); // sets typical 'de_DE' date format, shows as "23. Juni 1982"

Defaults can be applied globally for all field instances through the `DateField.default_config`
and `TimeField.default_config` [configuration arrays](/topics/configuration).
If no 'locale' default is set on the field, [api:i18n::get_locale()] will be used.

**Important:** Form fields in the CMS are automatically configured according to the profile settings for the logged-in user (`Member->Locale`, `Member->DateFormat` and `Member->TimeFormat`). This means that in most cases,
fields created through [api:DataObject::getCMSFields()] will get their i18n settings from a specific member

The [api:DateField] API can be enhanced by JavaScript, and comes with
[jQuery UI datepicker](http://jqueryui.com/demos/datepicker/) capabilities built-in.
The field tries to translate the date formats and locales into a format compatible with jQuery UI
(see [api:DateField_View_JQuery::$locale_map_] and [api:DateField_View_JQuery::convert_iso_to_jquery_format()]).

	:::php
	$field = new DateField();
	$field->setLocale('de_AT'); // set Austrian/German locale
	$field->setConfig('showcalendar', true);
	$field->setConfig('jslocale', 'de'); // jQuery UI only has a generic German localization
	$field->setConfig('dateformat', 'dd. MMMM YYYY'); // will be transformed to 'dd. MM yy' for jQuery

## Translating text

Adapting a module to make it localizable is easy with SilverStripe. You just need to avoid hardcoding strings that are
language-dependent and use a translator function call instead.

	:::php
	// without i18n
	echo "This is a string";
	// with i18n
	echo _t("Namespace.Entity","This is a string");


All strings passed through the `_t()` function will be collected in a separate language table (see [Collecting text](#collecting-text)), which is the starting point for translations.

### The _t() function

The `_t()` function is the main gateway to localized text, and takes four parameters, all but the first being optional.
It can be used to translate strings in both PHP files and template files. The usage for each case is described below.

 * **$entity:** Unique identifier, composed by a namespace and an entity name, with a dot separating them. Both are arbitrary names, although by convention we use the name of the containing class or template. Use this identifier to reference the same translation elsewhere in your code.
 * **$string:** (optional) The original language string to be translated. Only needs to be declared once, and gets picked up the [text collector](#collecting-text).
 * **$string:** (optional) Natural language comment (particularly short phrases and individual words)
are very context dependent. This parameter allows the developer to convey this information
to the translator.
 * **$array::** (optional) An array of injecting variables into the second parameter

#### Usage in PHP Files

	:::php

	// Simple string translation
	_t('LeftAndMain.FILESIMAGES','Files & Images');

	// Using the natural languate comment parameter to supply additional context information to translators
	_t('LeftAndMain.HELLO','Site content','Menu title');

	// Using injection to add variables into the translated strings.
	_t('CMSMain.RESTORED',
		"Restored {value} successfully",
		'This is a message when restoring a broken part of the CMS',
		array('value' => $itemRestored)
	);

#### Usage in Template Files

<div class="hint" markdown='1'>
The preferred template syntax has changed somewhat since [version 2.x](http://doc.silverstripe.org/framework/en/2.4/topics/i18n#usage-2).
</div>

In `.ss` template files, instead of `_t(params)` the syntax `<%t params %>` is used. The syntax for passing parameters to the function is quite different to
the PHP version of the function.

 * Parameters are space separated, not comma separated
 * The original language string and the natural language comment parameters are separated by ` on `.
 * The final parameter (which is an array in PHP) is passed as a space separated list of key/value pairs.

	:::ss
	// Simple string translation
	<%t Namespace.Entity "String to translate" %>

	// Using the natural languate comment parameter to supply additional context information to translators
	<%t SearchResults.NoResult "There are no results matching your query." is "A message displayed to users when the search produces no results." %>

	// Using injection to add variables into the translated strings (note that $Name and $Greeting must be available in the current template scope).
	<%t Header.Greeting "Hello {name} {greeting}" name=$Name greeting=$Greeting %>

#### Caching in Template Files with locale switching

When caching a `<% loop %>` or `<% with %>` with `<%t params %>`. It is important to add the Locale to the cache key 
otherwise it won't pick up locale changes.

	:::ss
	<% cached 'MyIdentifier', $CurrentLocale %>
		<% loop $Students %>
			$Name
		<% end_loop %>
	<% end_cached %>

## Collecting text

To collect all the text in code and template files we have just to visit: `http://localhost/dev/tasks/i18nTextCollectorTask`

Text collector will then read the files, build the master string table for each module where it finds calls to the
underscore function, and tell you about the created files and any possible entity redeclaration.

If you want to run the text collector for just one module you can use the 'module' parameter: 
`http://localhost/dev/tasks/i18nTextCollectorTask/?module=cms`

<div class="hint" markdown='1'>
You'll need to install PHPUnit to run the text collector (see [testing-guide](/topics/testing)).
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

	---
	Name: customi18n
	Before: 'defaulti18n'
	---
	i18n:
	  module_priority:
	    - module1
	    - module2
	    - module3

The config option being set is `i18n.module_priority`, and it is a list of module names.

There are a few special cases:

 * If not explicitly mentioned, your project is put as the first module.
 * The module name `other_modules` can be used as a placeholder for all modules that aren't
   specifically mentioned.

## Language definitions

Each module can have one language table per locale, stored by convention in the `lang/` subfolder.
The translation is powered by [Zend_Translate](http://framework.zend.com/manual/en/zend.translate.html),
which supports different translation adapters, dealing with different storage formats.

By default, SilverStripe 3.x uses a YAML format (through the [Zend_Translate_RailsYAML adapter](https://github.com/chillu/zend_translate_railsyaml)).

Example: framework/lang/en.yml (extract)

	en:
	  ImageUploader:
	    Attach: 'Attach %s'
	  UploadField:
	    NOTEADDFILES: 'You can add files once you have saved for the first time.'

Translation table: framework/lang/de.yml (extract)

	de:
	  ImageUploader:
	    ATTACH: '%s anhängen'
	  UploadField:
	    NOTEADDFILES: 'Sie können Dateien hinzufügen sobald Sie das erste mal gespeichert haben'

Note that translations are cached across requests.
The cache can be cleared through the `?flush=1` query parameter,
or explicitly through `Zend_Translate::getCache()->clean(Zend_Cache::CLEANING_MODE_ALL)`.

<div class="hint" markdown='1'>
The format of language definitions has changed significantly in since version 2.x.
</div>

In order to enable usage of [version 2.x style language definitions](http://doc.silverstripe.org/framework/en/2.4/topics/i18n#language-tables-in-php) in 3.x, you need to register a legacy adapter
in your `mysite/_config.php`:

	:::php
	i18n::register_translator(
		new Zend_Translate(array(
			'adapter' => 'i18nSSLegacyAdapter',
			'locale' => i18n::default_locale(),
			'disableNotices' => true,
		)),
		'legacy',
		9 // priority lower than standard translator
	);

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

	:::php
	Requirements::add_i18n_javascript('<my-module-dir>/javascript/lang');


###  Translation Tables in JavaScript

Translation tables are automatically included as required, depending on the configured locale in `i18n::get_locale()`.
As a fallback for partially translated tables we always include the master table (`en.js`) as well.

Master Table (`<my-module-dir>/javascript/lang/en.js`)

	:::js
	if(typeof(ss) == 'undefined' || typeof(ss.i18n) == 'undefined') {
	  console.error('Class ss.i18n not defined');
	} else {
	  ss.i18n.addDictionary('en', {
	    'MYMODULE.MYENTITY' : "Really delete these articles?"
	  });
	}


Example Translation Table (`<my-module-dir>/javascript/lang/de.js`)

	:::js
	ss.i18n.addDictionary('de', {
	  'MYMODULE.MYENTITY' : "Artikel wirklich löschen?"
	});

For most core modules, these files are generated by a
[build task](https://github.com/silverstripe/silverstripe-buildtools/blob/master/src/GenerateJavascriptI18nTask.php),
with the actual source files in a JSON
format which can be processed more easily by external translation providers (see `javascript/lang/src`).

### Basic Usage

	:::js
	alert(ss.i18n._t('MYMODULE.MYENTITY'));


### Advanced Use

The `ss.i18n` object contain a couple functions to help and replace dynamic variable from within a string.

#### Legacy sequential replacement with sprintf()

	`sprintf()` will substitute occurencies of `%s` in the main string with each of the following arguments passed to the function. The substitution is done sequentially.

	:::js
	// MYMODULE.MYENTITY contains "Really delete %s articles by %s?"
	alert(ss.i18n.sprintf(
		ss.i18n._t('MYMODULE.MYENTITY'),
		42,
		'Douglas Adams'
	));
	// Displays: "Really delete 42 articles by Douglas Adams?"


#### Variable injection with inject()

	`inject()` will substitute variables in the main string like `{myVar}` by the keys in the object passed as second argument. Each variable can be in any order and appear multiple times.

	:::js
	// MYMODULE.MYENTITY contains "Really delete {count} articles by {author}?"
	alert(ss.i18n.inject(
		ss.i18n._t('MYMODULE.MYENTITY'),
		{count: 42, author: 'Douglas Adams'}
	));
	// Displays: "Really delete 42 articles by Douglas Adams?"


## Limitations

*  No detecting/conversion of character encodings (we rely fully on UTF-8)
*  Translation of graphics/assets
*  Usage of gettext (too clumsy, too many requirements)
*  Displaying multiple languages/encodings on the same page

## Links

 * [Help to translate](/misc/contribute/translation) - Instructions for online collaboration to translate core
 * [Help to translate](/misc/translation-process) - Instructions for adding translation to your own modules
 * [http://www.i18nguy.com/](http://www.i18nguy.com/)
 * [balbus.tk i18n notes](http://www.balbus.tk/internationalize)
