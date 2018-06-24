title: Upgrading
introduction: Keep your SilverStripe installations up to date with the latest fixes, security patches and new features.

# Upgrading to SilverStripe 4

SilverStripe applications should be kept up to date with the latest security releases. Usually an update or upgrade to
your SilverStripe installation means overwriting files, flushing the cache and updating your database schema.

<div class="info" markdown="1">
See our [upgrade notes and changelogs](/changelogs/4.0.0) for 4.0.0 specific information, bugfixes and API changes.
</div>

## Composer

SilverStripe CMS is a modular system, and [Composer](http://getcomposer.org)
is the default way to manage your modules ([instructions](/getting_started/composer)).
We recommend using `recipe-cms` in your `composer.json` file to help you keep
up to date and run `composer update`.

```json
{
    "require": {
        "silverstripe/recipe-cms": "^1"
    }
}
```

This will also add extra dependencies, such as the `admin`, `asset-admin`, `reports`, `errorpage` and `siteconfig`
modules.

If you want more granular control over what gets installed,
reading through the README documentation in the [recipe plugin repository](https://github.com/silverstripe/recipe-plugin)
and also checking the `composer.json` files in [recipe-core](https://github.com/silverstripe/recipe-core) and
[recipe-cms](https://github.com/silverstripe/recipe-cms).

For a description on how to handle issues with pre-existing composer installs or upgrading other modules, please read
through the [Composer dependency update section](/changelogs/4.0.0#deps)

## Manual upgrades

* Check if any modules (e.g. `blog` or `forum`) in your installation are incompatible and need to be upgraded as well.
* Backup your database content.
* Backup your webroot files.
* Download the new release and uncompress it to a temporary folder.
* Leave custom folders like *themes* in place.
* Rename `app/code` folder to `app/src`, updating your `app/_config/app.yml` config to set the new project name.
* Identify system folders in your webroot (`cms`, `framework` and any additional modules).
* Delete existing system folders (or move them outside of your webroot).
* Add a `private static $table_name = 'MyDataObject'` for any custom DataObjects in your code that are namespaced. This ensures that your database table name will be `MyDataObject` instead of `Me_MyPackage_Model_MyDataObject` (converts your namespace to the table_name).
* Ensure you add [namespaces](http://php.net/manual/en/language.namespaces.php) to any custom classes in your `app/` folder. Your namespaces should follow the pattern of `Vendor\Package` with anything additional defined at your discretion. **Note:** The `Page` and `PageController` classes *must* be defined in the global namespace (or without a namespace).
* Install the updated framework, CMS and any other modules you require by updating your `composer.json` configuration and running `composer update`. Some features have been split into their own modules, such as `asset-admin` and `errorpage`. Please refer to [`recipe-cms` composer.json](https://github.com/silverstripe/recipe-cms) and [`recipe-core` composer.json](https://github.com/silverstripe/recipe-core) for a list of recommended modules to include.
* Check if you need to adapt your code to changed PHP APIs. For more information please refer to [the changelog](/changelogs/4.0.0). There is an upgrader tool available to help you with most of the changes required (see below).
* Visit http://yoursite.com/dev/build/?flush=1 to rebuild the website database.
* Check if you have overwritten any core templates or styles which might need an update.

<div class="warning" markdown="1">
Never update a website on the live server without trying it on a development copy first!
</div>

## Environment variables file changed to dotenv

SilverStripe 4 requires the use of `.env` and no longer supports using `_ss_environment.php` for your
environment configuration.

You'll need to move your constants to a new `.env` file before SilverStripe will build successfully.

For further details about the `.env` migration, read through the
[`_ss_environment.php` changed to `.env` section](/changelogs/4.0.0#env)

If you have installed the upgrader tool, you can use the `environment` command to generate a valid `.env` file from your
existing `_ss_environment.php` file.

```
cd ~/my-project-root
upgrade-code environment --write
```

Read the [upgrader `environment` command documentation](https://github.com/silverstripe/silverstripe-upgrader/blob/master/docs/en/environment.md)
for more details.

## Using the upgrader tool

We've developed [an upgrader tool](https://github.com/silverstripe/silverstripe-upgrader) which you can use to help
with the upgrade process to SilverStripe 4. See the README documentation in the repository for more detailed
instructions on how to use it.


### `index.php` and `.htaccess` rewrites

The location of SilverStripe's "entry file" has changed. Your project and server environment will need
to adjust the path to this file from `framework/main.php` to `public/index.php`.

For more details, please read through the [`index.php` and `.htaccess` rewrites section](/changelogs/4.0.0#index-php-rewrites)

After installing, run the upgrader doctor command:

```
cd ~/my-project-root
upgrade-code doctor
```

This will ensure that your `.htaccess` and `index.php` are set to a reasonable default value for a clean installation.

### Renamed and namespaced classes

Nearly all core PHP classes in SilverStripe have been namespaced. For example, `DataObject` is now called `SilverStripe\ORM\DataObject`.

For a full list of renamed classes, check the `.upgrade.yml` definitions in each module.

After installing, run the upgrader upgrade command:
```
cd ~/my-project-root
~/.composer/vendor/bin/upgrade-code upgrade ./app/src --write
```

### Switching to new `app/src` structure

The `reorganise` command can automatically rename the `mysite` and `code` folder for you. It will search your code and find any occurence of `mysite`. It won't replace those occurence with `app` however.

After installing, run the upgrader reorganise command:
```
cd ~/my-project-root
~/.composer/vendor/bin/upgrade-code reorganise --write
```

## Migrating files

Since the structure of `File` dataobjects has changed, a new task `MigrateFileTask`
has been added to assist in migration of legacy files (see [file migration documentation](/developer_guides/files/file_migration)).

```
$ ./vendor/bin/sake dev/tasks/MigrateFileTask
```

## Upgrade tips

While there's some code we can automatically rewrite, other uses of changed SilverStripe APIs aren't that obvious.
You can use our `inspect` to get some hints on where you need to review code manually.
Hints will generally point to more detail about a specific upgrade in this guide.
This task requires [the upgrader tool](https://github.com/silverstripe/silverstripe-upgrader).

```
~/.composer/vendor/bin/upgrade-code inspect ./mysite
```

These hints only cover a part of the upgrade work, but can serve as a good indicator for where to start.

If you've already had a look over the changelog, you will see that there are some fundamental changes that need to be implemented to upgrade from 3.x. Here's a couple of the most important ones to consider:

* PHP 5.6 is now the minimum required version (and up to PHP 7.1.x is supported!).
* CMS CSS has been re-developed using Bootstrap 4 as a base.
* SilverStripe code _should_ now be [PSR-2 compliant](http://www.php-fig.org/psr/psr-2/). While this is not a requirement, we strongly suggest you switch over now. You can use tools such as [`phpcbf`](https://github.com/squizlabs/PHP_CodeSniffer/wiki/Fixing-Errors-Automatically) to do most of it automatically.
* We've also introduced some best practices for module development. [See the Modules article](/developer_guides/extending/modules) for more information.

## Additional tips easily missed

[Object class replaced by traits](/changelogs/4.0.0#object-replace)
The `Object` class has been superceded by three traits:
 - `Injectable`: Provides `MyClass::create()` and `MyClass::singleton()`
 - `Configurable`: Provides `MyClass::config()`
 - `Extensible`: Provides all methods related to extensions (E.g. add_extension()).
`$this->class` no longer recommended, should use `static::class` or `get_class($classObject)` instead.

[Rewrite literal table names](/changelogs/4.0.0#literal-table-names)  
Use `$table = SilverStripe\ORM\DataObject::getSchema()->tableForField($model, $field)` instead of `$model` directly.

[Rewrite literal class names](/changelogs/4.0.0#literal-class-names)  
For example, referencing the class name `'Member'` should be `Member::class` or if you're in YML config it should be `SilverStripe\Security\Member`.

[Template locations and references](/changelogs/4.0.0#template-locations)  
Templates require the folder path inside the templates folder, and Core templates are placed in paths following the class namespace, e.g. `FormField` is now `SilverStripe/Forms/FormField`.  
When using the `<% include %>` syntax, you can leave out the `Includes` folder in the path.

[Config settings should be set to `private static`](/changelogs/4.0.0#private-static)  
We no longer support `public static $config_item` on classes, it now needs to be `private static $config_item`.

[Module paths can't be hardcoded](/changelogs/4.0.0#module-paths)  
Modules may not be placed in a deterministic folder (e.g. `/framework`),
you should use getters on the [Module](api:SilverStripe\Core\Manifest\Module) object instead.

Please see the changelogs for more details on ways that the getters on the `Module` object could be used.

[Adapt tooling to modules in vendor folder](#vendor-folder)
SilverStripe modules are now placed in the `vendor` folder like many other composer package.

Modules need to declare which files need to be exposed via the new [vendor-plugin](https://github.com/silverstripe/vendor-plugin), using symlinks to link to files from the publically accessible `resources` folder.

[SS_Log replaced with PSR-3 logging](/changelogs/4.0.0#psr3-logging)
SilverStripe 4 introduces [PSR-3](http://www.php-fig.org/psr/psr-3/) compatible logger interfaces. Services can access the logger using the LoggerInterface::class service.

Please see the changelogs for more details on how to implement logging.

[Upgrade `app/_config.php`](/changelogs/4.0.0#config-php)
The globals `$database` and `$databaseConfig` are deprecated. You should upgrade your site `_config.php` files to use the [.env configuration](#env).  
`conf/ConfigureFromEnv.php` is no longer used, and references to this file should be deleted.

[Session object removes static methods](/changelogs/4.0.0#session)
Session object is no longer statically accessible via `Session::inst()`. Instead, `Session` is a member of the current request.

[Extensions are now singletons](#extensions-singletons)
This means that state stored in private/protected variables are now shared across all objects which use this extension.  
It is recommended to refactor the variables to be stored against the owner object.

[Explicit text casting on template variables](/changelogs/4.0.0#template-casting)
Calling `$MyField` on a DataObject in templates will by default cast MyField as `Text` which means it will be safely encoded.  
You can change the casting for this by defining a casting config on the DataObject:
```php
    private static $casting = [
        'MyField' => 'HTMLText'
    ];
```


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
