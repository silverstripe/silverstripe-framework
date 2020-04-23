---
title: Recipes
summary: What Recipes are, and how they are used in Silverstripe CMS
icon: clipboard
---

# Recipes

Silverstripe CMS is powered by a system of components in the form of Composer packages. It consists of two types of package:

- **Modules**, which provide pieces of functionality (such as `silverstripe/cms` and `silverstripe/framework`)
- **Recipes**, which group related Modules together to make them easier to install and release.

When we announce a new version of Silverstripe CMS and publish a changelog for it, we refer to a new set of _Recipe_ versions, which include new versions of some or all of their associated Modules. The easiest way to keep up to date with new Silverstripe CMS releases is to depend on one of the core Recipes:

- [`silverstripe/recipe-core`](https://packagist.org/packages/silverstripe/recipe-core): Contains only the base framework, without the admin UI or CMS features.
- [`silverstripe/recipe-cms`](https://packagist.org/packages/silverstripe/recipe-cms): Includes `recipe-core`, and adds the admin UI and CMS features. We recommend specifying this recipe in your dependencies.
- [`silverstripe/installer`](https://packagist.org/packages/silverstripe/installer): Includes `recipe-cms`, and adds a default theme for the front-end of your site. We recommend creating new projects based on this recipe (via `composer create-project silverstripe/installer myproject ^4`)

When determining whether you are running the latest version of Silverstripe CMS, it is easier to refer to the Recipe version than the individual Module versions, which may not align with Recipe versions. You can use Packagist to find detailed information on what versions of individual modules are contained in each Recipe release.
