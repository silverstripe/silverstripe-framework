title: Upgrading to SilverStripe 4
introduction: Upgrade your project SilverStripe 4 and keep it up to date with the latest fixes, security patches and new features.

# Upgrading a SilverStripe 3 project to SilverStripe 4

SilverStripe applications should be kept up to date with the latest security releases. Usually an update or upgrade to
your SilverStripe installation means overwriting files, flushing the cache and updating your database schema.

## Understanding and planning your upgrade {#planning}

How easy will it be to update my project? It's a fair question, and sometimes a difficult one to answer.

* SilverStripe follows _semantic versioning_ (see our [release process](/contributing/release_process) for details).
  * "Major" releases introduce API changes that may break your application.
  * "Minor" releases (x.y) introduce API changes in a backwards compatible way and can mark some API as deprecated.
  * "Patch" releases (x.y.z) fix bugs without introducing any API changes.
* If you've made custom branches of SilverStripe core, or any thirdparty module, upgrades are going to be more complex.
* More custom features will mean more work to re-test all of those features, and adapt to API changes in core.
* Customisations of a well defined type - such as custom page types or custom blog widgets - 
  are going to be easier to upgrade than customisations that modify deep system internals like rewriting SQL queries.

### Overview of changes

There are some fundamental changes in SilverStripe 4:

* PHP 5.6 is now the minimum required version and up to PHP 7.2 is supported.
* SilverStripe is now even more modular which allows you to remove functionality your project might not need.
* Common functionality sets can now be installed via SilverStripe _recipes_.
* SilverStripe modules can now be installed in the `vendor/` folder along with your regular PHP packages.
* All classes are namespaced: You have to use these, but can decide if you namespace your project code.
* PHP _traits_ replace a few core classes (e.g. `Object`) and make it easy to apply common patterns 
* Public files can now be served from a `public/` webroot for added security.  
* Versioning is more powerful through an "ownership" concept, and available for all DataObject classes.
* Changes across objects can be collected in a "campaign" for batch publication.
* GraphQL is now the favourite way of creating web services with SilverStripe.
* Asset management has been completely redone with a brand new React-based UI, protected draft files and versioning.
* Parts of the CMS UI are now built with React and Bootstrap instead of Entwine and custom CSS.
* PSR-4 auto-loading is supported for modules and for your project code.

