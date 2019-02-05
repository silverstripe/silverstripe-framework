title: Upgrading a module
introduction: Upgrade your module to be compatible with SilverStripe 4 and make it easy for your users to upgrade.

# Upgrading a module to be compatible with SilverStripe 4

This guide will help you upgrade a SilverStripe 3 module to be compatible with SilverStripe 4.

You should be familiar with [Upgrading to SilverStripe 4](/upgrading) before reading this guide. The process for upgrading a SilverStripe module is very similar to the process for Upgrading a SilverStripe project. This guide focuses on highlighting ways in which upgrading a module differs from upgrading a regular project.

## Improving the upgrade experience of your users with a`.upgrade.yml` file 

Making your module compatible with SilverStripe 4 is only one part of the process. As a module maintainer, you also want to provide a good upgrade experience for your users. Your module can integrate with the [SilverStripe upgrader]() just like the SilverStripe core modules.

Your SilverStripe 4 module should ship with a `.upgrade.yml` file. This file is read by the upgrader and will define new APIs introduced by the upgraded version of your module. Each steps in this guide details what entry you should add your module's `.upgrade.yml` file.

## Step 0 - Branching off your project

You'll want to run your module upgrade on a dedicated development branch. While it's possible to upgrade a module from within a SilverStripe project, it's usually cleaner and easier to clone your module and work directly on it.

```bash
# We're assumming that the default branch of you module is the latest SS3 compatible branch 
git clone git@github.com:example-user/silverstripe-example-module.git
cd silverstripe-example-module

git checkout -b pulls/ss4-upgrade
git push origin pulls/ss4-upgrade --set-upstream
```

If you're planning to keep supporting the SilverStripe 3 version of your module, consider creating a dedicated SilverStripe 3 branch.

To require the development branch of your module in a SilverStripe 4 project, you can use composer and prefix the name the name of your branch with `dev-`.

```bash
composer require example-user/silverstripe-example-module dev-pulls/ss4-upgrade
```

If the development branch is hosted on a different GIT remote than the one used to publish your module, you'll need to add a VCS entry to your test project's `composer.json` file.

```json
{
  "name": "example-user/test-project",
  "type": "project",
  "require": {
    "example-user/silverstripe-example-module": "dev-pulls/ss4-upgrade"
  },
  "repositories": [
    {
      "type": "vcs",
      "url": "git@github.com:alternative-user/silverstripe-example-module.git"
    }
  ]
}
```

Note that you will not be able to install your development branch in a SilverStripe 4 project until you've adjusted its dependencies.

## Step 1 - Upgrade your dependencies

Before you can install your module in a SilverStripe 4 project, you must update your module's `composer.json` file to require SilverStripe 4 compatible dependencies. In most cases, you'll be better off updating your module's composer file manually, especially if your module only requires a small number of dependencies. You can use upgrader's `recompose` command if you want, but you'll need to carefulyl validate the resulting `composer.json` file.

### Update module's type 

SilverStripe 4 modules are now installed inside the vendor directory. To get your modules installed in the vendor directory, you'll need to update the its `type` to `silverstripe-vendormodule`. You'll also need to add a dependency to `silverstripe/vendor-plugin`

```diff
{
    "name": "example-user/silverstripe-example-module",
-    "type": "silverstripe-module",
+    "type": "silverstripe-vendormodule",
    "require": {
        "silverstripe/framework": "^3",
+        "silverstripe/vendor-plugin": "^1"
        
    }
}
```

### Prefer specific modules over recipes

When upgrading a project, it is recommended to require recipes over modules. However, when upgrading a module, you want to limit the number the number of additional packages that gets installed along with your module. You should target specific packages that your module dependents on. 

For example, let's say your module adds a new ModelAdmin to the SilverStripe administration area without interacting with the CMS directly. In this scenario the main module you will need is `silverstripe/admin` which contains the `ModelAdmin` class and related administration functionality. If you update your `composer.json` file to require `silverstripe/recipe-cms`, you'll force your users to install a lot of modules they may not need like `silverstripe/cms`, `silverstripe/campaign-admin`, `silverstripe/asset-admin`, `silverstripe/versioned-admin`.

### Avoid rigid constraints

Choose constraint based on the minimum version of SilverStripe 4 you are planning on supporting and allow your module to work with future releases. 

For example, if your module requires an API that got introduced with the 4.1 release of `silverstripe/framework`, then that's the version you should target. You should use the caret symbol (`^`) over the ellipse (`~`) so your module works with more recent releases. In this scenario, your constraint should look like `"silverstripe/framework": "^4.1"`.

### Avoid tracking unnecessary files

If you run composer commands from your module's folder, a lock file will be created and dependencies will be installed in a vendor folder. You may also get `project-files` and `public-files` entries added under the `extra` key in your composer.json.

While these changes may be useful for testing, they should not be part of the final release of your module.

### Finalising the module's dependency upgrade

You should commit the changes to your module's `composer.json` and push them to your remote branch.

By this point, your module should be installable in a test SilverStripe 4 project. It will be installed under the vendor directory (e.g.: `vendor/example-user/silverstripe-example-module`). However, it produce exception if you try to run it.

From this point, you can either work from a test project or you can keep working directly on your module.

