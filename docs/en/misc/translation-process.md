# Translation Process #

This page covers a few advanced topics related to SilverStripe's translation system.

To find out about how to assist with translating SilverStripe, see the [Contributing Translations page](contributing/translation).

## Set up your own module for localization

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

As a project maintainer, you have the permission can simply download the whole language pack as a ZIP archive
and add it to your project. But for composite locales (e.g. "en-GB"),
you have to change the keys in the first line of the file.

We encourage you to use the SilverStripe build tools for this instead,
as they run some additional sanity checks. They require the "phing" tool.
Create a 'translation-staging' branch in your module before starting,
and merge it back manually to your 'master' as required.

	pear install phing/phing
	cp build.properties.default
	cp build.properties # Add your own getlocalization config to 'build.properties'
	phing -Dmodule=<yourmodule> -propertyfile build.properties translations-sync

### Merge back existing translations

Since the latest translations are downloaded into a "translations-staging"
branch, you need to get them back into your main project repository.
This depends on your release strategy: For simpler modules,
just merge back to master:

	git checkout master
	git merge translations-staging

In case you are maintaining release branches, its a bit more complicated:
The "translations-staging" branch is (correctly) based off master,
but you don't want to merge all other master changes into your release branch.
Use the following task to copy & commit the specific files instead:

	phing -Dmodule=<yourmodule> translations-mergeback

### Converting your language files from 2.4 PHP format

The conversion from PHP format to YML is taken care of by a module
called [i18n_yml_converter](https://github.com/chillu/i18n_yml_converter).

## Download Translations from GetLocalization.com

We are managing our translations through a tool called [getlocalization.com](http://getlocalization.com).
Most modules are managed under the "silverstripe" user there,
see [list of translatable modules](http://www.getlocalization.com/profile/?username=silverstripe).

Translations are exported from there into YML files, generated every hour,
and committed to a special "translation-staging" branch on github.
You can download individual files by opening them on github.com (inside the `lang/` folder), and using the "Raw" view.
Place those files in the appropriate directories on a local silverstripe installation. 

 * ["translation-staging" branch for framework module](https://github.com/silverstripe/sapphire/tree/translation-staging)
 * ["translation-staging" branch for cms module](https://github.com/silverstripe/silverstripe-cms/tree/translation-staging)

# Related

 * [i18n](/topics/i18n): Developer-level documentation of Silverstripe's i18n capabilities
 * [contributing/translation](contributing/translation): Information for translators looking to contribute translations of the SilverStripe UI.
 * [translatable](https://github.com/silverstripe/silverstripe-translatable): DataObject-interface powering the website-content translations
 * ["Translatable ModelAdmin" module](http://silverstripe.org/translatablemodeladmin-module/): An extension which allows translations of DataObjects inside ModelAdmin