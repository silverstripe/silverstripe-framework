# Directory Structure

## Introduction

The directory-structure in SilverStripe it built on "convention over configuration", so the placement of some files and
directories is meaningful to its logic.

## Core Structure

Directory   | Description
---------   | -----------
`assets/`   | Images and other files uploaded via the SilverStripe CMS. You can also place your own content inside it, and link to it from within the content area of the CMS.
`resources/`| Public files from modules (added automatically) 
`vendor/`   | SilverStripe modules and other supporting libraries (the framework is in `vendor/silverstripe/framework`)

## Custom Code Structure

We're using `<mysite>` as an example - arbitrary directory-names are allowed, as long as they don't collide with
existing modules or the directories lists in "Core Structure".

 | Directory             | Description                                                         |
 | ---------             | -----------                                                         |
 | `<mysite>/`           | This directory contains all of your code that defines your website. |
 | `<mysite>/_config`    | YAML configuration specific to  your application                    |
 | `<mysite>/src`        | PHP code for model and controller (subdirectories are optional)     |
 | `<mysite>/tests`      | PHP Unit tests                                                      |
 | `<mysite>/templates`  | HTML [templates](/developer_guides/templates) with *.ss-extension for the `$default` theme   |
 | `<mysite>/css `       | CSS files                                                           |
 | `<mysite>/images `    | Images used in the HTML templates                                   |
 | `<mysite>/javascript` | Javascript and other script files                                   |
 | `<mysite>/client`     | More complex projects can alternatively contain frontend assets in a common `client` folder |
 | `<mysite>/themes/<yourtheme>` | Custom nested themes (note: theme structure is described below)     |

Check our [JavaScript Coding Conventions](javascript_coding_conventions) for more details
on folder and file naming in SilverStripe core modules.

## Themes Structure

 | Directory                       | Description                                                     |
 | ------------------              | ---------------------------                                     |
 | `themes/simple/`                | Standard "simple" theme                                         |
 | `themes/<yourtheme>/`           | Custom theme base directory                                     |
 | `themes/<yourtheme>/templates`  | Theme templates                                                 |
 | `themes/<yourtheme>/css`        | Theme CSS files                                                 |


See [themes](/developer_guides/templates/themes)

## Module Structure {#module_structure}

Modules are commonly stored as composer packages in the `vendor/` folder.
They need to have a `_config.php` file or a `_config/` directory present,
and should follow the same conventions as posed in "Custom Site Structure".

Example Forum:

 | Directory  | Description                                                         |
 | ---------  | -----------                                                         |
 | `vendor/silverstripe/blog/`| This directory contains all of your code that defines your website. |
 | `vendor/silverstripe/blog/code` | PHP code for model and controller (subdirectories are optional)     |
 | ...        | ...                                                                 |

Note: Before SilverStripe 4.x, modules were living as top-level folders in the webroot itself.
Some modules might not have been upgraded to support placement in `vendor/`

### Module documentation

Module developers can bundle developer documentation with their code by producing
plain text files inside a 'docs' folder located in the module folder. These files
can be written with the Markdown syntax (See [Contributing Documentation](/contributing/documentation))
and include media such as images or videos.

Inside the `docs/` folder, developers should organise the markdown files into each
separate language they wish to write documentation for (usually just `en`). Inside
each languages' subfolder, developers then have freedom to create whatever structure
they wish for organising the documentation they wish.

Example Forum Documentation:

 | Directory  | Description                                                         |
 | ---------  | -----------                                                         |
 | `blog/docs` | |
 | `blog/docs/_manifest_exclude` | Empty file to signify that SilverStripe does not need to load classes from this folder |
 | `blog/docs/en/`       | English documentation  |
 | `blog/docs/en/index.md`	| Documentation homepage. Should provide an introduction and links to remaining docs |
 | `blog/docs/en/Getting_Started.md` | Documentation page. Naming convention is Uppercase and underscores. |
 | `blog/docs/en/_images/` | Folder to store any images or media |
 | `blog/docs/en/Some_Topic/` | You can organise documentation into nested folders. Naming convention is Uppercase and underscores. |
 | `blog/docs/en/04_Some_Topic/00_Getting_Started.md`|Structure is created by use of numbered prefixes. This applies to nested folders and documentations pages, index.md should not have a prefix.|


## Autoloading

SilverStripe recursively detects classes in PHP files by building up a manifest used for autoloading,
as well as respecting Composer's built-in autoloading for libraries. This means
in most cases, you don't need to worry about include paths or `require()` calls
in your own code - after adding a new class, simply regenerate the manifest
by using a `flush=1` query parameter. See the ["Manifests" documentation](/developer_guides/execution_pipeline/manifests) for details.

## Best Practices

### Making /assets readonly
See [Secure coding](/developer_guides/security/secure_coding#filesystem)