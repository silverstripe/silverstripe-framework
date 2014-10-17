title: Configuration API
summary: SilverStripe's YAML based Configuration API for setting runtime configuration.

# Configuration API

SilverStripe comes with a comprehensive code based configuration system through the [api:Config] class. It primarily 
relies on declarative [YAML](http://en.wikipedia.org/wiki/YAML) files, and falls back to procedural PHP code, as well 
as PHP static variables.

The Configuration API can be seen as separate from other forms of variables in the SilverStripe system due to three 
properties API:

  - Configuration is **per class**, not per instance.
  - Configuration is normally set once during initialization and then not changed.
  - Configuration is normally set by a knowledgeable technical user, such as a developer, not the end user.

<div class="notice" markdown="1">
For providing content editors or CMS users a place to manage configuration see the [SiteConfig](siteconfig) module.
</div>

## Configuration Properties

Configuration values are static properties on any SilverStripe class. These should be at the top of the class and 
marked with a `@config` docblock. The API documentation will also list the static properties for the class. They should
be marked `private static` and follow the `lower_case_with_underscores` structure.

**mysite/code/MyClass.php**

	:::php
	<?php

	class MyClass extends Page {

		/**
		 * @config
		 */
		private static $option_one = true;

		/**
		 * @config
		 */
		private static $option_two = array();

		// ..
	}

## Accessing and Setting Configuration Properties

This can be done by calling the static method `[api:Config::inst]`, like so:

	:::php
	$config = Config::inst()->get('MyClass');

Or through the `config()` object on the class.
	
	$config = $this->config();

There are three public methods available on the instance. `get($class, $variable)`, `remove($class, $variable)` and
`update($class, $variable, $value)`.

<div class="notice" markdown="1">
There is no "set" method. It is not possible to completely set the value of a classes' property. `update` adds new 
values that are treated as the highest priority in the merge, and remove adds a merge mask that filters out values.
</div>

To set those configuration options on our previously defined class we can define it in a `YAML` file.

**mysite/_config/app.yml**

	:::yml
	MyClass:
	  option_one: false
	  option_two:
	    - Foo
	    - Bar
	    - Baz

To use those variables in your application code:

	:::php
	$me = new MyClass();

	echo $me->config()->option_one;
	// returns false

	echo implode(', ', $me->config()->option_two);
	// returns 'Foo, Bar, Baz'

	echo Config::inst()->get('MyClass', 'option_one');
	// returns false

	echo implode(', ', Config::inst()->get('MyClass', 'option_two'));
	// returns 'Foo, Bar, Baz'

	Config::inst()->update('MyClass', 'option_one', true);

	echo Config::inst()->get('MyClass', 'option_one');
	// returns true

	// You can also use the static version
	MyClass::config()->option_two = array(
		'Qux'
	);

	echo implode(', ', MyClass::config()->option_one);
	// returns 'Qux'

<div class="notice" markdown="1">
There is no way currently to restrict read or write access to any configuration property, or influence/check the values 
being read or written.
</div>

## Configuration Values

Each configuration property can contain either a literal value (`'foo'`), integer (`2`), boolean (`true`) or an array. 
If the value is an array, each value in the array may also be one of those types.

The value of any specific class configuration property comes from several sources. These sources do not override each 
other - instead the values from each source are merged together to give the final configuration value, using these 
rules:

- If the value is an array, each array is added to the _beginning_ of the composite array in ascending priority order.
  If a higher priority item has a non-integer key which is the same as a lower priority item, the value of those items
  is merged using these same rules, and the result of the merge is located in the same location the higher priority item
  would be if there was no key clash. Other than in this key-clash situation, within the particular array, order is preserved.
- If the value is not an array, the highest priority value is used without any attempt to merge


<div class="alert" markdown="1">
The exception to this is "false-ish" values - empty arrays, empty strings, etc. When merging a non-false-ish value with 
a false-ish value, the result will be the non-false-ish value regardless of priority. When merging two false-ish values
the result will be the higher priority false-ish value.
</div>

The locations that configuration values are taken from in highest -> lowest priority order are:

- Any values set via a call to Config#update
- The configuration values taken from the YAML files in `_config/` directories (internally sorted in before / after 
order, where the item that is latest is highest priority)
- Any static set on an "additional static source" class (such as an extension) named the same as the name of the property
- Any static set on the class named the same as the name of the property
- The composite configuration value of the parent class of this class

<div class="notice">
It is an error to have mixed types of the same named property in different locations. An error will not necessarily
be raised due to optimizations in the lookup code.
</div>

## Configuration Masks

At some of these levels you can also set masks. These remove values from the composite value at their priority point 
rather than add.

	$actionsWithoutExtra = $this->config()->get(
		'allowed_actions', Config::UNINHERITED
	);

They are much simpler. They consist of a list of key / value pairs. When applied against the current composite value

- If the composite value is a sequential array, any member of that array that matches any value in the mask is removed
- If the composite value is an associative array, any member of that array that matches both the key and value of any 
pair in the mask is removed
- If the composite value is not an array, if that value matches any value in the mask it is removed


## Configuration YAML Syntax and Rules

Each module can have a directory immediately underneath the main module directory called `_config/`. Inside this 
directory you can add YAML files that contain values for the configuration system. 

<div class="info" markdown="1">
The name of the files within the applications `_config` directly are arbitrary. Our examples use 
`mysite/_config/app.yml` but you can break this file down into smaller files, or clearer patterns like `extensions.yml`, 
`email.yml` if you want. For add-on's and modules, it is recommended that you name them with `<module_name>.yml`.
</div>

The structure of each YAML file is a series of headers and values separated by YAML document separators. 

	:::yml
	---
	Name: adminroutes
	After:
  	  - '#rootroutes'
  	  - '#coreroutes'
	---
	Director:
	  rules:
	    'admin': 'AdminRootController'
	---

<div class="info">
If there is only one set of values the header can be omitted.
</div>

Each value section of a YAML file has:

  - A reference path, made up of the module name, the config file name, and a fragment identifier Each path looks a 
  little like a URL and is of this form: `module/file#fragment`.
  - A set of rules for the value section's priority relative to other value sections
  - A set of rules that might exclude the value section from being used

The fragment identifier component of the reference path and the two sets of rules are specified for each value section 
in the header section that immediately precedes the value section.

 - "module" is the name of the module this YAML file is in.
 - "file" is the name of this YAML file, stripped of the extension (so for routes.yml, it would be routes).
 - "fragment" is a specified identifier. It is specified by putting a `Name: {fragment}` key / value pair into the 
 header section. If you don't specify a name, a random one will be assigned.

This reference path has no affect on the value section itself, but is how other header sections refer to this value
section in their priority chain rules.

## Before / After Priorities

Values for a specific class property can be specified in several value sections across several modules. These values are
merged together using the same rules as the configuration system as a whole.

However unlike the configuration system, there is no inherent priority amongst the various value sections.

Instead, each value section can have rules that indicate priority. Each rule states that this value section must come 
before (lower priority than) or after (higher priority than) some other value section.

To specify these rules you add an "After" and/or "Before" key to the relevant header section. The value for these
keys is a list of reference paths to other value sections. A basic example:

	:::yml
	---
	Name: adminroutes
	After:
  	  - '#rootroutes'
  	  - '#coreroutes'
	---
	Director:
	  rules:
	    'admin': 'AdminRootController'
	---

You do not have to specify all portions of a reference path. Any portion may be replaced with a wildcard "\*", or left
out all together. Either has the same affect - that portion will be ignored when checking a value section's reference
path, and will always match. You may even specify just "\*", which means "all value sections".

When a particular value section matches both a Before _and_ an After rule, this may be a problem. Clearly
one value section can not be both before _and_ after another. However when you have used wildcards, if there
was a difference in how many wildcards were used, the one with the least wildcards will be kept and the other one
ignored.

The value section above has two rules:

  - It must be merged in before (lower priority than) all other value sections
  - It must be merged in after (higher priority than) any value section with a fragment name of "rootroutes"

In this case there would appear to be a problem - adminroutes can not be both before all other value sections _and_
after value sections with a name of `rootroutes`. However because `\*` has three wildcards
(it is the equivalent of `\*/\*#\*`) but `#rootroutes` only has two (it is the equivalent of `\*/\*#rootroutes`). 

In this case `\*` means "every value section _except_ ones that have a fragment name of rootroutes".

<div class="alert" markdown="1">
It is possible to create chains that are unsolvable. For instance, A must be before B, B must be before C, C must be 
before A. In this case you will get an error when accessing your site.
</div>

## Exclusionary rules

Some value sections might only make sense under certain environmental conditions - a class exists, a module is 
installed, an environment variable or constant is set, or SilverStripe is running in a certain environment mode (live, 
dev, etc).

To accommodate this, value sections can be filtered to only be used when either a rule matches or doesn't match the
current environment.

To achieve this, add a key to the related header section, either `Only` when the value section should be included
only when all the rules contained match, or `Except` when the value section should be included except when all of the
rules contained match.

You then list any of the following rules as sub-keys, with informational values as either a single value or a list.

  - 'classexists', in which case the value(s) should be classes that must exist
  - 'moduleexists', in which case the value(s) should be modules that must exist
  - 'environment', in which case the value(s) should be one of "live", "test" or "dev" to indicate the SilverStripe
    mode the site must be in
  - 'envvarset', in which case the value(s) should be environment variables that must be set
  - 'constantdefined', in which case the value(s) should be constants that must be defined

For instance, to add a property to "foo" when a module exists, and "bar" otherwise, you could do this:

	:::yml
	---
	Only:
	  moduleexists: 'MyFineModule'
	---
	MyClass:
	  property: 'foo'
	---
	Except:
	  moduleexists: 'MyFineModule'
	---
	MyClass:
	  property: 'bar'
	---

<div class="alert" markdown="1">
When you have more than one rule for a nested fragment, they're joined like 
`FRAGMENT_INCLUDED = (ONLY && ONLY) && !(EXCEPT && EXCEPT)`. 
That is, the fragment will be included if all Only rules match, except if all Except rules match.
</div>


<div class="alert" markdown="1">
Due to YAML limitations, having multiple conditions of the same kind (say, two `EnvVarSet` in one "Only" block)
will result in only the latter coming through.
</div>


## API Documentation

* [api:Config]