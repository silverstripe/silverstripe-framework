title: Translations
summary: Translate interface components like button labels into multiple languages.

# Contributing Translations

We are always looking for new translators. Even if a specific language already is translated and has an official 
maintainer, we can use helping hands in reviewing and updating translations. Important: It is perfectly fine if you 
only have time for a partial translation or quick review work - our system accomodates many people collaborating on the 
same language.

The content for UI elements (button labels, field titles) and instruction texts shown in the CMS and elsewhere is 
stored in the PHP code for a module (see [i18n](/topics/i18n)). All content can be extracted as a "language file", and 
uploaded to an online translation editor interface. SilverStripe is already translated in over 60 languages, and we're 
relying on native speakers to keep these up to date, and of course add new languages. 

Please register a free translator account to get started, even if you just feel like fixing up a few sentences.

## The online translation tool

We provide a GUI for translations through [transifex.com](http://transifex.com).  If you don't have an account yet, 
please follow the links there to sign up.  Select a project from the 
[list of translatable modules](https://www.transifex.com/accounts/profile/silverstripe/) and start translating online!

For all modules listed there, we automatically import new master strings as they get committed to the various code 
bases (via a nightly task), so you're always translating on the latest and greatest version. 

You can check the last successful push of the translation master strings in our 
[public continuous integration server](http://teamcity.silverstripe.com/viewType.html?buildTypeId=bt112) 
(select "log in as guest").

## FAQ

### What happened to getlocalization.com?

We migrated from getlocalization.com to transifex in mid 2013.

### How do I translate a module not listed on Transifex?

Most modules maintained by SilverStripe are on Transifex. For other modules, have a look in the module README if 
there's any specific instructions. If there aren't, you'll need to translate the YAML files directly. If the module is 
on github, you can create a fork, edit the files, and send back your pull request all directly on the website 
([instructions](https://help.github.com/articles/fork-a-repo)).

### How do I translate substituted strings? (e.g. '%s' or '{my-variable}')

You don't have to - if the english master-string reads 'Hello %s', your german translation would be 'Hallo %s'. Strings 
prefixed by a percentage-sign are automatically replaced by silverstripe with dynamic content. See 
http://php.net/sprintf for details. The newer `{my-variable}` format works the same way, but makes its intent clearer, 
and allows reordering of placeholders in your translation.

### Do I need to convert special characters (e.g. HTML-entities)?

Special characters (such as german umlauts) need to be entered in their native form. Please don't use HTML-entities 
("ä" instead of "ä"). Silverstripe stores and renders most strings in UTF8 (Unicode) format.

### How can I check out my translation in the interface?

Currently translated entities are not directly factored into code (for security reasons and release/review-control), so 
you can't see them straight away. 

It is strongly encouraged that you check your translation this way, as its a good way to double check your translation 
works in the right context. Please use our [daily-builds](http://www.silverstripe.org/daily-builds/) for your local 
installation, to ensure you're looking at the most up to date interface. See "Download Translations" above to find out 
how to retrieve the latest translation files.

### Can I change a translation just for one SilverStripe version?

While we version control our translation files like all other source code, the online translation tool doesn't have the 
same capabilities. A translated string (as identified by its unique "entity name") is assumed to work well in all 
releases. If the interface changes in a non-trivial fashion, the new translations required should have new identifiers 
as well.

Example: We renamed the "Security" menu title to "Users" in our 3.0 release. As it would confuse users of older versions
unnecessarily, we should be using a new entity name for this, and avoid the change propagating to an older SilverStripe 
version.

### How do I change my interface language?

Once you've logged into the CMS, you should see the text "Hi <your name>" near the top left, you can click this to edit 
your profile ([direct link](http://localhost/admin/myprofile/)). You can then set the "interface language" from a 
dropdown which automatically includes all found translations (based on the files in the `/lang` folders).

### I've found a piece of untranslatable text

It is entirely possible that we missed certain strings in preparing Silverstripe for translation-support. If you're 
technically minded, please read [i18n](/topics/i18n) on how to make it translatable. Otherwise just post your findings 
to the forum.

### How do I add my own module?

Once you've built a translation-enabled module, you can run the "textcollector" on your local installation for this 
specific module (see [i18n](/topics/i18n)). This should find all calls to `_t()` in php and template files, and generate 
a new lang file with the default locale (path: <mymodule>/lang/en.yml). Upload this file to the online translation 
tool, and wait for your translators to do their magic!

### What about right-to-left (RTL) languages (e.g. Arabic)?

SilverStripe doesn't have built-in support for attribute-based RTL-modifications (`<html dir="rtl">`). 

We are currently investigating the available options, and are eager to get feedback on your experiences with 
translating silverstripe RTL.

### Can I translate/edit the language files in my favorite text editor (on my local installation)

Not for modules managed by transifex.com, including "framework" and "cms. It causes us a lot of work in merging these 
files back. Please use the online translation tool for all new and existing translations.

### How does my translation get into a SilverStripe release?

Currently this is a manual process of a core team member downloading approved translations and committing them into our 
source tree.

### How does my translation get approved, who is the maintainer?

The online translation tool (transifex.com) is designed to be decentralized and collaborative, so there's no strict 
approval or review process. Every logged-in user on the system can flag translations, and discuss them with other 
translators.

### I'm seeing lots of duplicated translations, what should I do?

For now, please translate all duplications - sometimes they might be intentional, but mostly the developer just didn't 
know his phrase was already translated. Please contact us about any duplicates that might be worth merging.

### What happened to translate.silverstripe.org?

This was a custom-built online translation tool serving us well for a couple of years, but started to show its age 
(performance and maintainability). It was replaced with transifex.com. All translations from translate.silverstripe.org 
were migrated. Unfortunately, the ownership of individual translations couldn't be migrated.

As the new tool doesn't support the PHP format used in SilverStripe 2.x, this means that we no longer have a working 
translation tool for PHP files. Please edit the PHP files directly and [send us pull requests](/misc/contributing).

This also applies for any modules staying compatible with SilverStripe 2.x.

## Contact

Translators have their own [mailinglist](https://groups.google.com/forum/#!forum/silverstripe-translators), but you can 
also reach a core member on [IRC](http://silverstripe.org/irc). The transifex.com interface has a built-in discussion 
board if you have specific comments on a translation.

## Related

 * [i18n](/developer_guids/i18n): Developer-level documentation of Silverstripe's i18n capabilities
 * [translation-process](translation-process): Information about managing translations for the core team and/or module maintainers.
 * [translatable](https://github.com/silverstripe/silverstripe-translatable): DataObject-interface powering the website-content translations
 * ["Translatable ModelAdmin" module](http://silverstripe.org/translatablemodeladmin-module/): An extension which allows translations of DataObjects inside ModelAdmin
