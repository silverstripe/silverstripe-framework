# URL Variable Tools

## Introduction

This page lists a number of "page options" , "rendering tools" or "special URL variables" that you can use to debug your
SilverStripe applications.  These are consumed in PHP using the $_REQUEST or $_GET superglobals throughout the SilverStripe
core.

**General Usage**

Append the option and corresponding value to your URL in your browser's address bar.  You may find the [Firefox UrlParams extension](https://addons.mozilla.org/en-US/firefox/addon/1290) useful in order to debug a POST requests (Like Forms).

    http://yoursite.com/page?option_name=value
    http://yoursite.com/page?option_1=value&option_2=value

## Templates

 | URL Variable | | Values | | Description                                                     | 
 | ------------ | | ------ | | -----------                                                     | 
 | flush=1      | | 1      | | Clears out all caches. Used mainly during development, e.g. when adding new classes or templates. Requires "dev" mode or ADMIN login |
 | showtemplate | | 1      | | Show the compiled version of all the templates used, including line numbers.  Good when you have a syntax error in a template. Cannot be used on a Live site without **isDev**. |

## General Testing

 | URL Variable  | | Values | | Description                                                | 
 | ------------  | | ------ | | -----------                                                | 
 | isDev         | | 1      | | Put the site into [development mode](/topics/debugging), enabling debugging messages to the browser on a live server.  For security, you'll be asked to log in with an administrator log-in. Will persist for the current browser session. | 
 | isTest        | | 1      | | See above. | 
 | debug         | | 1      | | Show a collection of debugging information about the director / controller operation        |
 | debug_request | | 1      | | Show all steps of the request from initial `[api:HTTPRequest]` to `[api:Controller]` to Template Rendering  | 

## Classes and Objects

 | URL Variable    | | Values | | Description                                                                               | 
 | ------------    | | ------ | | -----------                                                                               | 
 | debugmethods    | | 1      | | Shows all methods available when an object is constructed (useful when extending classes or using object extensions) | 
 | debugfailover   | | 1      | | Shows failover methods from classes extended                                              | 

## Database

 | URL Variable | | Values | | Description                                                                                  | 
 | ------------ | | ------ | | -----------                                                                                  | 
 | showqueries  | | 1      | | List all SQL queries executed                                                                | 
 | previewwrite | | 1      | | List all insert / update SQL queries, and **don't** execute them.  Useful for previewing writes to the database. | 

## Security Redirects

You can set an URL to redirect back to after a [Security](/topics/security) action.  See the section on [URL
Redirections](security#redirect_back_to_another_page_after_login) for more information and examples.

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

