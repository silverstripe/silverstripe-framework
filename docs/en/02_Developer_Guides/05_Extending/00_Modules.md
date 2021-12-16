---
title: Modules
summary: Extend core functionality with modules.
icon: code
---

# Modules

Silverstripe CMS is designed to be a modular application system - even the CMS is simply a module that plugs into the core
framework.

A module is a collection of classes, templates, and other resources that is loaded into a directory.
Modules are [Composer packages](https://getcomposer.org/), and are placed in the `vendor/` folder.
These packages need to contain either a toplevel `_config` directory or `_config.php` file,
as well as a special `type` in their `composer.json` file ([example](https://github.com/silverstripe/silverstripe-module/blob/4/composer.json)).

Like with any Composer package, we recommend declaring your PHP classes through
[PSR autoloading](https://getcomposer.org/doc/01-basic-usage.md#autoloading).
Silverstripe CMS will automatically discover templates and configuration settings
within your module when you next flush your cache.


## Finding Modules

* [Official module list on silverstripe.org](http://addons.silverstripe.org/)
* [Packagist.org "silverstripe" tag](https://packagist.org/search/?tags=silverstripe)
* [GitHub.com "silverstripe" search](https://github.com/search?q=silverstripe)

## Installation

Modules are installed through the [Composer](http://getcomposer.org) package manager. It
enables you to install modules from specific versions, checking for compatibilities between modules and even allowing
to track development branches of them. To install modules using this method, you will first need to setup Silverstripe CMS
with [Composer](../../getting_started/composer).

Each module has a unique identifier, consisting of a vendor prefix and name. For example, the "blog" module has the
identifier `silverstripe/blog` as it is published by *silverstripe*. To install, use the following command executed in
the root folder:

```bash
composer require silverstripe/blog *@stable
```

This will fetch the latest compatible stable version of the module. To install a specific version of the module give the
tag name.

```bash
composer require silverstripe/blog 1.1.0
```

Composer is using [version constraints](https://getcomposer.org/doc/articles/versions.md).
To lock down to a specific version, branch or commit, read up on
["lock" files](http://getcomposer.org/doc/01-basic-usage.md#composer-lock-the-lock-file).

[notice]
After you add or remove modules, make sure you rebuild the database, class and configuration manifests by going to http://yoursite.com/dev/build?flush=1
[/notice]

## Creating a Module {#create}

Creating a module is a good way to re-use code and templates across multiple projects,
or share your code with the community. Silverstripe CMS already
has certain modules included, for example the `cms` module and core functionality such as commenting and spam protection
are also abstracted into modules allowing developers the freedom to choose what they want.

### Create a new directory

The easiest way to get started is our [Module Skeleton](https://github.com/silverstripe/silverstripe-module).

First, create a new directory named after your intended module in your main project. It should sit alongside the other modules
such as *silverstripe/framework* and *silverstripe/cms* and use it for the module development:

`mkdir /vendor/my_vendor/nice_feature`

Then clone the Module Skeleton to get a headstart with the module files:

```bash
cd /vendor/my_vendor/nice_feature
git clone git@github.com:silverstripe/silverstripe-module.git .
```

### Allow your module to be importable by composer

You need to set your module up to be importable via composer. For this, edit the new `composer.json` file in the root of
your module. Here is an example for a module that builds on the functionality provided by the `blog` main module (hence the
requirement):

```json
{
	"name": "my_vendor/nice_feature",
	"description": "Short module description",
	"type": "silverstripe-vendormodule",
	"require": {
		"silverstripe/cms": "^4.0",
		"silverstripe/framework": "^4.0",
		"silverstripe/blog": "^4@dev"
	}
}
```

After your module is running and tested, you can publish it. Since your module is a self-contained piece of software, it
will constitute a project in itself. The below assumes you are using GitHub and have already created a new GitHub repository for this module.

Push your module upstream to the empty repository just created:

```bash
	git init
	git add -A
	git commit -m 'first commit'
	git remote add origin git@github.com:my_vendor/nice_feature.git
	git push -u origin master
```

Once the module is pushed to the repository you should see the code on GitHub. From now on it will be available for
others to clone, as long as they have access (see the note below though: private modules are not deployable).

### Including a private module in your project

Including public or private repositories that are not indexed on **Packagist** is different from simply using the `composer require silverstripe/blog` command. We will need to point *composer* to specific URLs. Background information can be found at
[Working with project forks and unreleased
modules](../../getting_started/composer/#working-with-project-forks-and-unreleased-modules).

For our *nice_module* example module we have just pushed upstream and can add the following lines to your `composer.json` file in the root directory of your main project.

```json
	"repositories": [
		{
			"type": "vcs",
			"url": "git@github.com:my_vendor/nice_feature.git",
		}
	]
```

This will add the repository to the list of URLs composer checks when updating the project dependencies. Hence you can
now include the following requirement in the same `composer.json`:

```
	"require": {
		...
		"my_vendor.nice_feature": "*"
	}
```

Add the module directory name (`nice_feature/`) to `.gitignore` - we will rely on *composer* to update the dependencies so
we don't need to version-control it through the master repository.

Run `composer update` to pull the module in and update all other dependencies as well. You can also update just this one
module by calling `composer update my_vendor/nice_feature`.

If you get cryptic composer errors it's worth checking that your module code is fully pushed. This is because composer
can only access the code you have actually pushed to the upstream repository and it may be trying to use the stale
versions of the files. Also, update composer regularly (`composer self-update`). You can also try deleting Composer
cache: `rm -fr ~/.composer/cache`.

Finally, commit the the modified `composer.json`, `composer.lock`  and `.gitignore` files to the repository. The
`composer.lock` serves as a snapshot marker for the dependencies - other developers will be able to `composer install`
exactly the version of the modules you have used in your project, as well as the correct version will be used for the
deployment. Some additional information is available in the [Deploying projects with
composer](https://docs.silverstripe.org/en/4/getting_started/composer/#deploying-projects-with-composer).

### Open-sourcing your creation for the community to use

In case you want to share your creation with the community, read more about [publishing a module](how_tos/publish_a_module).

## Module Standard

The Silverstripe CMS module standard defines a set of conventions that high-quality Silverstripe CMS modules should follow. It’s a bit like PSR for Silverstripe CMS. Suggested improvements can be raised as pull requests.
This standard is also part of the more highlevel
[Supported Modules Definition](https://www.silverstripe.org/software/addons/supported-modules-definition/)
which the Silverstripe CMS project applies to the modules it creates and maintains directly.

### Coding Guidelines

 * Declaration of level of support is provided for each module (either via README.md or composer) including the following:
   * Level of support provided.
   * Supporting user(s) and/or organisation(s).
 * Complies to a well defined module directory structure and coding standards:
   * `templates/` (for `.ss` templates)
   * `src/` (for `.php` files)
   * `tests/` (for `*Test.php` test files), and;
   * `_config/` (for `.yml` config files)
 * The module is a Composer package.
 * All Composer dependencies are bound to a single major release (e.g. `^4.0` not `>=4` or `*`).
 * There is a level of test coverage.
 * A clear public API documented in the docblock tags.
 * Code follows [PSR-1](http://www.php-fig.org/psr/psr-1/) and [PSR-2](http://www.php-fig.org/psr/psr-2/) style guidelines.
 * `.gitattributes` will be used to exclude non-essential files from the distribution. At a minimum tests, docs, and IDE/dev-tool config should be excluded.
 * Add a [PSR-4 compatible autoload reference](https://getcomposer.org/doc/04-schema.md#psr-4) for your module.

### Documentation Guidelines

Documentation will use the following format:

 * README.md provides:
   * Links or badges to CI and code quality tools.
   * A short summary of the module, end-user.
   * Installation instructions.
   * Testing/development instructions and a link to contributing instructions.
   * How to report security vulnerabilities. Note that PSR-9 / PSR-10 may be recommended once released.
   * Security, license, links to more detailed docs.
 * CONTRIBUTING.md explaining terms of contribution.
 * A changelog: CHANGELOG.md (may link to other more detailed docs or GitHub releases if you want). You could [use a changelog generator](https://github.com/skywinder/Github-Changelog-Generator) to help create this.
 * Has a licence (`LICENSE` file) - for Silverstripe CMS supported this needs to be BSD.
 * Detailed documentation in `/docs/en` as a nested set of GitHub-compatible Markdown files.
 * It is suggested to use a documentation page named `userguide.md` in `docs/en/` that includes documentation of module features that have CMS user functionality (if applicable). For modules with large userguides, this should be in a directory named `userguide` with an `index.md` linking to any other userguide pages.
 * Links and image references are relative, and are able to be followed in viewers such as GitHub.
 * Markdown may include non-visible comments or meta-data.

Documentation will cover:

 * Installation
 * Configuration
 * Usage guides for key features; screenshots are recommended.
 * A committers guide, covering pull request merging and release guidelines.

## Related

* [Module Skeleton](https://github.com/silverstripe/silverstripe-module)
* [Publishing a module](how_tos/publish_a_module)
