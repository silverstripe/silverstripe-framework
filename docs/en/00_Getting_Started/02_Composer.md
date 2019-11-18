---
title: Composer
summary: What is composer and how to use it with Silverstripe CMS
---

# Using SilverStripe with Composer

## Requirements

[Composer](http://getcomposer.org/) is a package management tool for PHP that lets you install and upgrade SilverStripe and its modules.
We also have separate instructions for [installing modules with Composer](/developer_guides/extending/modules).

Before installing Composer you should ensure your system has the version control system, [Git installed](http://git-scm.com/book/en/v2/Getting-Started-Installing-Git). Composer uses Git to check out the code dependancies you need to run your SilverStripe CMS website from the code repositories maintained on GitHub.

Next, [install composer](https://getcomposer.org/download/). For our documentation we assume the `composer` command is installed globally.
You should now be able to run the command:

```
composer help
```

## Create a new site

Composer can create a new site for you, using the installer as a template.
By default it will download the latest stable version:

```
composer create-project silverstripe/installer my-project
```

If you want to get all additional fixtures for testing, such as behat and phpunit configuration,
an example `.env.example` file, and all documentation, then it's recommended to use `--prefer-source`
to include these files.

If you want a minimal installation with the bare essentials to get working without any additional overhead,
and don't plan on contributing back changes to framework, use `--prefer-dist` (default) for a more lightweight install.

`./my-project` should be the root directory where your site will live.
For example, on OS X, you might use a subdirectory of `~/Sites`.
As long as your web server is up and running, this will get all the code that you need.
Now visit the site in your web browser, and the installation process will be completed.

You can also specify a version to download that version explicitly, i.e. this will download the older `4.3.3` release:

```
composer create-project silverstripe/installer ./my-project 4.3.3
```

When `create-project` is used with a release version like above,
it will try to get the code from archives instead of creating
git repositories. If you're planning to contribute to SilverStripe,
see [Using development versions](#using-development-versions).

## Adding modules to your project

Composer isn't only used to download SilverStripe CMS, it is also used to manage all SilverStripe modules. 
You can find thousands of modules on [https://addons.silverstripe.org](https://addons.silverstripe.org).
Installing a module can be done with the following command:

```
composer require silverstripe/blog
```

This will install the `silverstripe/blog` module in the latest compatible version. If you know the specific version you want to install already (such as `^2`), you can add it after the package name as a [version constraint](http://getcomposer.org/doc/01-basic-usage.md#the-require-key):

```
composer require silverstripe/blog ^2
```

[warning]
**Version constraints:** `master` is not a legal version string - it's a branch name. These are different things. The version string that would get you the branch is `dev-master`. The version string that would get you a numeric branch is a little different. The version string for the `4` branch is `4.x-dev`.
[/warning]

## Updating dependencies

Except for the control code of the Voyager space probe, every piece of code in the universe gets updated from time to time. SilverStripe modules are no exception.

To get the latest updates of the modules in your project, run this command:

```
composer update
```

Updates to the required modules will be installed, and the `composer.lock` file will get updated with the specific commits and version constraints for each of them.

## Deploying projects with Composer

When deploying projects with composer, you could just push the code and run `composer update`. This, however, is risky. In particular, if you were referencing development dependencies and a change was made between your testing and your deployment to production, you would end up deploying untested code. Not cool!

The `composer.lock` file helps with this. It references the specific commits that have been checked out, rather than the version string. You can run `composer install` to install dependencies from this rather than `composer.json`.

So your deployment process, as it relates to Composer, should be as follows:

 * Run `composer update` on your development version before you start whatever testing you have planned. Perform all the necessary testing.
 * Check `composer.lock` into your repository.
 * Deploy your project code base, using the deployment tool of your choice.
 * Run `composer install --no-dev -o` on your production version. In this command, the `--no-dev` command tells Composer not to install your development-only dependencies, and `-o` is an alias for `--optimise-autoloader`, which will convert your PSR-0 and PSR-4 autoloader definitions into a classmap to improve the speed of the autoloader.

## Composer managed modules, Git and .gitignore

Modules and themes managed by Composer should not be committed with your projects source code. 
SilverStripe ships with a [.gitignore](http://git-scm.com/docs/gitignore) file by default which prevents this.
For more details read  [Should I commit the dependencies in my vendor directory?](https://getcomposer.org/doc/faqs/should-i-commit-the-dependencies-in-my-vendor-directory.md).

## Dev Environments for Contributing Code {#contributing}

So you want to contribute to SilverStripe? Fantastic! You can do this with composer too.
You have to tell composer three things in order to be able to do this:

  - Keep the full git repository information
  - Include dependencies marked as "developer" requirements
  - Use the development version, not the latest stable version

The first two steps are done as part of the initial create project using additional arguments.

```
composer create-project --keep-vcs --dev silverstripe/installer ./my-project 4.x-dev --prefer-source
```

The process will take a bit longer, since all modules are checked out as full git repositories which you can work on. The command checks out from the 4.x release line. To check out from master instead,
replace `4.x-dev` with `dev-master` (more info on [composer version naming](http://getcomposer.org/doc/02-libraries.md#specifying-the-version)).

The `--keep-vcs` flag will make sure you have access to the git history of the installer and the requirements

The `--dev` flag is optional, and can be used to add a couple modules which are useful for
SilverStripe development:

 * The `behat-extension` module allows running [Behat](http://behat.org) integration tests
 * The `docsviewer` module will let you preview changes to the project documentation
 * The `buildtools` module which adds [phing](http://phing.info) tasks for creating SilverStripe releases

Once the `create-project` command completes, you need to edit the `composer.json` in the project root
and remove the `@stable` markers from the `silverstripe/cms` and `silverstripe/framework` version entries.
Another `composer update --dev` call will now fetch from the development branch instead.
Note that you can also convert an existing composer project with these steps.

Please read the ["Contributing Code"](/contributing/code) documentation to find out how to
create forks and send pull requests.

# Advanced usage

## Manually editing composer.json

To remove dependencies, or if you prefer seeing all your dependencies in a text file, you can edit the `composer.json` file. It will appear in your project root, and by default, it will look something like this:

```json
{
    "name": "silverstripe/installer",
    "description": "The SilverStripe Framework Installer",
    "require": {
        "php": ">=7.3",
        "silverstripe/cms": "^4",
        "silverstripe/framework": "^4",
        "silverstripe-themes/simple": "^3"
    },
    "require-dev": {
        "silverstripe/docsviewer": "^3"
    },
    "minimum-stability": "dev",
    "prefer-stable": true
}
```

To add modules, you should add more entries into the `"require"` section. For example, we might add the blog and forum modules. Be careful with the commas at the end of the lines!

Save your file, and then run the following command to refresh the installed packages:

```
composer update
```

## Using development versions

Composer will by default download the latest stable version of silverstripe/installer.
The `composer.json` file that comes with silverstripe/installer may also explicitly state it requires the stable version of cms and framework - this is to ensure that when developers are getting started, running `composer update` won't upgrade their project to an unstable version

However it is relatively easy to tell composer to use development versions. Not only
is this required if you want to contribute back to the SilverStripe project, it also allows you to get fixes and API changes early.

This is a two step process. First you get composer to start a project based on
the latest unstable silverstripe/installer

```
composer create-project silverstripe/installer ./my-project dev-master
```

Or for the latest development version in the 4.0.x series

```
composer create-project silverstripe/installer ./my-project 4.0.x-dev
```

## Working with project forks and unreleased modules

By default, Composer will install modules listed on the Packagist site. There are a few reasons that you might not
want to do this. For example:

 * You may have your own fork of a module, either specific to a project, or because you are working on a pull request
 * You may have a module that hasn't been released to the public.

There are many ways that you can address this, but this is one that we recommend, because it minimises the changes you would need to make to switch to an official version in the future.

This is how you do it:

 * **Ensure that all of your fork repositories have correct composer.json files.** Set up the project forks as you would a distributed package. If you have cloned a repository that already has a composer.json file, then there's nothing you need to do, but if not, you will need to create one yourself.

 * **List all your fork repositories in your project's composer.json files.** You do this in a `repositories` section. Set the `type` to `vcs`, and `url` to the URL of the repository. The result will look something like this:

```json
{
    "name": "silverstripe/installer",
    "description": "The SilverStripe Framework Installer",
    "repositories": [
        {
            "type": "vcs",
            "url": "git@github.com:sminnee/silverstripe-cms.git"
        }
    ]
}
```

 * **Install the module as you would normally.** Use the regular composer function - there are no special flags to use a fork. Your fork will be used in place of the package version.

```
composer require silverstripe/cms
```

Composer will scan all of the repositories you list, collect meta-data about the packages within them, and use them in favour of the packages listed on packagist.  To switch back to using the mainline version of the package, just remove the `repositories` section from `composer.json` and run `composer update`.

Now add an "upstream" remote to the original repository location so you can rebase or merge your fork as required.

```
cd cms
git remote add -f upstream git://github.com/silverstripe/silverstripe-cms.git
```

For more information, read the ["Repositories" chapter of the Composer documentation](http://getcomposer.org/doc/05-repositories.md).

### Forks and branch names

Generally, you should keep using the same pattern of branch names as the main repositories does. If your version is a fork of 4.0, then call the branch `4.0`, not `4.0-myproj` or `myproj`. Otherwise, the dependency resolution gets confused.

Sometimes, however, this isn't feasible. For example, you might have a number of project forks stored in a single repository, such as your personal GitHub fork of a project. Or you might be testing/developing a feature branch. Or it might just be confusing to other team members to call the branch of your modified version `4.0`.

In this case, you need to use Composer's aliasing feature to specify how you want the project branch to be treated, when it comes to dependency resolution.

Open `composer.json`, and find the module's `require`. Then put `as (core version name)` on the end.

```json
{
    "require": {
        "php": ">=5.5.0",
        "silverstripe/cms": "3.5.1.2",
        "silverstripe/framework": "dev-myproj as 4.0.x-dev",
        "silverstripe-themes/simple": "~3.2.0"
    }
}
```

What this means is that when the `myproj` branch is checked out into a project, this will satisfy any dependencies that `4.0.x-dev` would meet. So, if another module has `"silverstripe/framework": "^4.0.0"` in its dependency list, it won't get a conflict.

Both the version and the alias are specified as Composer versions, not branch names.  For the relationship between branch/tag names and Composer versions, read [the relevant Composer documentation](http://getcomposer.org/doc/02-libraries.md#specifying-the-version).

This is not the only way to set things up in Composer. For more information on this topic, read the ["Aliases" chapter of the Composer documentation](http://getcomposer.org/doc/articles/aliases.md).

## FAQ

### Error "The requested package silverstripe/framework 1.0.0 could not be found"

Composer needs hints about the base package version, either by using `composer create-project`
as described above, or by checking out the `silverstripe-installer` project directly from version control.
In order to use Composer on archive downloads from silverstripe.org, or other unversioned sources,
an advanced workaround is to set the `COMPOSER_ROOT_VERSION` before every command
([details](http://getcomposer.org/doc/03-cli.md#composer-root-version))

### How do I convert an existing module to using Composer?

Simply decide on a [unique name and vendor prefix](https://packagist.org/about),
create a `composer.json`, and either commit it or send a pull request to the module author.
Look at existing modules like the ["blog" module](https://github.com/silverstripe/silverstripe-blog/blob/master/composer.json) for good examples on what this file should contain.
It's important that the file contains a custom "type" to declare it as a
`silverstripe-module` or `silverstripe-theme` (see [custom installers](http://getcomposer.org/doc/articles/custom-installers.md)).
Then register the module on [packagist.org](http://packagist.org).

### How should I name my module?

Follow the packagist.org advice on choosing a [unique name and vendor prefix](https://packagist.org/about). Please don't use the `silverstripe/<modulename>` vendor prefix, since that's reserved
for modules produced by SilverStripe Ltd. In order to declare that your module is
in fact a SilverStripe module, use the "silverstripe" tag in the composer.json file,
and set the "type" to "silverstripe-module".

### What about themes?

Themes are technically just "modules" which are placed in the `themes/` subdirectory.
We denote a special type for them in the `composer.json` (`"type": "silverstripe-theme"`),
which triggers their installation into the correct path.

### How do I convert an existing project to Composer?

Copy the `composer.json` file from a newer release, and adjust the
version settings in the "require" section to your needs. Then refer to
the [upgrading documentation](/upgrading).
You'll also need to update your webserver configuration
from there (`.htaccess` or `web.config` files), in order to prevent
web access to the composer-generated files.

### Do I need composer on my live server?

It depends on your deployment process. If you copy or rsync files to your live server,
the process stays the same. If the live server hosts a git repository checkout,
which is updated to push a newer version, you'll also need to run `composer install` afterwards.
We recommend looking into [Composer "lock" files](http://getcomposer.org/doc/01-basic-usage.md#composer-lock-the-lock-file) for this purpose.

### Can I keep using Downloads, Subversion Externals or Git Submodules?

Composer is more than just a file downloader. It comes with additional features such as
[autoloading](http://getcomposer.org/doc/01-basic-usage.md#autoloading)
or [scripts](http://getcomposer.org/doc/articles/scripts.md)
which some modules will start relying on.
Please check the module README for specific installation instructions.

### I don't want to get development versions of everything!

You don't have to, Composer is designed to work on the constraints you set.
You can declare the ["minimum-stability"](http://getcomposer.org/doc/04-schema.md#minimum-stability)
on your project as suitable, or even whitelist specific modules as tracking
a development branch while keeping others to their stable release.
Read up on [Composer "lock" files](http://getcomposer.org/doc/01-basic-usage.md#composer-lock-the-lock-file) on how this all fits together.
