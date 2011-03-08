# URL Variable Tools

## Introduction

This page lists a number of "page options" , "rendering tools" or "special URL variables" that you can use to debug your
sapphire applications.  These are consumed in PHP using the $_REQUEST or $_GET super globals throughout the Sapphire
core.

**General Usage**

Append the option and corresponding value to your URL in your browser's address bar.  You may find the [Firefox UrlParams extension](https://addons.mozilla.org/en-US/firefox/addon/1290) useful in order to debug a POST requests (Like Forms).

    http://yoursite.com/page?option_name=value
    http://yoursite.com/page?option_1=value&option_2=value

## Templates

 | URL Variable | | Values | | Description                                                     | 
 | ------------ | | ------ | | -----------                                                     | 
 | flush        | | 1,all  | | This will clear out all cached information about the page.  This is used frequently during development - for example, when adding new PHP or SS files. See below for value descriptions. | 
 | showtemplate | | 1      | | Show the compiled version of all the templates used, including line numbers.  Good when you have a syntax error in a template. Cannot be used on a Live site without **isDev**.  **flush** can be used with the following values: |
 | ?flush=1     | |        | | Flushes the current page and included templates |
 | ?flush=all   | |        | | Flushes the entire template cache |            

## General Testing

 | URL Variable  | | Values | | Description                                                | 
 | ------------  | | ------ | | -----------                                                | 
 | isDev         | | 1      | | Put the site into [development mode](/topics/debugging), enabling debugging messages to the browser on a live server.  For security, you'll be asked to log in with an administrator log-in | 
 | isTest        | | 1      | | Put the site into [test mode](/topics/debugging), enabling debugging messages to the admin email and generic errors to the browser on a live server                                         | 
 | debug         | | 1      | | Show a collection of debugging information about the director / controller operation        |
 | debug_request | | 1      | | Show all steps of the request from initial `[api:HTTPRequest]` to `[api:Controller]` to Template Rendering  | 

## Classes and Objects

 | URL Variable    | | Values | | Description                                                                               | 
 | ------------    | | ------ | | -----------                                                                               | 
 | debugmanifest   | | 1      | | Show the entire Sapphire manifest as currently built (Use `/dev/build` to rebuild)        | 
 | usetestmanifest | | 1      | | Force use of the default test manifest                                                    | 
 | debugmethods    | | 1      | | Shows all methods available when an object is constructed (useful when extending classes or using object decorators) | 
 | debugfailover   | | 1      | | Shows failover methods from classes extended                                              | 

## Database

 | URL Variable | | Values | | Description                                                                                  | 
 | ------------ | | ------ | | -----------                                                                                  | 
 | showqueries  | | 1      | | List all SQL queries executed                                                                | 
 | previewwrite | | 1      | | List all insert / update SQL queries, and **don't** execute them.  Useful for previewing writes to the database. | 

## Profiling

 | URL Variable     | | Values | | Description                                                                                      | 
 | ------------     | | ------ | | -----------                                                                                      | 
 | debug_memory     | | 1      | | Output the number of bytes of memory used for this request                                       | 
 | debug_profile    | | 1      | | Enable the [profiler](/topics/debugging) for the duration of the request                         | 
 | profile_trace    | | 1      | | Includes full stack traces, must be used with **debug_profile**                                  | 
 | debug_behaviour  | | 1      | | Get profiling of [Behaviour.js](http://bennolan.com/behaviour) performance (Firebug recommended) | 
 | debug_javascript | | 1      | | Force debug-output on live-sites                                                                 | 

## Misc

 | URL Variable | | Values     | | Description                                                                                                | 
 | ------------ | | ------     | | -----------                                                                                                | 
 | forceFormat  | | xhtml,html | | Force the content negotiator to deliver HTML or XHTML is allowed                                           | 
 | showspam     | | 1          | | Show comments marked as spam when viewing Comments on a Page (Saving spam to the database must be enabled) | 
 | ajax         | | 1          | | Force request to process as AJAX request, useful for debugging from a browser                              | 
 | force_ajax   | | 1          | | Similar to **ajax**                                                                                        | 

## Security Redirects

You can set an URL to redirect back to after a [Security](/topics/security) action.  See the section on [URL
Redirections](security#redirect_back_to_another_page_after_login) for more information and examples.

 | URL Variable | | Values | | Description                                                          | 
 | ------------ | | ------ | | -----------                                                          | 
 | BackURL      | | URL    | | Set to a relative URL string to use once Security Action is complete | 

## Building and Publishing URLS

 | Site URL                                         | | Action                                                            | 
 | --------                                         | | ------                                                            | 
 | http://yoursite.com**/dev/build**                | | Rebuild the entire database and manifest, see below for additional URL Variables                                      | 
 | http://yoursite.com**/admin/publishall/**        | | Publish all pages on the site                                     | 
 | http://yoursite.com**/anypage/images/flush**     | | Creates new images for the page by deleting the resized ones and going back to the original to create new resized one | 

###  /dev/build

 | URL Variable  | | Values | | Description                                                                                 | 
 | ------------  | | ------ | | -----------                                                                                 | 
 | quiet         | | 1      | | Don't show messages during build                                                            | 
 | dont_populate | | 1      | | Don't run **requireDefaultRecords()** on the models when building. This will build the table but not insert any records | 

