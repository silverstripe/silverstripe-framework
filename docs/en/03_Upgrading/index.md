title: Upgrading
introduction: Keep your SilverStripe installations up to date with the latest fixes, security patches and new features.

# Upgrading

SilverStripe Applications should be kept up to date with the latest security releases. Usually an update or upgrade to 
your SilverStripe installation means overwriting files, flushing the cache and updating your database-schema. 

<div class="info" markdown="1">
See our [upgrade notes and changelogs](/changelogs) for release-specific information.
</div>

## Composer 

For projects managed through Composer, check the version defined in your `composer.json` file. Update the version 
constraints if required and update composer.

	:::bash
	composer update

## Manual

*  Check if any modules (e.g. blog or forum) in your installation are incompatible and need to be upgraded as well
*  Backup your database content
*  Backup your webroot files
*  Download the new release and uncompress it to a temporary folder
*  Leave custom folders like *mysite* or *themes* in place.
*  Identify system folders in your webroot (`cms`, `framework` and any additional modules). 
*  Delete existing system folders (or move them outside of your webroot)
*  Extract and replace system folders from your download (Deleting instead of "copying over" existing folders ensures that files removed from the new SilverStripe release are not persisting in your installation)
*  Visit http://yoursite.com/dev/build/?flush=1 to rebuild the website database.
*  Check if you need to adapt your code to changed PHP APIs
*  Check if you have overwritten any core templates or styles which might need an update.

<div class="warning" markdown="1">
Never update a website on the live server without trying it on a development copy first.
</div>

##  Decision Helpers

How easy will it be to update my project? It's a fair question, and sometimes a difficult one to answer. 

*  "Micro" releases (x.y.z) are explicitly backwards compatible, "minor" and "major" releases can deprecate features and change APIs (see our [/misc/release-process](release process) for details)
*  If you've made custom branches of SilverStripe core, or any thirdparty module, it's going to be harder to upgrade.
*  The more custom features you have, the harder it will be to upgrade.  You will have to re-test all of those features, and adapt to API changes in core.
*  Customizations of a well defined type - such as custom page types or custom blog widgets - are going to be easier to upgrade than customisations that modify deep system internals like rewriting SQL queries.

## Related

* [Release Announcements](http://groups.google.com/group/silverstripe-announce/)
* [Blog posts about releases on silverstripe.org](http://silverstripe.org/blog/tag/release)
* [Release Process](../contributing/release_process)
