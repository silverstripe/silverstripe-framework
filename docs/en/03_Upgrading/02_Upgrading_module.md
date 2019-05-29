title: Upgrading a module
introduction: Upgrade your module to be compatible with SilverStripe 4 and make it easy for your users to upgrade.

# Upgrading a module to be compatible with SilverStripe 4

This guide will help you upgrade a SilverStripe 3 module to be compatible with SilverStripe 4.

You should be familiar with [Upgrading a project to SilverStripe 4](upgrading_project) before reading this guide. The process for upgrading a SilverStripe module is very similar to the process for Upgrading a SilverStripe project. This guide focuses on highlighting ways in which upgrading a module differs from upgrading a regular project.

## Improving the upgrade experience of your users with a`.upgrade.yml` file 

Making your module compatible with SilverStripe 4 is only one part of the process. As a module maintainer, you also want to provide a good upgrade experience for your users. Your module can integrate with the [SilverStripe upgrader](https://github.com/silverstripe/silverstripe-upgrader) just like the SilverStripe core modules.

Your SilverStripe 4 module should ship with a `.upgrade.yml` file. This file is read by the upgrader and will define new APIs introduced by the upgraded version of your module. Each step in this guide details what entry you should add to your module's `.upgrade.yml` file.

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

If the development branch is hosted on a different Git remote than the one used to publish your module, you'll need to add a VCS entry to your test project's `composer.json` file.

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

You will not be able to install your development branch in a SilverStripe 4 project until you've adjusted your module's dependencies.

## Step 1 - Upgrade your dependencies

Before you can install your module in a SilverStripe 4 project, you must update your module's `composer.json` file to require SilverStripe 4 compatible dependencies. In most cases, you'll be better off updating your module's composer file manually, especially if your module only requires a small number of dependencies. You can use upgrader's `recompose` command if you want, but you'll need to carefully validate the resulting `composer.json` file.

### Update module's type 

SilverStripe 4 modules are now installed inside the vendor directory. To get your module installed in the vendor directory, you'll need to update its `type` to `silverstripe-vendormodule`. You'll also need to add a dependency to `silverstripe/vendor-plugin`.

```diff
{
    "name": "example-user/silverstripe-example-module",
-    "type": "silverstripe-module",
+    "type": "silverstripe-vendormodule",
    "require": {
+        "silverstripe/vendor-plugin": "^1",
+        "silverstripe/framework": "^3"

    }
}
```

### Prefer specific modules over recipes

When upgrading a project, it is recommended to require recipes rather than modules. However, when upgrading a module, you want to limit the number of additional packages that gets installed along with your module. You should target specific packages that your module depends on. 

For example, let's say your module adds a ModelAdmin to the SilverStripe administration area without interacting with the CMS directly. In this scenario, the main module you need is `silverstripe/admin` which contains the `ModelAdmin` class and related administration functionality. If you update your `composer.json` file to require `silverstripe/recipe-cms`, you'll force your users to install a lot of modules they may not need like `silverstripe/cms`, `silverstripe/campaign-admin`, `silverstripe/asset-admin`, `silverstripe/versioned-admin`.

### Avoid rigid constraints

Choose constraints based on the minimum version of SilverStripe 4 you are planning on supporting and allow your module to work with future releases. 

For example, if your module requires an API that got introduced with the 4.1 release of `silverstripe/framework`, then that's the version you should target. You should use the caret symbol (`^`) over the tilde (`~`) so your module works with more recent releases. In this scenario, your constraint should look like `"silverstripe/framework": "^4.1"`.

### Avoid tracking unnecessary files

If you run composer commands from your module's folder, a lock file will be created and dependencies will be installed in a vendor folder. You may also get `project-files` and `public-files` entries added under the `extra` key in your composer.json.

While these changes may be useful for testing, they should not be part of the final release of your module.

### Finalising the module's dependency upgrade

You should commit the changes to your module's `composer.json` and push them to your remote branch.

By this point, your module should be installable in a test SilverStripe 4 project. It will be installed under the vendor directory (e.g.: `vendor/example-user/silverstripe-example-module`). However, it will throw exceptions if you try to run it.

From this point, you can either work from a test project or you can keep working directly on your module.

## Step 2 - Update your environment configuration

As a module maintainer, you shouldn't be shipping any environment file with your module. So there's no need for you to run the upgrader `environment` command. If your module requires environment variables, you should update your documentation accordingly, but otherwise you can move on to the next step.

## Step 3 - Namespacing your module

Namespacing your module is mandatory to get it working with SilverStripe 4. You can use the `add-namespace` upgrader command to achieve this.

```bash
# If you are working from a test project, you need to specify the `--root-dir` parameter
upgrade add-namespace --root-dir vendor/example-user/silverstripe-example-module \
  "ExampleUser\\SilverstripeExampleModule" \
  vendor/example-user/silverstripe-example-module/code/
  
# If you are working directly from the module, you can ommit `--root-dir` parameter
upgrade add-namespace "ExampleUser\\SilverstripeExampleModule" code/
```

If your module codebase is structured in folders, you can use the `--psr4` and `--recursive` flag to quickly namespace your entire module in one command. This command will recursively go through the `code` directory and namespace all files based on their position relative to `code`.

```bash
upgrade add-namespace --recursive --psr4 "ExampleUser\\SilverstripeExampleModule" code/
```

### Configuring autoloading

You need to update your `composer.json` file with an autoload entry, so composer knows what folder maps to what namespace.

You can do this manually:
```diff
{
    "name": "example-user/silverstripe-example-module",
    "type": "silverstripe-vendormodule",
    "require": {
        "silverstripe/framework": "^4",
        "silverstripe/vendor-plugin": "^1"
-    }
+    },
+    "autoload": {
+        "psr-4": {
+            "ExampleUser\\SilverstripeExampleModule\\": "code/",
+            "ExampleUser\\SilverstripeExampleModule\\Tests\\": "tests/"
+        }
+    }
}
```

Alternatively, you can use the `--autoload` parameter when calling `add-namespace` to do this for you.

```bash
upgrade add-namespace --recursive --psr4 --autoload "ExampleUser\\SilverstripeExampleModule" code/
upgrade add-namespace --recursive --psr4 --autoload "ExampleUser\\SilverstripeExampleModule\\Tests" tests
```

[Learn more about configuring autoloading](https://getcomposer.org/doc/04-schema.md#autoload) in your `composer.json` file.

### Preparing your `.upgrade.yml` file

`add-namespace` will create a `.upgrade.yml` file that maps your old class names to their new namespaced equivalent. This will be used by the `upgrade` command in the next step.

Depending on the nature of your module, you may have some class names that map to other common names. When the `upgrade` command runs, it will try to substitute any occurrence of the old name with the namespaced one. This can lead to accidental substitution. For example, let's say you have a `Link` class in your module. In many project the word `Link` will be used for other purposes like a field label or property names. You can manually update your `.upgrade.yml` file to define a `renameWarnings` section. This will prompt users upgrading to confirm each substitution.

```yml
mappings:
  # Prompt user before replacing references to Link
  Link: ExampleUser\SilverstripeExampleModule\Model\Link
  # No prompt when replacing references to ExampleModuleController
  ExampleModuleController: ExampleUser\SilverstripeExampleModule\Controller
  
renameWarnings:
  - Link

```

Make sure to commit this file and to ship it along with your upgraded module. This will allow your users to update references to your module's classes if they use the upgrader on their project. 

### Finalising your namespaced module

By this point:
* all your classes should be inside a namespace
* your `composer.json` file should have an autoload definition
* you should have a `.upgrade.yml` file.

However, your codebase is still referencing SilverStripe classes by their old non-namespaced names. Commit your changes before proceeding to the next step.

## Step 4 - Update codebase with references to newly namespaced classes

This part of the process is identical for both module upgrades and project upgrades.

```bash
# If upgrading from inside a test project
upgrade-code upgrade --root-dir vendor/example-user/silverstripe-example-module \
  vendor/example-user/silverstripe-example-module/
  
# If upgrading the module directly
upgrade-code upgrade ./
```

All references to the old class names will be replaced with namespaced class names.

By this point, you should be able to load your module with PHP. However, your module will be using deprecated APIs.

## Step 5 - Updating your codebase to use SilverStripe 4 API

This step will allow you to update references to deprecated APIs. If you are planning on making changes to your own module's API, take a minute to define those changes in your `.upgrade.yml`:
* this will help you with updating your own codebase
* your users will be warned when using your module's deprecated APIs.

You can define warnings for deprecated APIs along with a message. If there's a one-to-one equivalent for the deprecated API, you can also define a replacement. e.g.: 

```yml
warnings:
  classes:
    'ExampleUser\SilverstripeExampleModule\Controller':
      message: 'This warning message will be displayed to your users'
      url: 'https://github.com/example-users/silverstripe-example-module/en/4/changelogs/#object-replace'
  methods:
    'ExampleUser\SilverstripeExampleModule\AmazingClass::deprecatedMethod()':
      message: 'Replace with a different method'
      replacement: 'newBetterMethod'
  props:
    'ExampleUser\SilverstripeExampleModule\AmazingClass->oldProperty':
      message: 'Replace with a different property'
      replacement: 'newProperty'
```

When you are done updating your `.upgrade.yml` file, you can run the `inspect` command to search for deprecated APIs.

```bash
# If upgrading from inside a test project
upgrade-code inspect --root-dir vendor/example-user/silverstripe-example-module \
  vendor/example-user/silverstripe-example-module/code/
  
# If upgrading the module directly
upgrade-code inspect code/
```

## Step 6 - Update your entry point

Module do not have an entry point. So there's nothing to do here.

## Step 7 - Update project structure

This step is optional. We recommend renaming `code` to `src`. This is only a convention and will not affect how your module will be executed.

If you do rename this directory, do not forget to update your `autoload` configuration in your `composer.json` file.

## Step 8 - Switch to public web-root

The public web root does not directly affect module. So you can skip this step.

## Step 9 - Move away from hardcoded paths for referencing static assets

While SilverStripe 4 projects can get away with directly referencing static assets under some conditions, modules must dynamically expose their static assets. This is necessary to move modules to the vendor folder and to enable the public web root.  

### Exposing your module's static assets

You'll need to update your module's `composer.json` file with an `extra.expose` key.


```diff
{
    "name": "example-user/silverstripe-example-module",
    "type": "silverstripe-vendormodule",
    "require": {
        "silverstripe/framework": "^4",
        "silverstripe/vendor-plugin": "^1"
    },
    "autoload": {
        "psr-4": {
            "ExampleUser\\SilverstripeExampleModule\\": "code/"
        }
    },
    "autoload-dev": {
        "psr-4": {
            "ExampleUser\\SilverstripeExampleModule\\Tests\\": "tests/"
        }
-    }
+    },
+    "extra": {
+        "expose": [
+            "images",
+            "styles",
+            "javascript"
+        ]
+    }
}
```

### Referencing static assets

This process is essentially the same for projects and modules. The only difference is that module static asset paths must be prefix with the module's name as defined in their `composer.json` file.

```diff
<?php 
- Requirements::css('silverstripe-example-module/styles/admin.css');
+ Requirements::css('example-user/silverstripe-example-module: styles/admin.css');
$pathToImage =
-    'silverstripe-example-module/images/logo.png';
+    ModuleResourceLoader::singleton()->resolveURL('example-user/silverstripe-example-module: images/logo.png');
```

## Step 10 - Update database class references {#step10}

Just like projects, your module must define class names remapping for every DataObject child.

```
SilverStripe\ORM\DatabaseAdmin:
  classname_value_remapping:
    ExampleModuleDummyDataObject: ExampleUser\SilverstripeExampleModule\Models\DummyDataObject
```

On the first `dev/build` after a successful upgrade, the `ClassName` field on each DataObject table will be substituted with the namespaced classname.

    
## Extra steps

You've been through all the steps covered in the regular project upgrade guide. These 2 additional steps might not be necessary.

### Create migration tasks

Depending on the nature of your module, you might need to perform additional tasks to complete the upgrade process. For example, the `framework` module ships with a file migration task that converts files from the old SilverStripe 3 structure to the new structure required by SilverStripe 4.

Extend [BuildTask](api:SilverStripe/Dev/BuildTask)s and create your own migration task if your module requires post-upgrade work. Document this clearly for your users so they know they need to run the task after they're done upgrading their project.

### Keep updating your `.upgrade.yml`

The upgrader can be run on projects that have already been upgraded to SilverStripe 4. As you introduce new API and deprecate old ones, you can keep updating your `.upgrade.yml` file to make it easy for your users to keep their code up to date. If you do another major release of your module aimed at SilverStripe 4, you can use all the tools in the upgrader to make the upgrade process seamless for your users.
