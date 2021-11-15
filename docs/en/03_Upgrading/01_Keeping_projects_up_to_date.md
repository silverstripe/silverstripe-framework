---
title: Staying up to date with CMS releases
summary: Guidance on upgrading your website with new recipe releases
---

# Upgrading

Upgrading to new patch versions of the recipe shouldn't take a long time. See [recipes and supported modules](../00_Getting_Started/05_Recipes.md)) documentation to learn more about how recipe versioning is structured.

## Patch upgrades

To get the newest patch release of the recipe, just run:

`composer update`

This will update the recipe to the new version, and pull in all the new dependencies. A new `composer.lock` file will be generated. Once you are satisfied the site is running as expected, commit both files:

`git commit composer.* -m "Upgrade the recipe to latest patch release"`

After you have pushed this commit back to your remote repository you can deploy the change.

## Minor and major upgrades

Assuming your project is using one of the [supported recipes](../00_Getting_Started/05_Recipes.md), these will likely take more time as the APIs may change between minor and major releases. For small sites it's possible for minor upgrade to take a day of work, and major upgrades could take several days. Of course this can widely differ depending on each project.

To upgrade your code, open the root `composer.json` file. Find the lines that reference the recipes, like  `silverstripe/recipe-cms` and change the referenced versions to what has been reference in the changelog (as well as any other modules that have a new version).

For example, assuming that you are currently on version `~4.8.0@stable`, if you wish to upgrade to 4.9.0 you will need to modify your `composer.json` file to explicitly specify the new release branch, here `~4.9.0`:

```json
"require": {
    "silverstripe/recipe-cms": "~4.9.0"
},
...
```

You now need to pull in new dependencies and commit the lock file:

```bash
composer update
git commit composer.* -m "Upgrade to recipe 4.9.0"
```

Push this commit to your remote repository, and continue with your deployment workflow.

## Cherrypicking the upgrades

If you like to only upgrade the recipe modules, you can cherry pick what is upgraded using this syntax:

`composer update silverstripe/recipe-cms`

This will update only the two specified metapackage modules without touching anything else. You still need to commit resulting `composer.lock`.