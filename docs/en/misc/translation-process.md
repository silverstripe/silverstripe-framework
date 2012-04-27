# Translation Process #

## Overview ##

SilverStripe contains language files for user-facing strings (see [i18n](/topics/i18n)).
These are stored in YML format, which is fairly readable,
but also sensitive to whitespace and formatting changes,
so not ideally suited for non-technical editors.

Note: Until SilverStripe 3.0, we used a PHP storage format.
This format is now deprecated, and we don't provide tools
for editing the files. Please see below for information on
how to convert these legacy files and existing translations to YML.
	
## Help as a translator

### The online translation platform

We are managing our translations through a service called
[getlocalization.com](http://getlocalization.com).
Most modules are managed under the "silverstripe" user there,
see [list of translatable modules](http://www.getlocalization.com/profile/?username=silverstripe).
If you don't have an account yet, please follow the links there to sign up.

## Set up your module for localization

### Collecting translatable text

As a first step, you can automatically collect
all translatable text in your module through the `i18nTextCollector` task.
See [i18n](/topics/i18n#collecting-text) for more details.

### Import master files

If you don't have an account on getlocalization.com yet, [create one](http://www.getlocalization.com/signup).
Choose the free option for public repositories.

On the "Files" tab, you can choose "Import from SCM",
and connect getlocalization to your github account.
Alternatively, upload the `en.yml` file in the "Ruby on Rails" format.

If you don't have existing translations,
your project is ready to go - simply point translators
to the URL, have them sign up, and they can create languages and translations as required.

### Import existing translations

In case you have existing translations in YML format,
there's a "New language file" option in the "Files" tab.

IMPORTANT: Composite locales need to be uploaded with 
a dash separator, which is different from the core format (underscores).
For example, to upload a file called en_GB.yml,
change the first line in this file from "en_GB" to "en-GB".

### Export existing translations

You can simply download the whole language pack as a ZIP archive
and add it to your project. But for composite locales (e.g. "en-GB"),
you have to change the keys in the first line of the file (see note above).

### Converting your language files from 2.4 PHP format

The conversion from PHP format to YML is taken care of by a module
called [i18n_yml_converter](https://github.com/chillu/i18n_yml_converter).

## Contact

Translators have their own [mailinglist](https://groups.google.com/forum/#!forum/silverstripe-translators),
but you can also reach a core member on [IRC](http://silverstripe.org/irc).
The getlocalization.com interface has a built-in discussion board if
you have specific comments on a translation.