title: Upgrading
introduction: Keep your SilverStripe installations up to date with the latest fixes, security patches and new features.

# Upgrading to SilverStripe 4

SilverStripe applications should be kept up to date with the latest security releases. Usually an update or upgrade to your SilverStripe installation means overwriting files, flushing the cache and updating your database schema.

<div class="info" markdown="1">
See our [upgrade notes and changelogs](/changelogs/4.0.0) for 4.0.0 specific information, bugfixes and API changes.
</div>

## Composer

For projects managed through Composer, update the version number of `framework` and `cms` to `^4.0` in your `composer.json` file and run `composer update`.

```json
"require": {
	"silverstripe/framework": "^4.0",
	"silverstripe/cms": "^4.0"
}
```

<div class="info" markdown="1">
Please note that until SilverStripe 4 is stable you will need to also add `"minimum-stability": "dev"` and `"prefer-stable": true` to your `composer.json` to be able to pull these modules.</div>

This will also add extra dependencies, the `reports` and `siteconfig` modules. SilverStripe CMS is becoming more modular, and [composer is becoming the preferred way to manage your code](/getting_started/composer).

### Asset-admin

SilverStripe 4 comes with a new asset administration module. While it is installed by default for new projects, if you are upgrading you will need to install it manually:

```
composer require silverstripe/asset-admin ^1.0
```

This will also install the `graphql` module for GraphQL API access to your SilverStripe system, which powers the `asset-admin` module.

## Manual

* Check if any modules (e.g. `blog` or `forum`) in your installation are incompatible and need to be upgraded as well.
* Backup your database content.
* Backup your webroot files.
* Download the new release and uncompress it to a temporary folder.
* Leave custom folders like *mysite* or *themes* in place.
* Identify system folders in your webroot (`cms`, `framework` and any additional modules).
* Delete existing system folders (or move them outside of your webroot).
* Rename your `Page_Controller` class to `PageController`.
* Add a `private static $table_name = 'MyDataObject'` for any custom DataObjects in your code that are namespaced. This ensures that your database table name will be `MyDataObject` instead of `Me\MyPackage\Model\MyDataObject` (your namespace for the class).
* Ensure you add [namespaces](http://php.net/manual/en/language.namespaces.php) to any custom classes in your `mysite` folder. Your namespaces should follow the pattern of `Vendor\Package` with anything additional defined at your discretion. **Note:** The `Page` and `PageController` classes *must* be defined in the global namespace (or; without a namespace).
* Install the updated framework, CMS and any other modules you require by updating your `composer.json` configuration and running `composer update`. As of SilverStripe 4.0.0 you should also include the `asset-admin` module to power your asset management in the CMS.
* Check if you need to adapt your code to changed PHP APIs. For more information please refer to [the changelog](/changelogs/4.0.0). There is an upgrader tool available to help you with most of the changes required (see below).
* Visit http://yoursite.com/dev/build/?flush=1 to rebuild the website database.
* Check if you have overwritten any core templates or styles which might need an update.

<div class="warning" markdown="1">
Never update a website on the live server without trying it on a development copy first!
</div>

## Using the upgrader tool

We've developed [an upgrader tool](https://github.com/silverstripe/silverstripe-upgrader) which you can use to help you with the upgrade process to SilverStripe 4. [See the upgrading notes](/changelogs/4.0.0/#a-name-upgrading-a-upgrading) in the changelog for more detailed instructions on how to use it.

## Quick tips

If you've already had a look over the changelog, you will see that there are some fundamental changes that need to be implemented to upgrade from 3.x. Here's a couple of the most important ones to consider:

* PHP 5.5 is now the minimum required version (and PHP 7.x is supported!).
* All SilverStripe classes are now namespaced, and some have been renamed. Most of your modules will also have been namespaced, and you will need to consider this when updating class references (including YAML configuration) in your own code.
* CMS CSS has been re-developed using Bootstrap 4 as a base.
* SilverStripe code _should_ now be [PSR-2 compliant](http://www.php-fig.org/psr/psr-2/). While this is not a requirement, we strongly suggest you switch over now. You can use tools such as [`phpcbf`](https://github.com/squizlabs/PHP_CodeSniffer/wiki/Fixing-Errors-Automatically) to do most of it automatically.

We've also introduced some best practices for module development. [See the Modules article](/developer_guides/extending/modules) for more information.

## Decision Helpers

How easy will it be to update my project? It's a fair question, and sometimes a difficult one to answer.

*  "Micro" releases (x.y.z) are explicitly backwards compatible, "minor" and "major" releases can deprecate features and change APIs (see our [release process](/contributing/release_process) for details)
*  If you've made custom branches of SilverStripe core, or any thirdparty module, it's going to be harder to upgrade.
*  The more custom features you have, the harder it will be to upgrade. You will have to re-test all of those features, and adapt to API changes in core.
*  Customizations of a well defined type - such as custom page types or custom blog widgets - are going to be easier to upgrade than customisations that modify deep system internals like rewriting SQL queries.

## Related

* [Release Announcements](http://groups.google.com/group/silverstripe-announce/)
* [Blog posts about releases on silverstripe.org](http://silverstripe.org/blog/tag/release)
* [Release Process](../contributing/release_process)