[Learn more about major API changes introduced by SilverStripe 4](#list-of-major-api-changes),
and dig into the changelogs for [4.0.0](/changelogs/4.0.0), [4.1.0](/changelogs/4.1.0), [4.2.0](/changelogs/4.2.0), and [4.3.0](/changelogs/4.3.0).

### Using recipes instead of requiring individual modules
The SilverStripe CMS and SilverStripe Framework are becoming more modular. Many of the secondary features contained in SilverStripe CMS 3 and SilverStripe Framework 3 have been moved to separate modules.  

SilverStripe 4 introduces the concept of _recipes_. Recipes are a combination of modules to achieve a common pattern.

Read the [Switching to recipes](#switching-to-recipes) section of this guide for more information about how recipes work.

### Automating your upgrades using the SilverStripe Upgrader tool
We've developed [an upgrader tool](https://github.com/silverstripe/silverstripe-upgrader) which you can use to help
with the upgrade process. The upgrader is unlikely to completely upgrade your project, however it can take care of the most tedious part of the upgrade.
It can also be used to upgrade your existing SilverStripe 4 project to a newer minor release.

## Step 0 - Pre-requisites and background work {#step0}

Before you begin the upgrade process, make sure you meet these pre-requisites.

### Back up your files and database

* Set up your codebase in your development environment.
* Backup your database content.
* Backup your codebase (use version control if possible).

<div class="warning" markdown="1">
Never update a website on the live server. Get it working on a development copy first!
</div>

### Install composer

[Composer](http://getcomposer.org) is a tool for managing PHP dependencies. SilverStripe 4 requires composer version _1.1_ or greater. Read the [SilverStripe _Getting started_ guide](/getting_started/composer) for more details.

We recommend using `recipe-cms` in your `composer.json` file to help you keep up to date.

```json
{
    "require": {
        "silverstripe/recipe-cms": "^4"
    }
}
```

Running `composer update` will install additional dependencies, such as the `admin`, `asset-admin`, `reports`, `errorpage`, and `siteconfig` modules.

If you want more granular control over what gets installed,
check out the [recipe plugin repository](https://github.com/silverstripe/recipe-plugin)
as well as the `composer.json` files in [recipe-core](https://github.com/silverstripe/recipe-core) and
[recipe-cms](https://github.com/silverstripe/recipe-cms).

For a description on how to handle issues with pre-existing composer installs or upgrading other modules, read
through the [Step 1 - Upgrade your dependencies](#step1) section.

### Install or update the upgrader tool
Using the upgrader is not mandatory, but it can speed up the process. Although SilverStripe 4 can run in both PHP 5.6 and PHP 7, the upgrader itself requires PHP 7.1.

The upgrader is available as a phar executable. 

To install the PHAR executable:

1. [Download the upgrader as a PHAR executable](https://silverstripe.github.io/silverstripe-upgrader/upgrade-code.phar) or `wget https://silverstripe.github.io/silverstripe-upgrader/upgrade-code.phar`
2. Make the file executable `chmod +x upgrade-code.phar`
3. Move the file to a folder in your path, for example `sudo mv upgrade-code.phar /usr/local/bin/upgrade-code` 

When starting a new upgrade project, it's a good idea to check if you are using the latest release of the upgrader. Releases from 1.4 and above ship with a `self-update` command and will warn you if you are using an outdated version. If you are upgrading from a prior version, follow the regular installation instructions and override your existing executable.

You can run `upgrade-code help` to get more information about the upgrader or `upgrade-code help command-name` to information about a specific command.

Each command in the upgrader has somewhat different arguments. However, most of them accept these two options:
* `--write` which tells the upgrader to apply changes to your code base
* `--root-dir` which can be use to explicitly specify the root of your project. If this is not specified then the current working directory is assumed to be the root of the project.

<div class="info" markdown="1">
Sample upgrader commands in this guide assume your working directory is the root of your SilverStripe project. You'll need to use the `--root-dir` flag if that's not the case.
</div>

#### Install the upgrader globally with composer

You can install the upgrader globally with composer. This can make it easier to update to newer releases, however you can get dependency conflicts if you have other packages installed globally. 

To install the upgrader globally run this command.

```bash
composer global require silverstripe/upgrader
```

Add your global composer bin directory to your path. On \*nix system, this directory is normally located at `$HOME/.composer/vendor/bin`. On Windows system, this directory is normally located at `C:\Users\<COMPUTER NAME>\AppData\Roaming\Composer\vendor\bin`. You can find the exact location by running this command:
```bash
composer global config bin-dir
```

On \*nix system, the following command will add your global composer bin directory to your path if `bash` is your default shell environment:
```bash
echo 'export PATH=$PATH:~/.composer/vendor/bin/' >> ~/.bash_profile
source ~/.bash_profile
```

### Running all the upgrader commands in this guide with one line

The upgrader comes with an `all` command. This command will attempt to run all the upgrader commands in the same order as this guide. This is unlikely to work on your first try, but can be a good way to get started without going through this entire guide.

```bash
upgrade-code all --namespace="App\\Web" --psr4
```

* `--recipe-core-constraint` defines your SilverStripe release version (optional, will default to the most recent stable release).
* `--cwp-constraint` can be used instead `--recipe-core-constraint` when upgrading a CWP project.
* `--namespace` allows you to specify how your project will be namespaced (optional).
* `--psr4` allows you to specify that your project structure respects the PSR-4 standard and to automatically use sub-namespaces.
* `--skip-add-namespace` allows you to skip the `add-namespace` command.
* `--skip-reorganise` allows you to skip the `reorganise` command.
* `--skip-webroot` allows you to skip the `webroot` command.

### Branching your project

Creating a dedicated branch in your source version control system to track your upgrade work can help you manage your upgrade. If you're upgrading a big project, you should consider creating individual branches or commits for each step.

## Step 1 - Upgrade your dependencies {#step1}

The first step is to update your dependencies' constraints in your `composer.json` file to require the latest version of modules.

### Automatically upgrade dependencies with the `recompose` upgrader command

If you've installed the upgrader, you can use the `recompose` command to help you upgrade your dependencies. This command will try to:
* upgrade your PHP constraint
* upgrade core SilverStripe modules to their version 4 equivalent
* switch to recipes where possible
* find SilverStripe 4 compatible versions of third party modules.

Take for example the following SilverStripe 3 `composer.json` file.
```json
{
    "name": "app/cms-website",
    "description": "The Example website project.",
    "license": "BSD-3",
    "require": {
        "php": ">=5.3.3",
        "silverstripe/cms": "3.6.5@stable",
        "silverstripe/framework": "3.6.5@stable",
        "silverstripe/reports": "3.6.5@stable",
        "silverstripe/siteconfig": "3.6.5@stable",
        "dnadesign/silverstripe-elemental": "^1.8.0"
    }
}
```

You can upgrade the `composer.json` file with this command:
```bash
upgrade-code recompose --write
```

You can add a `--recipe-core-constraint` flag to target a specific version of `silverstripe/recipe-core`. By default, the project will be upgraded to the latest stable version. If you are upgrading a CWP project, you can use `--cwp-constraint` instead to target a specific version of `cwp/cwp-core`.

The upgrader uses [caret version constraint](https://getcomposer.org/doc/articles/versions.md#caret-version-range-) by default. This will cause composer to install compatible minor releases. You can use the `--strict` option if you want to use the more conservative [tilde version constraints](https://getcomposer.org/doc/articles/versions.md#tilde-version-range-).

Omit the `--write` flag to preview your changes.

Your upgraded `composer.json` file will look like this.
```json
{
    "name": "app/cms-website",
    "description": "The Example website project.",
    "license": "BSD-3",
    "require": {
        "dnadesign/silverstripe-elemental": "^4.0",
        "php": ">=5.6",
        "silverstripe/recipe-cms": "^4.3"
    }
}
```

If the `recompose` command can't find a compatible version for one of your modules, it will keep this dependency in your `composer.json` file with its existing constraint.

[Continue to Step 2](#step2)

### Manually upgrading your dependencies

The instructions in this section assume that you'll be editing your `composer.json` file in a text editor.

#### Switching to recipes

Where possible, we recommend you use recipes.

If your SilverStripe 3 project requires the `silverstripe/cms` module, replace that dependency with `silverstripe/recipe-cms`. The version constraint for `silverstripe/recipe-cms` must match your targeted version of SilverStripe:
* `~4.0.0` to upgrade to SilverStripe 4.0
* `~4.1.0` to upgrade to SilverStripe 4.1
* `~4.2.0` to upgrade to SilverStripe 4.2
* and so on.

If your SilverStripe 3 project requires the `silverstripe/framework` module without `silverstripe/cms`, replace `silverstripe/framework` with `silverstripe/recipe-core`.  The version constraint for `silverstripe/recipe-core` must match your targeted version of SilverStripe:
* `~4.0.0` to upgrade to SilverStripe 4.0
* `~4.1.0` to upgrade to SilverStripe 4.1
* `~4.2.0` to upgrade to SilverStripe 4.2
* and so on.

The following modules are implicitly required by `silverstripe/recipe-core`. They can be removed from your `composer.json` dependencies if you are using `silverstripe/recipe-core` or `silverstripe/recipe-cms`.
* `silverstripe/framework`
* `silverstripe/config`
* `silverstripe/assets`

The following modules are implicitly required by `silverstripe/recipe-cms`. They can be removed from your `composer.json` dependencies if you are using `silverstripe/recipe-cms`.
* `silverstripe/admin`
* `silverstripe/asset-admin`
* `silverstripe/campaign-admin`
* `silverstripe/cms`
* `silverstripe/errorpage`
* `silverstripe/reports`
* `silverstripe/graphql`
* `silverstripe/siteconfig`
* `silverstripe/versioned`
* `silverstripe/recipe-core`

Take for example the following SilverStripe 3 `composer.json`.
```json
{
    "name": "app/cms-website",
    "require": {
        "silverstripe/cms": "3.6.5@stable",
        "silverstripe/framework": "3.6.5@stable",
        "silverstripe/reports": "3.6.5@stable",
        "silverstripe/siteconfig": "3.6.5@stable"
    }
}
```

After switching to SilverStripe 4 recipes, the `composer.json` file should look like this.
```json
{
    "name": "app/cms-website",
    "require": {
        "silverstripe/recipe-cms": "~4.1.0"
    }
}
```

#### Explicitly defining your dependencies
If you would rather explicitly define your dependencies, you can do so. Update the `silverstripe/framework` constraint and `silverstripe/cms` constraint to match your targeted minor version of SilverStripe 4. If you use `silverstripe/reports` and `silverstripe/siteconfig`, update their constraints as well.

In most cases, you'll also want to require the same modules as the equivalent recipes. If you don't, your users will likely lose some features after the upgrade is completed.

Take for example the following SilverStripe 3 `composer.json`.
```json
{
    "name": "app/cms-website",
    "require": {
        "silverstripe/cms": "3.6.5@stable",
        "silverstripe/framework": "3.6.5@stable",
        "silverstripe/reports": "3.6.5@stable",
        "silverstripe/siteconfig": "3.6.5@stable"
    }
}
```

After switching to SilverStripe 4 and explicitly defining your dependencies, the `composer.json` file should look like this.
```json
{
    "name": "app/cms-website",
    "require": {
         "silverstripe/cms": "~4.1.0",
         "silverstripe/framework": "~4.1.0",
         "silverstripe/reports": "~4.1.0",
         "silverstripe/siteconfig": "~4.1.0",
         "silverstripe/admin": "~1.1.0",
         "silverstripe/asset-admin": "~1.1.0",
         "silverstripe/campaign-admin": "~1.1.0",
         "silverstripe/errorpage": "~1.1.0",
         "silverstripe/graphql": "~1.1.0",
         "silverstripe/versioned": "~1.1.0"
    }
}
```

#### Updating third party dependencies
If you project requires third party modules, you'll need to adjust their associated constraint. This will allow you to retrieve a SilverStripe 4 compatible version of the module.

[Look up the module on Packagist](https://packagist.org/) to see if a SilverStripe 4 version is provided.

Take for example the following SilverStripe 3 `composer.json`.
```json
{
    "name": "app/cms-website",
    "require": {
        "silverstripe/framework": "3.6.5@stable",
        "silverstripe/cms": "3.6.5@stable",
        "dnadesign/silverstripe-elemental": "^1.8.0"
    }
}
```

Looking at the [Packagist entry for `dnadesign/silverstripe-elemental`](https://packagist.org/packages/dnadesign/silverstripe-elemental#2.0.0), you can see that versions 2.0.0 and above of this module are compatible with SilverStripe 4. So you can update that constraint to `^2.0.0`.

Alternatively, you can set a very permissive constraint and let composer find a SilverStripe 4 compatible version. After you're done updating your dependencies, make sure you adjust your constraints to be more specific.

Once you've updated your third-party modules constraints, try updating your dependencies by running `composer update`. If composer can't resolve all your dependencies it will throw an error.

### Resolving conflicts

You'll likely have some conflicts to resolve, whether you've updated your dependencies with the upgrader or manually.

Running a `composer update` will tell you which modules are conflicted and suggested alternative combinations of modules that might work.

The most typical reason for a conflict is that the maintainer of a module hasn't released a version compatible with SilverStripe 4.

If the maintainer of the module is in the process of upgrading to SilverStripe 4, a development version of the module might be available. In some cases, it can be worthwhile to look up the repository of the module or to reach out to the maintainer.

<div class="info" markdown="1">
If you're going to install development version of third party modules, you should consider adding the following entries to your `composer.json` file.

```json
{
  // ...
  "minimum-stability": "dev",
  "prefer-stable": true,
  // ...
} 
```
</div>

To resolve a conflict you can do any of the following:

#### Upgrade the module manually

In some cases a module may not be available for SilverStripe 4 yet.
You can work around this issue by upgrading the module yourself.

First integrate the affected module into your project's codebase:

1. Remove the module from your dependencies: `composer remove <package>` or through `composer.json`.
2. Recompose again, this should now work without the conflicting module: `bin/upgrade-code recompose`
3. Clone the module repository into your project root: `git clone <repository>`

Then fork the affected module and upgrade it yourself:

1. Fork the repository: `git fork --remote-name <remote>`
2. Upgrade the module so it works with version `4` of SilverStripe, commit and push your changes to your forked repository.

<div class="info" markdown="1">
If you're taking the time to upgrade a third party module, consider doing a pull request against the original project so other developers can benefit from your work or releasing your fork as a separate module.

[Learn about how to publish a SilverStripe module](/developer_guides/extending/how_tos/publish_a_module)

Once your changes have been merged you can remove your forked module and add the dependency through composer again with: `composer require <package>`
</div>

#### Remove the module from your project, if it is not essential
You can remove the module completely if you do not need it.
This can be done simply by removing the dependency: `composer remove <package>`

### Finalising your dependency upgrade

Once you've resolved all conflicts in your `composer.json` file, `composer update` will be able to run without errors.

This will install your new dependencies. You'll notice many of the folders in the root of your project will disappear. That's because SilverStripe 4 modules can be installed in the vendor folder like generic PHP packages.

If you've decided to use recipes, some generic files will be copied from the recipe into your project. The `extra` attribute in your `composer.json` file will be updated to keep track of those new files.

This is a good point to commit your changes to your source control system before moving on to the next step.  

## Step 2 - Update your environment configuration {#env}{#step2}

The php configuration `_ss_environment.php` file has been replaced with a non-executable
`.env` file. It follows a syntax similar to a `.ini` file for key/value pair assignment. Your `.env` file may be placed in your project root, or one level above your project root.

Read the [Environment management](/getting_started/environment_management/) documentation to learn more about configuring your project's environment.

### Automatically convert `_ss_environment.php` to `.env`

If you have installed the upgrader tool, you can use the `environment` command to generate a valid `.env` file from your existing `_ss_environment.php` file.

```bash
upgrade-code environment --write
```

If your `_ss_environment.php` file contains unusual logic (conditional statements or loops), you will get a warning. `upgrade-code` will still try to convert the file, but you should double-check the output. Omit the `--write` flag to do a dry-run.

[Continue to "Cleaning up `mysite/_config.php`"](#env-config-cleanup)

### Manually convert `_ss_environment.php` to `.env`

Create a `.env` file in the root of your project. Replace `define` statements from `_ss_environment.php` with `KEY=VALUE` pairs in `.env`.

Most SilverStripe 3 environment variables have been carried over to SilverStripe 4. See [Environment Management docs](/getting_started/environment_management/) for the full list of available variables. Your `.env` file can contain environment variables specific to your project as well.

The global array `$_FILE_TO_URL_MAPPING` has been removed and replaced with the `SS_BASE_URL` environment variable. `SS_BASE_URL` expects an absolute url with an optional protocol. The following are values would be valid entries for `SS_BASE_URL`:
* `http://localhost/`
* `https://localhost/`
* `//localhost/`                                                                                                        

For example, take the following `_ss_environment.php` file.
```php
<?php
// Environment
define('SS_ENVIRONMENT_TYPE', 'dev');
define('SS_DEFAULT_ADMIN_USERNAME', 'admin');
define('SS_DEFAULT_ADMIN_PASSWORD', 'password');
$_FILE_TO_URL_MAPPING[__DIR__] = 'http://localhost';

// Database
define('SS_DATABASE_CHOOSE_NAME', true);
define('SS_DATABASE_CLASS', 'MySQLDatabase');
define('SS_DATABASE_USERNAME', 'root');
define('SS_DATABASE_PASSWORD', '');
define('SS_DATABASE_SERVER', '127.0.0.1');
```

The equivalent `.env` file will look like this.
```bash
## Environment
SS_ENVIRONMENT_TYPE="dev"
SS_DEFAULT_ADMIN_USERNAME="admin"
SS_DEFAULT_ADMIN_PASSWORD="password"
SS_BASE_URL="http://localhost/"

## Database
SS_DATABASE_CHOOSE_NAME="true"
SS_DATABASE_CLASS="MySQLDatabase"
SS_DATABASE_USERNAME="root"
SS_DATABASE_PASSWORD=""
SS_DATABASE_SERVER="127.0.0.1"
```

### Cleaning up `mysite/_config.php` after your environment configuration upgrade {#env-config-cleanup}

Regardless if you've used the automated or manual path,
you'll need to clean up your `mysite/_config.php` file after upgrading your environment file.

The global values `$database` and `$databaseConfig` have been deprecated. Your database configuration details should be stored in your `.env` file. If you want to keep your database configuration in `_config.php`, you can use the new `DB::setConfig()` api, however this is discouraged.

Requiring `conf/ConfigureFromEnv.php` is is no longer necessary. You should remove any references to it in `_config.php`.

The removal of the `_ss_environment.php` file means that conditional logic is no longer available in the environment
variable set-up process. This encouraged bad practice and should be avoided. If you still require conditional logic early in the bootstrap, this is best placed in the `_config.php` files.

To access environment variables, use the `SilverStripe\Core\Environment::getEnv()` method. To define environment variables, use the `SilverStripe\Core\Environment::setEnv()` method.

### Finalising your environment upgrade
It's inadvisable to track your `.env` file in your source control system as it might contain sensitive information.

You should ignore the `.env` file by adding an entry to your `.gitignore` file. You can create a sample environment configuration by duplicating your `.env` file as `.env.sample`, and removing sensitive information from it. You can safely delete your legacy `_ss_environment.php` if you want.

This is a good point to commit your changes to your source control system before moving on to the next step.  

## Step 3 - Namespacing your project (optional) {#step3}

Namespacing your code is an optional step. It is recommended and will help future-proof your code base.
Read more about [PHP Namespaces](http://php.net/manual/en/language.namespaces.php)
and the [PSR-4 Autoloader Standard](https://www.php-fig.org/psr/psr-4/).

### Before you start namespacing your codebase

You need to choose a root namespace for your project. We recommend following the `Vendor\Package` pattern.
The `Page` and `PageController` classes *must* be defined in the global namespace (or without a namespace).

If you want your codebase to comply with the PSR-4 standard, make sure sub-directories of your code folder are using the _UpperCamelCase_ naming convention. For example, `mysite/code/page_types` should be renamed to `mysite/code/PageTypes`.

### Automatically namespacing your codebase with the upgrader

The `add-namespace` command of the [upgrader tool](https://github.com/silverstripe/silverstripe-upgrader/) provides a feature
to namespace your codebase and to automatic update references to those classes.

```bash
upgrade-code add-namespace "App\\Web" ./mysite/code --recursive --write
```

This task will do the following:
* Add the given namespace to all files in the code folder, and subdirectories
* All references to classes in any namespaced files will be safely retained with additional `use` directives added as necessary
* A `mysite/.upgrade.yml` file will be created/updated to record the new fully qualified name of each class. 
  This will be used in later steps to update references to the outdated class names in your own project code.

By default, the same namespace will be applied to all your classes regardless of which directory they are in. If you want to apply different namespaces to different folders to be compliant with PSR-4, combine the `--recursive` option with the `--psr4` option. Your folder structure must be PSR-4 compliant for this to work. If you want to do a dry-run, omit the `--write` option to see a preview of all changed project files.

[Continue to Step 4](#step4)

### Manually namespacing your codebase

Go through each PHP file under `mysite/code` and add a `namespace` statement at the top, *with the exception of the files for `Page` or `PageController`*.

Take for example this SilverStripe 3 file located at `mysite/code/Products/ExplosiveTennisBall.php`.
```php
<?php

class ExplosiveTennisBall extends DataObject
{
    // ...
}
```

Assuming your root namespace is `App\Web`, the equivalent namespaced file will look like this.
```php
<?php
namespace App\Web\Products;

class ExplosiveTennisBall extends DataObject
{
    // ...
}
```

If you intend to use the upgrader to update references to your namespaced classes, you'll need to create a `mysite/.upgrade.yml` file.
```yaml
mappings:
  ExplosiveTennisBall: App\Web\Products\ExplosiveTennisBall
```

If you intend to manually update references to your namespaced classes, you'll need to go through each of your file to add `use` statements.

For example, if `mysite/code/ProductService.php` is using the `ExplosiveTennisBall` class, you'll need to add a use statement at the top of the file just after it's own namespace definition.
```php
<?php
namespace App\Web;

use App\Web\Products\ExplosiveTennisBall;

class ProductService
{
    // ...
}
```

### Enable PSR-4 auto-loading in your `composer.json` file
If you have namespaced your project and followed the PSR-4 convention, you have the option to enable PSR-4 auto-loading in your composer.json file.
Enabling PSR-4 auto-loading is optional. It will provide better auto-loading of your classes in your development environment and will future proof your code.

For example, let's say you have defined the following namespaces for the following folders:
* `App\Web` for your main application logic contained in `mysite/code`
* `App\SubModule` for a secondary module contained in `sub-module/code`
* `App\Web\Tests` for your application test suite contained in `mysite/tests`.

Your `autoload` section in your `composer.json` file should look like this:
```json
{
    // ...
    "autoload": {
        "psr-4": {
            "App\\Web\\": "mysite/code",
            "App\\SubModule\\": "sub-module/code"
        }
    },
    "autoload-dev": {
        "psr-4": {
            "App\\Web\\Tests\\": "mysite/tests"
        }
    },
    // ...
}
```

Read the [Composer schema autoload documentation](https://getcomposer.org/doc/04-schema.md#autoload) for more information about configuring auto-loading in your project.

### Finalise your project namespacing
All your classes should now be fully namespaced.

Note that applying a namespace to your project will also affect which template file SilverStripe tries to load when rendering certain objects.

For example, pretend you have a `RoadRunnerPage` class that extends `Page`. In SilverStripe 3, you would define a template for this page in `themes/example/templates/Layout/RoadRunnerPage.ss`. If you decide to move `RoadRunnerPage` to `App\Web\RoadRunnerPage`, you'll need to move the template to `themes/example/templates/App/Web/Layout/RoadRunnerPage.ss`.

This is a good point to commit your changes to your source control system before moving on to the next step.

## Step 4 - Update codebase with references to newly namespaced classes {#step4}

All core PHP classes in SilverStripe 4 have been namespaced. For example, `DataObject` is now called `SilverStripe\ORM\DataObject`. Your project codebase, config files and language files need be updated to reference those newly namespaced classes. This will include explicit references in your PHP code, but also string that contain the name of a class.
If you've opted to namespace your own code in the previous step, those references will need to be updated as well.

### Automatically update namespaced references with the `upgrade` command

If you've installed the upgrader, you can use the `upgrade` command to update references to namespaced classes.

The `upgrade` command will update PHP files, YML configuration files, and YML language files.

Each core SilverStripe 4 module includes a `.upgrade.yml` that defines the equivalent fully qualified name of each class. Most third party SilverStripe modules that have been upgraded to be compatible with SilverStripe 4, also include a `.upgrade.yml`. If you've namespaced your own project, you'll need to provide your own `.upgrade.yml` file . If you've used the upgrader to namespace your project, that file will have been created for you.

The `upgrade` command will try to update some strings that reference the old name of some classes. In some cases this might not be what you want. You can tell the upgrader to skip specific strings by using the `@skipUpgrade` flag in your PHPDoc comment. For example:  

```PHP
/** @skipUpgrade */
return Injector::inst()->get('ProductService');
```

Execute the upgrade command with this command.

```bash
upgrade-code upgrade ./mysite/ --write
```

If you omit the `--write` flag you will get a preview of what change the upgrader will apply to your codebase. This can be helpful if you are tweaking your `.upgrade.yml` or if you are trying to identify areas where you should add a `@skipUpgrade` statement,

You can also tweak which rules to apply with the `--rule` flag: `code`, `config`, and `lang`. For example, the following command will only upgrade `lang` and `config` files:
```bash
upgrade-code upgrade ./mysite/ --rule=config --rule=lang
```

The `upgrade` command can alter big chunks of your codebase. While it works reasonably well in most use cases, you should not trust it blindly. You should take time to review all changes applied by the `upgrade` command and confirm you are happy with them.

[Continue to "Finalising namespace updates"](#namespace-finalise)

#### Rename Warnings

You can also show extra warnings for potentially ambiguous mappings with the `renameWarnings` property:

```yaml
renameWarnings:
  - File
  - Image
```

An example of an ambiguous rename would be:
```PHP
private static $has_one = [
    'Image' => 'Image',
];
```

Add the `--prompt` flag to manually approve ambiguous class renames.

### Manually update namespaced references

If you decide to update your namespace references by hand, you'll need to go through the entire code base and update them all from the old non-namespaced SilverStripe classes to the new namespaced equivalent. If you are referencing classes from third party modules that have been namespaced, you'll need to update those as well.

#### Update explicit references to classes in your code

Wherever your code explicitly references a SilverStripe class, it will need to be updated to the new namespaced equivalent. You can either update the reference to use the fully qualified name of the class or you can add a `use` statement to your file.

For example take the following SilverStripe 3 class. `DataObject` and `FieldList` need to point to their namespace equivalents.

```php
<?php
namespace App\Web\Products;

class ExplosiveTennisBall extends DataObject
{

    public function getCMSFields()
        {
           return FieldList::create([]);
        }

}
```

You can add `use` statements at the top of your file to reference the fully qualified name of `DataObject` and `FieldList`.

```php
<?php
namespace App\Web\Products;

use SilverStripe\ORM\DataObject;
use SilverStripe\Forms\FieldList;

class ExplosiveTennisBall extends DataObject
{
// ...
```

Alternatively, you can update the references to the fully qualified names.
```php
<?php
namespace App\Web\Products;

class ExplosiveTennisBall extends SilverStripe\ORM\DataObject
{

    public function getCMSFields()
        {
           return SilverStripe\Forms\FieldList::create([]);
        }

}
```

#### Update string references to classes

In many cases, SilverStripe expects to be provided the name of a class as a string. Typical scenarios include:
* defining an `has_one` or `has_many` relationship on a DataObject
* requesting an instance of class via the Injector
* specifying managed models for a `ModelAdmin`.

Those string need to use the fully qualified name of their matching classes. Take for example the following class.

```php
<?php
namespace App\Web\Products;

use SilverStripe\ORM\DataObject;

class ExplosiveTennisBall extends DataObject
{
    private static $has_one = [
        'Thumbnail' => 'Image'
    ];

    private static $has_many = [
        'Tags' => 'BlogPost'
    ];


    public function getShippingCost()
    {
        return Injector::inst('ProductService')->calculateCost($this);
    }
}
```

`Image`, `BlogPost`, and `ProductService` represent classes. Those strings need to be updated to specify the full namespace.

The best way of achieving this is to use the [`::class` PHP magic class constant](http://php.net/manual/en/language.oop5.basic.php#language.oop5.basic.class.class) which will return the fully qualified name of a class.

Our example could be update to:
```php
<?php
namespace App\Web\Products;

use SilverStripe\ORM\DataObject;
use SilverStripe\Assets\Image;
use SilverStripe\Blog\Model\BlogPost;
use App\Web\ProductService;

class ExplosiveTennisBall extends DataObject
{
    private static $has_one = [
        'Thumbnail' => Image::class
    ];

    private static $has_many = [
        'Tags' => BlogPost::class
    ];


    public function getShippingCost()
    {
        return Injector::inst(ProductService::class)->calculateCost($this);
    }
}
```

Alternatively, you can spell out the full qualified name of each class in a string. For example, `'Image'` would become `'SilverStripe\\Assets\\Image'`. Note the use of the _double backslash_ — this is necessary because the backslash is an escape character.

#### Update references to classes in your YML config

YAML configuration files can reference SilverStripe classes. Those references also need to use the fully qualified name of each class.

Take for example the following SilverStripe 3 YAML configuration file.

```yaml
Injector:
  ProductService:
    properties:
      RoadRunnerSpeed: 99999999
      CoyoteSpeed: 1

BlogPost:
  extensions:
    - HasOneExplosiveTennisBallExtension

Email:
  admin_email: no-reply@example.com
```

In SilverStripe 4, this will become:
```yaml
SilverStripe\Core\Injector\Injector:
  App\Web\ProductService:
    properties:
      RoadRunnerSpeed: 99999999
      CoyoteSpeed: 1

SilverStripe\Blog\Model\BlogPost:
  extensions:
    - App\Web\Extensions\HasOneExplosiveTennisBallExtension

SilverStripe\Control\Email\Email:
  admin_email: no-reply@example.com
```

#### Update references to classes in your language files

Translation keys are normally tied to classes. If you override SilverStripe's default translation or if you are localising your own project, you'll need to update those references to use the fully qualified name of each class.

For example, let's say you had the following translation file in `mysite/lang/eng.yml`.
```yaml
en:
  Member:
    SINGULARNAME: Coyote
  RoadRunner:
    SALUTATION: Beep Beep
```

In SilverStripe 4, it would become:
```yaml
en:
  SilverStripe\Security\Member:
    SINGULARNAME: Coyote
  App\Web\RoadRunner:
    SALUTATION: Beep Beep
```

### Finalising namespace updates {#namespace-finalise}
You'll need to perform the following steps manually, even if you've used the automated rewrite of namespaces.

DataObject database tables will default to use a namespaced name. For example, if you have a class under `App\Web\Products\ExplosiveTennisBall` that extends `DataObject`, the matching table in your database will be called `App_Web_Products_ExplosiveTennisBall`.
You can define a `private static $table_name` property on your DataObjects to use more convenient table names.
For example, `private static $table_name = 'ExplosiveTennisBall';`.

In your PHP code, calls to the `_t()` method should be updated to use the full namespace of the target class.

```php
<?php

# Old SilverStripe 3 way
$translation = _t('CMSMain.ACCESS', "Access to ''{title}'' section", ['title' => 'Products']);

# New SilverStripe 4
use SilverStripe\CMS\Controllers\CMSMain;
// ...
$translation = _t(CMSMain::class . '.ACCESS', "Access to '{title}' section", ['title' => 'Products']);
```

If you're calling `_t()` to retrieve a translation for the current class, you can also use `__CLASS__` or `self::class`. For example:
```php
<?php
namespace App\Web\Services;

class ProductService
{
    public function getTranslation()
    {
        # Those two lines are equivalent.
        $translation = _t(__CLASS__ . '.PRODUCT', 'Product');
        $translation = _t(self::class . '.PRODUCT', 'Product');
        return $translation;
    }
}
```

<div class="warning" markdown="1">
Avoid using `static::class` or `parent::class` to retrieve translated string. It will retrieve unpredictable values bases on the class inheritance. 
</div>

If your template files contain translatable strings, they also need to be updated to referenced the namespaced classes.
For example, `<%t Member.SINGULARNAME 'Member' %>` would become `<%t SilverStripe\Security\Member.SINGULARNAME 'Member' %>`.

Your codebase should now be referencing valid SilverStripe 4 classes. This means that your classes can be loaded at runtime. However, your codebase will still be using an outdated API.
This is a good point to commit your changes to your source control system before moving on to the next step.

## Step 5 - Updating your codebase to use SilverStripe 4 API {#step5}

This is the most intricate and potentially time-consuming part of the upgrade. It involves going through your entire codebase to remove references to deprecated APIs and update your project logic.

### Automatically update deprecated API references with the `inspect` command

The upgrader has an `inspect` command that can flag deprecated API usage, and in some cases, update your codebase to the SilverStripe 4 equivalent. This does require you to carefully review each change and warning to make sure the updated logic still work as intended. Even so, it is a huge time-saver compared to reviewing your code base manually.

Note that the `inspect` command loads your files with PHP interpreter. So basic syntax errors — for example, extending a class that does not exists — will cause an immediate failure. For this reason, you need to complete [Step 4 - Update codebase with references to newly namespaced classes](#step4) before running the `inspect` command.

```bash
upgrade-code inspect ./mysite/ --write
```

You can omit the `--write` flag if you just want to view the proposed changes without applying them. You can run the command on a specific subdirectory or file. This can be more manageable if you have a big project to go through.

Like the `upgrade` command, `inspect` gets its list of API changes from `.upgrade.yml` files. So you may get upgrade suggestions and substitution from third party modules. You can even include your own project specific changes in your `.upgrade.yml` if you want.

#### Sample output of the `inspect` command
Here's some sample output of what you might get back from the `inspect` command.

```bash
upgrade-code inspect ./mysite/Models/Coyote.php

Running post-upgrade on "/var/www/SS_example/mysite/code/Models/Coyote.php"
[2018-06-06 13:35:38] Applying ApiChangeWarningsRule to Coyote.php...
modified:	Coyote.php
@@ -68,7 +68,7 @@
     {
         // Getting a reference to Coyote's list of crazy ideas
-        $manyManyRelation = $this->manyManyComponent('CrazyIdeas');
+        $manyManyRelation = $this->getSchema()->manyManyComponent('CrazyIdeas');
         return $manyManyRelation;
     }

Warnings for Coyote.php:
 - Coyote.php:20 SS_Cache: Using symfony/cache now (https://docs.silverstripe.org/en/4/changelogs/4.0.0#cache)
 - Coyote.php:42 SilverStripe\Control\Director::setUrlParams(): Method removed
 - Coyote.php:71 SilverStripe\ORM\DataObject->manyManyComponent(): DataObject->manyManyComponent() moved to DataObjectSchema. Access through getSchema(). You must manually add static::class as the first argument to manyManyComponent()
Changes not saved; Run with --write to commit to disk
```

### Manually update deprecated API references

SilverStripe 4 introduces many API changes. To update deprecated API references manually, you have to go through each one of your project files.
Read the changelogs for [4.0.0](/changelogs/4.0.0/) and for [subsequent minor releases](/changelogs)

### Finalising the deprecated API update
At this stage, your site should be using only SilverStripe 4 API logic.

You still have some minor clean up tasks and configuration tweaks to apply, but you're almost done.

This is a good point to commit your changes to your source control system before moving on to the next step.

## Step 6 - Update your entry point {#step6}
The location of SilverStripe's _entry file_ has changed. Your project and server environment will need
to adjust the path to this file from `framework/main.php` to `index.php`.

### Update your `index.php` file
You can get a copy of the SilverStripe 4 `index.php` file at
`vendor/silverstripe/recipe-core/public/index.php`.

If you've created your own `index.php` or modified version of `main.php`,
you'll need to reconcile those changes with the `index.php` file you got from `recipe-core`.
Otherwise, just use the generic `index.php` file `recipe-core` provides.

Copy your new `index.php` to your project's web root. Unlike SilverStripe 3, `index.php` must be present in your web root.

### Update your server configuration
If you're using a `.htaccess` file or `web.config` file to handle your server configuration, you can get the generic SilverStripe 4 version of those file from
`vendor/silverstripe/recipe-core/public`.

Just like `index.php`, if you've modified your server configuration file from the one that shipped with SilverStripe 3, you'll need to reconcile your changes into the version retrieve from `recipe-core`.

[Refer to the installation instruction for your platform](/getting_started/installation/) if your server configuration is not managed via a `.htaccess` or `web.config` file.

### Finalising the entry point upgrade

At this stage, you could in theory run your project in SilverStripe 4.

This is a good point to commit your changes to your source control system before moving on to the next step.

## Step 7 - Update project structure (optional) {#step7}
SilverStripe 4 introduces a new recommended project structure ([details](/changelogs/4.2.0#app-folder-name)).
Adopting the recommended project structure is optional, but will become mandatory in SilverStripe 5.

[Skip to Step 8](#step8)

### Automatically switch to the new structure with the `reorganise` command
The reorganise command can automatically update your project to use the new recommended structure.

It will search your code and find any occurrence of `mysite`. It won't replace those occurrences with `app` however.

```bash
upgrade-code reorganise --write
```

Omit the `--write` flag if you just want to preview your changes

### Manually switch to the new structure

Simply rename your `mysite` fold to `app`. Then rename `app/code` to `app/src`.

### Finalising the reorganise structure

If you've implemented the new PSR-4 auto-loading logic in your `composer.json` file you'll need to update your namespace mapping.

For example, let's say you had the following autoload attribute in your `composer.json`.
```json
{
    // ...
    "autoload": {
        "classmap": [
            "mysite/code/Page.php",
            "mysite/code/PageController.php"
        ],
        "psr-4": {
            "App\\Web\\": "mysite/code/"
        }
    },
    // ...
}
```

It will become this:
```json
{
    // ...
    "autoload": {
        "classmap": [
            "app/src/Page.php",
            "app/src/PageController.php"
        ],
        "psr-4": {
            "App\\Web\\": "app/src/"
        }
    },
    // ...
}
```

You'll need to update the `project` attribute for your `ModuleManifest` in your `app/src/mysite.yml` file. It should now look something like this:
```yaml  
SilverStripe\Core\Manifest\ModuleManifest:
  project: app
```

At this stage, your project should be functional with the recommended project structure.
Note that if you've explicitly referenced any static assets (images, css, js) under `mysite`, you'll need to rewrite those references.

This is a good point to commit your changes to your source control system before moving on to the next step.

## Step 8 - Switch to public web-root (optional){#step8}

SilverStripe 4.1 introduces the concept of _public web-root_ this allows you to move
all publicly accessible assets under a `public` folder ([details](/changelogs/4.1.0#public-folder)).
This has security benefits as it minimises the possibility that files that are not meant to be access directly get accidentally exposed.

This step is optional and requires SilverStripe 4.1 or greater. It will become mandatory in SilverStripe 5.

[Skip to Step 9](#step9)

### Automatically switch to the public web root

The `webroot` upgrader command will automatically move your files for you.

```bash
upgrade-code webroot --write
```

Omit the `--write` flag if you want to preview the change.

If you are using a modified `index.php`, `.htaccess`, or `web.config`, you will get a warning.

### Manually switch to using the public web root
* Create a `public` folder in the root of your project
* Move the following files and folder to your new public folder
  * `index.php`
  * `.htaccess`
  * `webconfig.php`
  * `assets`
  * Any `favicon` files
  * Other common files that should be accssible in your project webroot (example: `robots.txt`)
* Delete the root `resources` or `_resources` directories if present.
* Run the following command `composer vendor-expose` to make static assets files accessible via the `public` directory.

If you are upgrading from SilverStripe 4.0 to SilverStripe 4.1 (or above), you'll need to update `index.php` before moving it to the public folder. You can get a copy of the generic `index.php` file from `vendor/silverstripe/recipe-core/public`. If you've made modifications to your `index.php` file, you'll need to replicate those into the new `public/index.php` file.

### Finalising the web root migration
You'll need to update your server configuration to point to the public directory rather than the root of your project.
Update your `.gitignore` file so `assets` and `_resources` (or `resources` if using a pre SilverStripe 4.4 release) are still ignored when located under the `public` folder.
Your project should still be functional, although you may now be missing some static assets.

This is a good point to commit your changes to your source control system before moving on to the next step.

## Step 9 - Move away from hardcoded paths for referencing static assets {#step9}

SilverStripe 4 introduces a new way to reference static assets like images and css. 
This enables innovations like moving the SilverStripe module [vendor folder](/changelogs/4.0.0#vendor-folder) or the [public web root](/changelogs/4.1.0#public-folder).

This change is mandatory if you've completed either
[step 7](#step7) (update project structure) or [step 8](#step8) (switch to public web-root).
If you have skipped these steps, it is strongly recommended, but not mandatory.

### Exposing your project static assets
If you have folders under `app` or `mysite` that need to be accessible for your project's web root, you need to say so in your `composer.json` file by adding an entry under `extra.expose`.

For example, let's say you have `scripts`, `images` and `css` folders under `app`. You can expose them by adding this content to your `composer.json` file:
```json
{
    // ...
    "extra": {
        "branch-alias": {
            "4.x-dev": "4.2.x-dev"
        },
        "expose": [
            "app/scripts",
            "app/images",
            "app/css"
        ]
    },
    // ...
}
```

For the change to take affect, run the following command: `composer vendor-expose`.

### Referencing static assets in your PHP code
Wherever you would have use a hardcoded path, you can now use the `path/to/file.css` syntax. To reference a static file from a module, prefix the path with the module's name (e.g.: `silverstripe/admin:client/dist/js/bundle.js`).

To add some javascript and css files to your requirements from your PHP code, you could use this syntax:
```php
use SilverStripe\View\Requirements;

# Load your own style and scripts
Requirements::css('app/css/styles.css');
Requirements::script('app/scripts/client.css');

# Load some assets from a module.  
Requirements::script('silverstripe/blog: js/main.bundle.js');
```

You can `SilverStripe\Core\Manifest\ModuleResourceLoader` to get the web path of file.
```php
ModuleResourceLoader::singleton()->resolveURL('app/images/road-runner.jpg');
ModuleResourceLoader::singleton()->resolveURL('silverstripe/blog: js/main.bundle.js');
```

You can use `SilverStripe\View\ThemeResourceLoader` to access files from your theme:
```php
$cssResourcePath = ThemeResourceLoader::inst()->findThemedResource('css/layout.css');
$relativeUrl = ModuleResourceLoader::singleton()->resolveURL($cssResourcePath);
```

For classes that expect icons, you can specify them with:
```php
class ListingPage extends \Page
{
    private static $icon = 'app/images/sitetree_icon.png';
}

class MyCustomModelAdmin extends \SilverStripe\Admin\ModelAdmin
{
    private static $menu_icon = 'app/images/modeladmin_icon.png';
}
```

### Referencing static assets in template files
SilverStripe template files accept a similar format for referencing static assets. You will need to go through your assets files and remove hardcoded references.

```html
<img src="$resourceURL(app/images/coyote.png)" />
<% require css("app: css/styles.css") %>
```

### Finalising removal of hardcoded paths for referencing static assets

All your assets should be loading properly now.

This is a good point to commit your changes to your source control system before moving on to the next step.


## Step 10 - Update database class references {#step10}

If you've updated your class names to use namespaces you will need to reflect those changes in any existing database fields. For example, if you've renamed your `HomePage` class to `App\HomePage` then the database `ClassName` column needs to be updated to point to the `App\HomePage` class, otherwise the CMS will tell you that the page is obsolete. This also applies to polymorphic relationships.

There is no automated way to do this, but you can use the list generated in .upgrade.yml and copy it to `app/_config/legacy.yml`, removing any classes that don't extend DataObject.

```
SilverStripe\ORM\DatabaseAdmin:
  classname_value_remapping:
    HomePage: App\HomePage
```

This will automatically update affected columns when you first build the database.


## Step 11 - Running your upgraded site for the first time {#step11}

You're almost across the finish line.  

### Run a dev build
Run a `dev/build` either on the command line or in your browser.

```bash
./vendor/bin/sake dev/build
```

This should migrate your existing data (non-destructively) to the new SilverStripe 4 structure.

#### Migrating files

Since the structure of the `File` DataObject has changed, a new task `MigrateFileTask`
has been added to assist in migration of legacy files (see [file migration documentation](/developer_guides/files/file_migration)).

```bash
./vendor/bin/sake dev/tasks/MigrateFileTask
```

### Any other script that needs running.

Some third party modules may include their own migration tasks. Take a minute to consult the release notes of your third party dependencies to make sure you haven't missed anything.
