# URL Variable Tools

## Introduction

This page lists a number of "page options" , "rendering tools" or "special URL variables" that you can use to debug your
SilverStripe applications.  These are consumed in PHP using the $_REQUEST or $_GET superglobals throughout the SilverStripe
core.

## Debug Toolbar

The easiest way to debug SilverStripe is through the
[lekoala/silverstripe-debugbar](https://github.com/lekoala/silverstripe-debugbar) module.
It similar to the browser "developer toolbar", and adds itself to the bottom of the screen
when your site is in development mode. It shows you render times, database queries,
session variables, used templates and much more.

## Templates

 | URL Variable | | Values | | Description                                                     | 
 | ------------ | | ------ | | -----------                                                     | 
 | flush        | | 1      | | Clears out all caches. Used mainly during development, e.g. when adding new classes or templates. Requires "dev" mode or ADMIN login |
 | showtemplate | | 1      | | Show the compiled version of all the templates used, including line numbers.  Good when you have a syntax error in a template. Cannot be used on a Live site. |

## General Testing

 | URL Variable  | | Values | | Description                                                | 
 | ------------  | | ------ | | -----------                                                | 
 | debug         | | 1      | | Show a collection of debugging information about the director / controller operation        |
 | debug_request | | 1      | | Show all steps of the request from initial [HTTPRequest](api:SilverStripe\Control\HTTPRequest) to [Controller](api:SilverStripe\Control\Controller) to Template Rendering  | 

## Classes and Objects

 | URL Variable    | | Values | | Description                                                                               | 
 | ------------    | | ------ | | -----------                                                                               | 
 | debugfailover   | | 1      | | Shows failover methods from classes extended                                              | 

## Database

 | URL Variable | | Values | | Description                                                                                  | 
 | ------------ | | --------- | | -----------                                                                                  | 
 | showqueries  | | 1&vert;inline | | List all SQL queries executed, the `inline` option will do a fudge replacement of parameterised queries          | 
 | previewwrite | | 1      | | List all insert / update SQL queries, and **don't** execute them.  Useful for previewing writes to the database. | 

## Security Redirects

You can set an URL to redirect back to after a [Security](/developer_guides/security) action.  See the section on [URL
Redirections](/developer_guides/controllers/redirection) for more information and examples.

 | URL Variable | | Values | | Description                                                          | 
 | ------------ | | ------ | | -----------                                                          | 
 | BackURL      | | URL    | | Set to a relative URL string to use once Security Action is complete | 

## Building and Publishing URLS

 | Site URL                                         | | Action                                                            | 
 | --------                                         | | ------                                                            | 
 | http://localhost**/dev/build**                | | Rebuild the entire database and manifest, see below for additional URL Variables                                      | 
 | http://localhost**/admin/pages/publishall/**        | | Publish all pages on the site

###  /dev/build

 | URL Variable  | | Values | | Description                                                                                 | 
 | ------------  | | ------ | | -----------                                                                                 | 
 | quiet         | | 1      | | Don't show messages during build                                                            | 
 | dont_populate | | 1      | | Don't run **requireDefaultRecords()** on the models when building. This will build the table but not insert any records | 

