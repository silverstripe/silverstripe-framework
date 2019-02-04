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

If the development branch is hosted on a different GIT remote than the one used to publish your module, you'll need to add a VCS entry to your `composer.json` file.

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

