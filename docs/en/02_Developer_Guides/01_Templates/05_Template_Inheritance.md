title: Template Inheritance
summary: Override and extend module and core markup templates from your application code.

# Template Inheritance

Bundled within SilverStripe are default templates for any markup the framework outputs for things like Form templates,
Emails or RSS Feeds. These templates are provided to make getting your site up and running quick with sensible defaults 
but it's easy to replace and customise SilverStripe (and add-on's) by providing custom templates in your own 
`mysite/templates` folder or in your `themes/your_theme/templates` folder.

Take for instance the `GenericEmail` template in SilverStripe. This is the HTML default template that any email created 
in SilverStripe is rendered with. It's bundled in the core framework at `framework/templates/email/GenericEmail.ss`. 

Instead of editing that file to provide a custom template for your application, simply define a template of the same 
name in the `mysite/templates/email` folder or in the `themes/your_theme/templates/email` folder if you're using themes. 

**mysite/templates/email/GenericEmail.ss**
	
	:::ss
	$Body

	<p>Thanks from Bob's Fantasy Football League.</p>

All emails going out of our application will have the footer `Thanks from Bob's Fantasy Football Leaguee` added.

<div class="alert" markdown="1">
As we've added a new file, make sure you flush your SilverStripe cache by visiting `http://yoursite.com/?flush=1`
</div>

Template inheritance works on more than email templates. All files within the `templates` directory including `includes`, 
`layout` or anything else from core (or add-on's) template directory can be overridden by being located inside your 
`mysite/templates` directory. SilverStripe keeps an eye on what templates have been overridden and the location of the
correct template through a [SS_TemplateManifest](api:SS_TemplateManifest).

## Template Manifest

The location of each template and the hierarchy of what template to use is stored within a [SS_TemplateManifest](api:SS_TemplateManifest) 
instance. This is a serialized object containing a map of template names, paths and other meta data for each template 
and is cached in your applications `TEMP_FOLDER` for performance. For SilverStripe to find the `GenericEmail` template 
it does not check all your `template` folders on the fly, it simply asks the `manifest`. 

The manifest is created whenever you flush your SilverStripe cache by appending `?flush=1` to any SilverStripe URL. For
example by visiting `http://yoursite.com/?flush=1`. When your include the `flush=1` flag, the manifest class will search 
your entire project for the appropriate `.ss` files located in `template` directory and save that information for later.

<div class="warning">
Whenever you add or remove template files, rebuild the manifest by visiting `http://yoursite.com/?flush=1`. You can 
flush the cache from any page, (.com/home?flush=1, .com/admin?flush=1, etc.). Flushing the cache can be slow, so you 
only need to do it when you're developing new templates.
</div>

## Template Priority

The order in which templates are selected from themes can be explicitly declared
through configuration. To specify the order you want, make a list of the module
names under `SilverStripe\Core\Manifest\ModuleManifest.module_priority` in a
configuration YAML file. 


*some-module/_config.yml*
```yml
SilverStripe\Core\Manifest\ModuleManifest:
  module_priority:
    - 'example/module-one'
    - 'example/module-two'
    - '$other_modules'
    - 'example/module-three'
```

The placeholder `$other_modules` is used to mark where all of the modules not specified
in the list should appear. (In alphabetical order of their containing directory names).

In this example, the module named `example/module-one` has the highest level of precedence,
followed by `example/module-two`. The module `example/module-three` is guaranteed the lowest
level of precedence.

### Defining a "project"

It is a good idea to define one of your modules as the `project`. Commonly, this is the
`mysite/` module, but there is nothing compulsory about that module name. The "project"
module can be specified as a variable in the `module_priorities` list, as well. 

*some-module/_config.yml*
```yml
SilverStripe\Core\Manifest\ModuleManifest:
  project: 'myapp'
  module_priority:
    - '$project'
    - '$other_modules'
```

### About module "names"

Module names are derived their local `composer.json` files using the following precedence:
* The value of the `name` attribute in `composer.json`
* The value of `extras.installer_name` in `composer.json`
* The basename of the directory that contains the module

## Nested Layouts through `$Layout`

SilverStripe has basic support for nested layouts through a fixed template variable named `$Layout`. It's used for 
storing top level template information separate to individual page layouts.

When `$Layout` is found within a root template file (one in `templates`), SilverStripe will attempt to fetch a child 
template from the `templates/Layout` directory. It will do a full sweep of your modules, core and custom code as it 
would if it was looking for a new root template.

This is better illustrated with an example. Take for instance our website that has two page types `Page` and `HomePage`.

Our site looks mostly the same across both templates with just the main content in the middle changing. The header, 
footer and navigation will remain the same and we don't want to replicate this work across more than one spot. The 
`$Layout` function allows us to define the child template area which can be overridden.

**mysite/templates/Page.ss**
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
``
**mysite/templates/Layout/Page.ss**
```ss
	<p>You are on a $Title page</p>

	$Content
```
**mysite/templates/Layout/HomePage.ss**
```ss
	<h1>This is the homepage!</h1>

	<blink>Hi!</blink>
```

If your classes have in a namespace, the Layout folder will be a found inside of the appropriate namespace folder.

For example, the layout template for `SilverStripe\Control\Controller` will be
found at `templates/SilverStripe/Control/Layout/Controller.ss`.
