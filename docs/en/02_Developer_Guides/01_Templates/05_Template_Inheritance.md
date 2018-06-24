title: Template Inheritance
summary: Override and extend module and core markup templates from your application code.

# Template Inheritance

## Theme types

Templates in SilverStripe are bundled into one of two groups:
 - Default Templates, such as those provided in `mymodule/templates` folder.
 - Theme templates, such as those provided in `themes/mytheme/templates` folders.

The default templates provide basic HTML formatting for elements such as Forms, Email, or RSS Feeds, and provide a
generic base for web content to be built on.

## Template types and locations

Typically all templates within one of the above locations will be nested in a folder deterministically through
the fully qualified namespace of the underlying class, and an optional `type` specifier to segment template types.
Basic template types include `Layout` and `Includes`, and a less commonly used `Content` type.

For instance, a class `SilverStripe\Blog\BlogPage` will have a default template of type `Layout`
in the folder `vendor/silverstripe/blog/templates/SilverStripe/Blog/Layout/BlogPage.ss`.

Note: The optional `type`, if specified, will require a nested folder at the end of the parent namespace
(`SilverStripe\Blog`) to the class, but before the filename (`BlogPage`).

Templates not backed by any class can exist in any location, but must always be referred to in code
by the full path (from the `templates` folder onwards).

### Nested Layouts through `$Layout` type

SilverStripe has basic support for nested layouts through a fixed template variable named `$Layout`. It's used for 
storing top level template information separate to individual page layouts.

When `$Layout` is found within a root template file (one in `templates`), SilverStripe will attempt to fetch a child 
template from the `templates/<namespace>/Layout/<class>.ss` path, where `<namespace>` and `<class>` represent
the class being rendered. It will do a full sweep of your modules, core and custom code as it 
would if it was looking for a new root template, as well as looking down the class hierarchy until
it finds a template.

This is better illustrated with an example. Take for instance our website that has two page types `Page` and `HomePage`.

Our site looks mostly the same across both templates with just the main content in the middle changing. The header, 
footer and navigation will remain the same and we don't want to replicate this work across more than one spot. The 
`$Layout` function allows us to define the child template area which can be overridden.

**app/templates/Page.ss**

```ss
<html>
<head>
    ..
</head>

<body>
    <% include Header %>
    <% include Navigation %>

    $Layout

    <% include Footer %>
</body>
```

**app/templates/Layout/Page.ss**

```ss
<p>You are on a $Title page</p>

$Content
```

**app/templates/Layout/HomePage.ss**

```ss
<h1>This is the homepage!</h1>

<blink>Hi!</blink>
```

If your classes have a namespace, the Layout folder will be found inside of the appropriate namespaced folder.

For example, the layout template for `SilverStripe\Control\Controller` will be
found at `templates/SilverStripe/Control/Layout/Controller.ss`.

## Cascading themes

Within each theme or templates folder, a specific path representing a template can potentially be found. As
there may be multiple instances of any matching path for a template across the set of all themes, a cascading
search is done in order to determine the resolved template for any specified string.

In order to declare the priority for this search, themes can be declared in a cascading fashion in order
to determine resolution priority. This search is based on the following three configuration values:

 - `SilverStripe\View\SSViewer.themes` - The list of all themes in order of priority (highest first).
   This includes the default set via `$default` as a theme set. This config is normally set by the web
   developer.
 - `SilverStripe\Core\Manifest\ModuleManifest.module_priority` - The list of modules within which $default
   theme templates should be sorted, in order of priority (highest first). This config is normally set by
   the module author, and does not normally need to be customised. This includes the `$project` and
   `$other_modules` placeholder values.
 - `SilverStripe\Core\Manifest\ModuleManifest.project` - The name of the `$project` module, which
   defaults to `app`.

### ThemeResourceLoader

The resolution of themes is performed by a [ThemeResourceLoader](api:SilverStripe\View\ThemeResourceLoader) 
instance, which resolves a template (or list of templates) and a set of themes to a system template path.

For each path the loader will search in this order:

 - Loop through each theme which is configured.
 - If a theme is a set (declared with the `$` prefix, e.g. `$default`) it will perform a nested search within 
   that set.
 - When searching the `$default` set, all modules will be searched in the order declared via the `module_priority`
   config, interpolating keys `$project` and `$other_modules` as necessary.
 - When the first template is found, it will be immediately returned, and will not continue to search. 

### Declaring themes

All themes can be enabled and sorted via the `SilverStripe\View\SSViewer.themes` config value. For reference
on what syntax styles you can use for this value please see the [themes configuration](./themes) documentation.

Basic example:

```yaml
---
Name: mytheme
---
SilverStripe\View\SSViewer:
  themes:
    - theme_name
    - '$default'
```

### Declaring module priority

The order in which templates are selected from themes can be explicitly declared
through configuration. To specify the order you want, make a list of the module
names under `SilverStripe\Core\Manifest\ModuleManifest.module_priority` in a
configuration YAML file.

Note: In order for modules to sort relative to other modules, it's normally necessary
to provide `before:` / `after:` declarations.

*mymodule/_config.yml*

```yml
Name: modules-mymodule
After:
  - '#modules-framework'
  - '#modules-other'
---
SilverStripe\Core\Manifest\ModuleManifest:
  module_priority:
    - myvendor/mymodule
```

In this example, our module has applied its priority lower than framework modules, meaning template lookup
will only defer to our modules templates folder if not found elsewhere.

### Declaring project

The default project structure contains an `app/` folder,
which also acts as as a module in terms of template priorities.
See [/getting_started/directory_structure](Directory Structure)
to find out how to rename this folder.

### About module "names"

Module names are derived their local `composer.json` files using the following precedence:
* The value of the `name` attribute in `composer.json`
* The value of `extras.installer_name` in `composer.json`
* The basename of the directory that contains the module

## Related Lessons
* [Working with multiple templates](https://www.silverstripe.org/learn/lessons/v4/working-with-multiple-templates-1)
