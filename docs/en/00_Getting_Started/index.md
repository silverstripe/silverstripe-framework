---
title: Getting Started
introduction: SilverStripe is a web application. This means that you will need to have a webserver and database. We will take you through the setup of the server environment as well the application itself.
icon: rocket
---

## Server Requirements

SilverStripe requires PHP 7.1 or newer.
It runs on many webservers and databases,
but is most commonly served using Apache and MySQL/MariaDB.

If you are setting up your own environment,
you'll need to consider a few configuration settings
such as URL rewriting and protecting access to certain files.
Refer to our [server requirements](server_requirements) for details.

## Quickstart Installation

If you're running Apache with MySQL/MariaDB already,
and know your way around webservers, follow these steps to get started.
SilverStripe is installed via [Composer](https://getcomposer.org), a package management tool for PHP that
lets you install and upgrade the framework and other modules. 
Assuming you've got this tool, run the following command to install SilverStripe:

```
composer create-project silverstripe/installer my-project
```

Within the newly created `my-project` folder, point your webserver at the `public/` folder.

Now create a `.env` file your project root (not the `public/` folder).
It sets up the minimum required [environment variables](environment_management).
Replace the placeholders as required:

```
SS_DATABASE_CLASS="MySQLDatabase"
SS_DATABASE_NAME="<database>"
SS_DATABASE_SERVER="localhost"
SS_DATABASE_USERNAME="<user>"
SS_DATABASE_PASSWORD="<password>"
SS_DEFAULT_ADMIN_USERNAME="admin"
SS_DEFAULT_ADMIN_PASSWORD="password"
```

Now you should be able to build your database by running this command:

```
vendor/bin/sake dev/build
```

Your website should be available on your domain now (e.g. `http://localhost`).
The CMS login can be accessed at `/admin`.

For more information on how to maintain your installation or install projects,
check out [Using SilverStripe with Composer](composer).

## Guided Installation

If you are unsure on how this all works, please jump on our [lessons](https://www.silverstripe.org/learn/lessons/v4/).
Webserver setup is covered in
[Lesson 4: Setting up a local dev environment](https://www.silverstripe.org/learn/lessons/v4/up-and-running-setting-up-a-local-silverstripe-dev-environment-1).



## Keep learning

[CHILDREN]

## Troubleshooting

If you run into trouble, see [common-problems](common_problems) or
check our [community help options](https://www.silverstripe.org/community/).

