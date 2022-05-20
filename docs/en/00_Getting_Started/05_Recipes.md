---
title: Recipes
summary: What Recipes are, and how they are used in Silverstripe CMS
icon: clipboard
---

# Adding features to your project with Recipes

To achieve more complex use cases in Silverstripe CMS, you may need to combine many modules and add extra configuration to integrate these together. Silverstripe CMS Recipes streamline this process for common use cases.

## What are Silverstripe CMS Recipes?

Recipes are used to implement common broad feature sets by shipping a collection of modules along with the relevant integration logic. They allow developers to quickly get started while retaining the ability to customise their integration to their specific needs.

Before each version of a supported CMS recipe is released, it is comprehensively regression tested and passed to a third party for a security-focused audit, making sure that projects have a secure starting point or a safe and secure upgrade with each recipe release.

## What's the difference between a recipe and a module?

Silverstripe CMS is powered by a system of components in the form of Composer packages. It consists of two types of package:

- **Modules**, which provide pieces of functionality (such as `silverstripe/cms` and `silverstripe/framework`)
- **Recipes**, which group related Modules together to make them easier to install and release.

By design, modules tend to be small and serve a specific function. You may need to combine many modules to achieve a wider goal. 

For example, the `silverstripe/blog` module by itself simply allows you to create blog posts. It does not include all the features you could want in a blog, like a comment system or widgets to display related content.

The `silverstripe/recipe-blog` recipe installs `silverstripe/blog` module, but also:
- `silverstripe/widgets` and `silverstripe/content-widget` to display widgets
- `silverstripe/comments` and `silverstripe/comment-notifications` to allow the management of comments on blog post
- `silverstripe/spamprotection` and `silverstripe/akismet` to provide basic SPAM protection on comments.

## Finding recipes for Silverstripe CMS

The Silverstripe CMS project maintains a number of recipes. Some third parties also maintain recipes.

[Search Packagist for all packages with the `silverstripe-recipe`](https://packagist.org/?query=silverstripe&type=silverstripe-recipe) type to find recipes you can install on your Silverstripe CMS project.

## Releasing supported recipes

When we announce a new release of Silverstripe CMS and publish a changelog for it, we refer to a new set of _Recipe_ versions, which include new versions of some or all of their associated Modules. The easiest way to keep up to date with new Silverstripe CMS releases is to depend on one of the core Recipes:

- [`silverstripe/recipe-core`](https://packagist.org/packages/silverstripe/recipe-core): Contains only the base
  framework, without the admin UI or CMS features.
- [`silverstripe/recipe-cms`](https://packagist.org/packages/silverstripe/recipe-cms): Includes `recipe-core`, and adds
  the admin UI and CMS features. We recommend specifying this recipe in your dependencies.
- [`silverstripe/installer`](https://packagist.org/packages/silverstripe/installer): Includes `recipe-cms`, and adds a
  default theme for the front-end of your site. We recommend creating new projects based on this recipe (
  via `composer create-project silverstripe/installer myproject ^4`).

When determining whether you are running the latest version of Silverstripe CMS, it is easier to refer to the Recipe
version than the individual Module versions, which may not align with Recipe versions. You can use Packagist to find
detailed information on what versions of individual modules are contained in each Recipe release.
