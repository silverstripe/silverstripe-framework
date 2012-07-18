# Upgrading

Usually an update or upgrade your SilverStripe installation just means 
overwriting files and updating your database-schema. 

See our [upgrade notes and changelogs](/changelogs) for release-specific information.

## Process

*  Check if any modules (e.g. blog or forum) in your installation are compatible and need to be upgraded as well
*  Backup your database content
*  Backup your webroot files
*  Download the new release and uncompress it to a temporary folder
*  Leave custom folders like *mysite* or *themes* in place.
*  Identify system folders in your webroot (`cms`, `framework`, `sapphire` and any additional modules). 
*  Delete existing system folders (or move them outside of your webroot)
*  Extract and replace system folders from your download (Deleting instead of "copying over" existing folders ensures that files removed from the new SilverStripe release are not persisting in your installation)
*  Visit http://yoursite.com/dev/build/?flush=1 to rebuild the website database
*  Check if you need to adapt your code to changed PHP APIs
*  Check if you have overwritten any core templates or styles which might need an update
*  See [common-problems](common-problems) for a list of likely mistakes that could happen during an upgrade.

<div class="warning" markdown="1">
	Never update a website on the live server without trying it on a development copy first.
</div>


##  Decision Helpers

How easy will it be to update my project? It's a fair question, and sometimes a difficult one to answer. 

*  "Micro" releases (x.y.z) are explicitly backwards compatible, "minor" and "major" releases can deprecate features and change APIs (see our [/misc/release-process](release process) for details)
*  If you've made custom branches of SilverStripe core, or any thirdparty module, it's going to be harder to upgrade.
*  The more custom features you have, the harder it will be to upgrade.  You will have to re-test all of those features, and adapt to API changes in core.
*  Customisations of a well defined type - such as custom page types or custom blog widgets - are going to be easier to upgrade than customisations that modify deep system internals like rewriting SQL queries.

## Related

*  [Release Announcements](http://groups.google.com/group/silverstripe-announce/)
*  [Blog posts about releases on silverstripe.org](http://silverstripe.org/blog/tag/release)
* [/misc/release-process](Release Process)