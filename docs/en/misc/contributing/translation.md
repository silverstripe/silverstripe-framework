# Contributing Translations for the User Interface

[« Back to Contributing page](../contributing)

We are always looking for new translators. Even if a specific language already is translated and has an official maintainer, we can use helping hands in reviewing and updating translations. Important: It is perfectly fine if you only have time for a partial translation or quick review work - our system accomodates many people collaborating on the same language.

The content for UI elements (button labels, field titles) and instruction texts shown in the CMS and elsewhere is stored in the PHP code for a module (see [i18n](/topics/i18n)). All content can be extracted as a "language file", and uploaded to an online translation editor interface. SilverStripe is already translated in over 60 languages, and we're relying on native speakers to keep these up to date, and of course add new languages. 

Please register a free translator account to get started, even if you just feel like fixing up a few sentences.

## The online translation tool

We provide a GUI for translations through [getlocalization.com](http://getlocalization.com).  If you don't have an account yet, please follow the links there to sign up.  Select a project from the [list of translatable modules](http://www.getlocalization.com/profile/?username=silverstripe) and start translating online!

For all modules listed there, we automatically import new master strings as they get committed to the various codebases, so you're always translating on the latest and greatest version.

## FAQ

### How do I translate substituted strings? (e.g. '%s')

You don't have to - if the english master-string reads 'Hello %s', your german translation would be 'Hallo %s'. Strings prefixed by a percentage-sign are automatically replaced by silverstripe with dynamic content. See http://php.net/sprintf for details.

### Do I need to convert special characters (e.g. HTML-entities)?

Special characters (such as german umlauts) need to be entered in their native form. Please don't use HTML-entities ("ä" instead of "ä"). Silverstripe stores and renders most strings in UTF8 (Unicode) format.

### How can I check out my translation in the interface?

Currently translated entities are not directly factored into code (for security reasons and release/review-control), so you can't see them straight away. 

It is strongly encouraged that you check your translation this way, as its a good way to doublecheck your translation works in the right context.
Please use our [daily-builds](http://www.silverstripe.org/daily-builds/) for your local installation, to ensure you're looking at the most up to date interface. See "Download Translations" above
to find out how to retrieve the latest translation files.

### Can I change a translation just for one SilverStripe version?

While we version control our translation files like all other source code,
the online translation tool doesn't have the same capabilities.
A translated string (as identified by its unique "entity name")
is assumed to work well in all releases. If the interface changes
in a non-trivial fashion, the new translations required should
have new identifiers as well.

Example: We renamed the "Security" menu title to "Users"
in our 3.0 release. As it would confuse users of older versions
unnecessarily, we should be using a new entity name for this,
and avoid the change propagating to an older SilverStripe version.

### How do I change my interface language?

Once you've logged into the CMS, you should see a "profile"-link on the lower right corner (direct link: http://www.site.com/admin/myprofile). You can set the "interface language" from a dropdown which automatically includes all found translations (based on the files in the `/lang` folders).

### I've found a piece of untranslatable text

It is entirely possible that we missed certain strings in preparing Silverstripe for translation-support. If you're technically minded, please read [i18n](/topics/i18n) on how to make it translatable. Otherwise just post your findings to the forum.

Note: JavaScript strings can't be translated through the online translation tool at the moment, 
you'll need to edit the file locally (e.g. cms/javascript/de_DE.js), and submit a patch. 

### How do I add my own module?

Once you've built a translation-enabled module, you can run the "textcollector" on your local installation for this specific module (see [i18n](/topics/i18n)). This should find all calls to `_t()` in php and template files, and generate a new lang file with the default locale (path: <mymodule>/lang/en.yml). Upload this file to the 
online translation tool, and wait for hyour translators to do their magic!

### What about right-to-left (RTL) languages (e.g. Arabic)?

SilverStripe doesn't have built-in support for attribute-based RTL-modifications (`<html dir="rtl">`). 
We are currently investigating the available options, and are eager to get feedback on your experiences with translating silverstripe RTL.

### Can I translate/edit the language files in my favourite text editor (on my local installation)

Not for modules managed by getlocalization.com, including "framework" and "cms.
It causes us a lot of work in merging these files back.
Please use the online translation tool for all new and existing translations.

### How does my translation get into a SilverStripe release?

Currently this is a manual process of a core team member downloading approved translations and committing them into our source tree.

### How does my translation get approved, who is the maintainer?

The online translation tool (getlocalization.com) is designed to be decentralized and collaborative,
so there's no strict approval or review process.
Every logged-in user on the system can flag translations,
and discuss them with other translators.

### I'm seeing lots of duplicated translations, what should I do?

For now, please translate all duplications - sometimes they might be intentional, but mostly the developer just didn't know his phrase was already translated. 
Please contact us about any duplicates that might be worth merging.

### What happened to translate.silverstripe.org?

This was a custom-built online translation tool serving us well for a couple of years,
but started to show its age (performance and maintainability). It was replaced
with getlocalization.com. All translations from translate.silverstripe.org were migrated.
Unfortunately, the ownership of individual translations couldn't be migrated.

As the new tool doesn't support the PHP format used in SilverStripe 2.x, 
this means that we no longer have a working translation tool for PHP files.
Please edit the PHP files directly and [send us pull requests](/misc/contributing).
This also applies for any modules staying compatible with SilverStripe 2.x.

## Contact

Translators have their own [mailinglist](https://groups.google.com/forum/#!forum/silverstripe-translators),
but you can also reach a core member on [IRC](http://silverstripe.org/irc).
The getlocalization.com interface has a built-in discussion board if
you have specific comments on a translation.

## Related

 * [i18n](/topics/i18n): Developer-level documentation of Silverstripe's i18n capabilities
 * [translation-process](../translation-process): Information about managing translations for the core team and/or module maintainers.
 * [translatable](https://github.com/silverstripe/silverstripe-translatable): DataObject-interface powering the website-content translations
 * ["Translatable ModelAdmin" module](http://silverstripe.org/translatablemodeladmin-module/): An extension which allows translations of DataObjects inside ModelAdmin