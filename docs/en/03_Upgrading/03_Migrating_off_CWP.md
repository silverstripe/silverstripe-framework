---
title: Migrating off CWP CMS recipe
summary: Migrate your project off the Common Web Platform CMS recipe v2
---

# Migrate your project off the Common Web Platform CMS recipe v2

Until September 2021, Silverstripe Ltd maintained a specialised version of Silverstripe CMS for the New Zealand Government and related New Zealand public-sector agencies. This version was called _Common Web Platform_ (CWP for short).

The CWP CMS release has been discontinued. CWP projects who wish to continue to receive CMS updates must migrate their code base off the latest CWP CMS release back to the regular Silverstripe CMS release.

## Steps to migrate away from CWP recipe

Before you begin this process you must identify which version of Silverstripe CMS Recipe you want to upgrade to. You can upgrade to [Silverstripe CMS Recipe 4.9.0](/changelogs/4.9.0/) or greater.

[Review the changelog list](/changelogs/) to choose the Silverstripe CMS Recipe version you wish to upgrade to. Read the changelog carefully. It may contain additional release specific upgrade guidance beyond the steps in this guide.

1. If your current composer file contains `cwp/cwp-installer`, run `composer update-recipe cwp/cwp-installer` to inline the recipe.
2. If your current composer file contains `cwp/cwp-recipe-cms`, run `composer update-recipe cwp/cwp-recipe-cms` to inline the recipe.
3. If your current composer file contains `cwp/cwp-recipe-core`, replace it with `silverstripe/recipe-ccl`.
4. If your current composer file contains `cwp/cwp-recipe-search`, replace it with `silverstripe/recipe-solr-search`.
5. Review all your composer requirements and update the constraints so the module versions documented in the changelog for you desired Silverstripe CMS Recipe release are installable.
6. Run a `composer update` to get the lastest tag of each module.

The `silverstripe/recipe-solr-search` and `silverstripe/recipe-ccl` release versions follow the same numbering scheme as the recipes they superseded:
- the last release version of `cwp/cwp-recipe-search` and `cwp/cwp-recipe-core` is 2.8.0
- the first release version of `silverstripe/recipe-solr-search` and `silverstripe/recipe-ccl` is 2.9.0.

### Optional clean-up step

`composer update-recipe` adds some superfluous data to your `composer.json` file. You may remove the following entries from your composer file:
- `extra.project-dependencies-installed` and all the underlying entries
- all the `cwp` entries under the `provide` key.

Once you've completed the clean up, you should run a `composer update` to sync up your changes in the `composer.lock` file.

## Questions

### Will this have an effect on the features of my project?

Neither the steps to inline now deprecated recipes, nor replacing those that have a new package name will affect the feature-set of your project. It will also have no effect on how your project is currently hosted. This change simply focuses on ensuring that your project continues to receive CMS upgrades through the standard Silverstripe CMS release line - no longer following the ‘CWP 2.x’ versioning convention.

### What constraint should I use in my requirement version?

That really depends on how comfortable your organisiation is with automatically applying updates to your Silverstripe CMS project.

The three broad constraint categories you can use are, in descending order of restrictiveness:
- _exact version_ which forces composer to install the exact version of a package (e.g.: `"silverstripe/userforms: "5.9.0"`)
- _tilde constraint_ which allows composer to install a later version of a package within the same minor release line (e.g.: `"silverstripe/userforms: "~5.9.2"` will allow composer to install the `5.9.2` or `5.9.3` releases, but not the `5.10.0` or `5.9.1` ones)
- _caret constraint_ which allows composer to install a later version of a package within the same major release line (e.g.: `"silverstripe/userforms: "^5.9.2"` will allow composer to install the `5.9.3` or `5.10.0` releases, but not the `6.0.0` one).

The _tilde constraint_ is a suitable default and is the constraint style that ships in the `silverstripe/installer`.

Read [Next Significant Release Operators](https://getcomposer.org/doc/articles/versions.md#next-significant-release-operators) in the composer documentation for more information version constraint operators.

### What if I get a conflict when running composer update?

Composer normally tells you which packages are in conflict. You can try using the `--with-all-dependencies` flag and allow composer upgrade or downgrade other dependencies to resolve conflicts:
```bash
composer update --with-all-dependencies
```

If the `--with-all-dependencies` flag doesn't fix the problem, carefully read the composer error message and try to identify which constraints need to be updated to resolve to an installable set of dependencies.

### What happens if I do not migrate off the CWP CMS recipes?

The CWP CMS recipes have been archived, but existing versions will remain installable for the indefinite future. No new versions of the CWP CMS recipes will be tagged. The CWP CMS recipes are in lockstep with exact versions of various Silverstripe CMS modules. This means you will not be able to upgrade to any Silverstripe CMS Recipes beyond the 4.8 release.

Your project will still work, but will not receive any bug fixes, security fixes or new features that ship in later releases.

### What if my project is still using CWP 1?

CWP version 1 (based on Silverstripe CMS version 3) support has [reached end of life](https://www.cwp.govt.nz/working-with-cwp/releases/) and will no longer be receiving security updates. Projects still on CWP 1 are at risk of security vulnerabilies and should get in touch with their development team, or the [Silverstripe Service Desk](https://servicedesk.silverstripe.cloud/), and make a plan for upgrading their project to CMS version 4.

Projects will need to have upgraded to CMS 4 before following the directions in this guide to remove all the legacy CWP CMS recipes.

### Is there a way to create a new project that would require all the modules a CWP project would have?

There's no officially supported alternative to the `cwp/cwp-installer` module. There's nothing stopping you from creating a new project using `cwp/cwp-installer` as a baseline and then inlining the CWP CMS recipes using the steps outlined in this guide.

However, your project will not be following the most up-to-date best practices if you use this approach. 
