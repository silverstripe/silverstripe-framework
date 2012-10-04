# Configuration in SilverStripe

## Introduction

SilverStripe 3 comes with a comprehensive code based configuration system.

Configuration can be seen as separate from other forms of variables (such as per-member or per-site settings) in the
SilverStripe system due to three properties:

  - Configuration is per class, not per instance

  - Configuration is normally set once during initialisation and then not changed

  - Configuration is normally set by a knowledgeable technical user, such as a developer, not the end user

In SilverStripe 3, each class has it's configuration specified as set of named properties and associated values. The
values at any given time are calculated by merging several sources using rules explained below. These sources are:

  - Values set via a call to Config#update

  - Values taken from YAML files in specially named directories

  - Statics set on an "extra config source" class (such as an extension) named the same as the name of the property
    (optionally)

  - Statics set on the class named the same as the name of the property

  - The parent of the class (optionally)

Some things to keep in mind when working with the configuration system

  - Like statics, configuration values may only contain a literal or constant; neither objects nor expressions are
    allowed

  - The list of properties that can be set on a class is not pre-defined, and there is no way to introspect the list
    of properties or the expected type of any property

  - There is no way currently to restrict read or write access to any configuration property

  - There is no way currently to mutate or intercept read or write access to any configuration property - that is
    (for example) there is no support for getters or setters

## The merge

Each named class configuration property can contain either

- An array
- A non-array value

If the value is an array, each value in the array may also be one of those three types

As mentioned, this value of any specific class configuration property comes from several sources. These sources do not
override each other (except in one specific circumstance) - instead the values from each source are merged together
to give the final configuration value, using these rules:

- If the value is an array, each array is added to the _beginning_ of the composite array in ascending priority order.
  If a higher priority item has a non-integer key which is the same as a lower priority item, the value of those items
  is merged using these same rules, and the result of the merge is located in the same location the higher priority item
  would be if there was no key clash. Other than in this key-clash situation, within the particular array, order is preserved.

- If the value is not an array, the highest priority value is used without any attempt to merge

It is an error to have mixed types of the same named property in different locations (but an error will not necessarily
be raised due to optimisations in the lookup code)

The exception to this is "false-ish" values - empty arrays, empty strings, etc. When merging a non-false-ish value with a
false-ish value, the result will be the non-false-ish value regardless of priority. When merging two false-sh values
the result will be the higher priority false-ish value.

The locations that configuration values are taken from in highest -> lowest priority order are:

- Any values set via a call to Config#update

- The configuration values taken from the YAML files in _config directories (internally sorted in before / after order, where
  the item that is latest is highest priority)

- Any static set on an "additional static source" class (such as an extension) named the same as the name of the property

- Any static set on the class named the same as the name of the property

- The composite configuration value of the parent class of this class

At some of these levels you can also set masks. These remove values from the composite value at their priority point rather than add.
They are much simpler. They consist of a list of key / value pairs. When applied against the current composite value

- If the composite value is a sequential array, any member of that array that matches any value in the mask is removed

- If the composite value is an associative array, any member of that array that matches both the key and value of any pair in the mask is removed

- If the composite value is not an array, if that value matches any value in the mask it is removed

## Reading and updating configuration via the Config class

The Config class is both the primary manner of getting configuration values and one of the locations you can set
configuration values

### Global access

The first thing you need to do to use the Config class is to get the singleton instance of that class. This can be
done by calling the static method Config::inst(), like so:

	$config = Config::inst();

There are then three public methods available on the instance so obtained

  - Config#get() returns the value of a specified classes' property

  - Config#update() adds additional information into the value of a specified classes' property

  - Config#remove() removes information from the value of a specified classes' property

Note that there is no "set" method. Because of the merge, it is not possible to completely set the value of a classes'
property (unless you're setting it to a true-ish literal). Update adds new values that are treated as the highest
priority in the merge, and remove adds a merge mask that filters out values.

### Short-hand reading and updating configuration for instances of Object children

Within any subclass of Object you can call the config() instance method to get an instance of a proxy object
which accesses the Config class with the class parameter already set.

For instance, instead of writing

	Config::inst()->get($this->class, 'my_property');
	Config::inst()->update($this->class, 'my_other_property', 2);

You can write

	$this->config()->get('my_property');
	$this->config()->update('my_other_property', 2);

## Setting configuration via YAML files

Each module can (in fact, should - see below for why) have a directory immediately underneath the main module
directory called "_config".

Inside this directory you can add yaml files that contain values for the configuration system.

The structure of each yaml file is a series of headers and values separated by YAML document separators. If there
is only one set of values the header can be omitted.

### The header

Each value section of a YAML file has

  - A reference path, made up of the module name, the config file name, and a fragment identifier

  - A set of rules for the value section's priority relative to other value sections

  - A set of rules that might exclude the value section from being used

The fragment identifier component of the reference path and the two sets of rules are specified for each
value section in the header section that immediately preceeds the value section.

#### Reference paths and fragment identifiers

Each value section has a reference path. Each path looks a little like a URL, and is of this form:

	module/file#fragment

"module" is the name of the module this YAML file is in

"file" is the name of this YAML file, stripped of the extension (so for routes.yml, it would be routes)

"fragment" is a specified identifier. It is specified by putting a `Name: {fragment}` key / value pair into the header
section. If you don't specify a name, a random one will be assigned.

This reference path has no affect on the value section itself, but is how other header sections refer to this value
section in their priority chain rules

#### Priorities

Values for a specific class property can be specified in several value sections across several modules. These values are
merged together using the same rules as the configuration system as a whole.

However unlike the configuration system itself, there is no inherent priority amongst the various value sections.

Instead, each value section can have rules that indicate priority. Each rule states that this value section
must come before (lower priority than) or after (higher priority than) some other value section.

To specify these rules you add an "After" and/or "Before" key to the relevant header section. The value for these
keys is a list of reference paths to other value sections. A basic example:

	---
	Name: adminroutes
	After: 'framework/routes#rootroutes', 'framework/routes#coreroutes'
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

A more complex example, taken from framework/_config/routes.yml

	---
	Name: adminroutes
	Before: '*'
	After:
	  - '#rootroutes'
	---
	Director:
	  rules:
	    'admin': 'AdminRootController'
	---

The value section above has two rules:

  - It must be merged in before (lower priority than) all other value sections

  - It must be merged in after (higher priority than) any value section with a fragment name of "rootroutes"

In this case there would appear to be a problem - adminroutes can not be both before all other value sections _and_
after value sections with a name of `rootroutes`. However because `\*` has three wildcards
(it is the equivalent of `\*/\*#\*`) but `#rootroutes` only has two (it is the equivalent of `\*/\*#rootroutes`),
`\*` in this case means "every value section _except_ ones that have a fragment name of rootroutes"

One important thing to note: it is possible to create chains that are unsolvable. For instance, A must be before B,
B must be before C, C must be before A. In this case you will get an error when accessing your site.

#### Exclusionary rules

Some value sections might only make sense under certain environmental conditions - a class exists, a module is installed,
an environment variable or constant is set, or SilverStripe is running in a certain environment mode (live, dev, etc)

To accommodate this, value sections can be filtered to only be used when either a rule matches or doesn't match the
current environment.

To achieve this you add a key to the related header section, either "Only" when the value section should be included
only when the rules contained match, or "Except" when the value section should be included except when the rules
contained match.

You then list any of the following rules as sub-keys, with informational values as either a single value or a list.

  - 'classexists', in which case the value(s) should be classes that must exist

  - 'moduleexists', in which case the value(s) should be modules that must exist

  - 'environment', in which case the value(s) should be one of "live", "test" or "dev" to indicate the SilverStripe
    mode the site must be in

  - 'envvarset', in which case the value(s) should be environment variables that must be set

  - 'constantdefined', in which case the value(s) should be constants that must be defined

For instance, to add a property to "foo" when a module exists, and "bar" otherwise, you could do this:

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

### The values

The values section of YAML configuration files is quite simple - it is simply a nested key / value pair structure
where the top level key is the class name to set the property on, and the sub key / value pairs are the properties
and values themselves (where values of course can themselves be nested hashes).

A simple example setting a property called "foo" to the scalar "bar" on class "MyClass", and a property called "baz"
to a nested array on class "MyOtherClass".

	MyClass:
	  foo: 'bar'
	MyOtherClass:
	  baz:
	    a: 1
	    b: 2

Notice that we can leave out the header in this case because there is only a single value section within the file.

## Setting configuration via statics

The final location that a property can get it's value from is a static set on the associated class.

Statics should be considered immutable. Although in 3.0 the configuration system will include modified
statics during the merge, this is not guaranteed to always be the case.

They should primarily be used to set the initial or default value for any given configuration property. It's also
a handy place to hand a docblock to indicate what a property is for. However, it's worth noting that you
do not have to define a static for a property to be valid.

## Configuration as a module marker

Configuration files also have a secondary sub-role. Modules are identified by the `[api:ManifestBuilder]` by the
presence of a _config directory (or a _config.php file) as a top level item in the module directory.

Although your module may choose not to set any configuration, it must still have a _config directory to be recognised
as a module by the `[api:ManifestBuilder]`, which is required for features such as autoloading of classes and template
detection to work.

## _config.php

In addition to the configuration system described above, each module can provide a file called _config.php
immediately within the module top level directory.

These _config.php files will be included at initialisation, and are a useful way to set legacy configuration
or set configuration based on rules that are more complex than can be encoded in YAML files.

However they should generally be avoided when possible, as they slow initialisation.

Please note that this is the only place where you can put in procedural code - all other functionality is wrapped in
classes (see [common-problems](/installation/common-problems)).

## Legacy configuration - static methods

Some configuration has not yet been moved to the SilverStripe 3 configuration system. The primary way to set this
configuration is to call a static method or set a static variable directly within a _config.php file.

You can call most static methods from _config.php - classes will be loaded as required. Here's a list - **this is
incomplete - please add to it** *Try to keep it in alphabetical order too! :)*

 | Call    |                                                            | Description |
 | ----    |                                                            | ----------- |
 | Authenticator::register_authenticator($authenticator);|              | Enable an authentication method (for more details see [security](/topics/security)). |
 | Authenticator::set_default_authenticator($authenticator); |          | Modify tab-order on login-form.|
 | BBCodeParser::disable_autolink_urls(); |                             | Disables plain hyperlinks from being turned into links when bbcode is parsed. |
 | DataObject::$create_table_options['MySQLDatabase'] = 'ENGINE=MyISAM';|	| Set the default database engine to MyISAM (versions 2.4 and below already default to MyISAM) |
 | Debug::send_errors_to(string $email) |								| Send live errors on your site to this address (site has to be in 'live' mode using Director::set_environment_type(live) for this to occur |
 | Director::set_environment_type(string dev,test,live) | 				| Sets the environment type (e.g. dev site will show errors, live site hides them and displays a 500 error instead) |
 | Director::set_dev_servers(array('localhost', 'dev.mysite.com)) |     | Set servers that should be run in dev mode (see [debugging](debugging)) |
 | Director::addRules(int priority, array rules) |	                    | Create a number of URL rules to be checked against when SilverStripe tries to figure out how to display a page. See cms/_config.php for some examples. Note: Using ->something/ as the value for one of these will redirect the user to the something/ page. |
 | Email::setAdminEmail(string $adminemail)  |                          | Sets the admin email for the site, used if there is no From address specified, or when you call Email::getAdminEmail() |
 | Email::send_all_emails_to(string $email)  |                          | Sends all emails to this address. Useful for debugging your email sending functions  |
 | Email::cc_all_emails_to(string $email)  |                            | Useful for CC'ing all emails to someone checking correspondence |
 | Email::bcc_all_emails_to(string $email) |                            | BCC all emails to this address, similar to CC'ing emails (above)  |
 | Requirements::set_suffix_requirements(false); |                      | Disable appending the current date to included files |
 | Security::encrypt_passwords($encrypt_passwords);  |                  | Specify if you want store your passwords in clear text or encrypted (for more details see [security](/topics/security)) |
 | Security::set_password_encryption_algorithm($algorithm, $use_salt);| | If you choose to encrypt your passwords, you can choose which algorithm is used to and if a salt should be used to increase the security level even more (for more details see [security](/topics/security)). |
 | Security::setDefaultAdmin('admin','password'); |                     | Set default admin email and password, helpful for recovering your password |
 | SSViewer::set_theme(string $themename) |                             | Choose the default theme for your site |

## Constants

Some constants are user-defineable within *_ss_environment.php*.

 | Name  |																| Description |
 | ----  |																| ----------- |
 | *TEMP_FOLDER* |														| Absolute file path to store temporary files such as cached templates or the class manifest. Needs to be writeable by the webserver user. Defaults to *sys_get_temp_dir()*, and falls back to *silverstripe-cache* in the webroot. See *getTempFolder()* in *framework/core/Core.php* |


## No GUI configuration

SilverStripe framework does not provide a method to set configuration via a web panel

This lack of a configuration-GUI is on purpose, as we'd like to keep developer-level options where they belong (into
code), without cluttering up the interface. See this core forum discussion ["The role of the
CMS"](http://www.silverstripe.org/archive/show/532) for further reasoning.

In addition to these principle, some settings are 
 * Author-level configuration like interface language or date/time formats can be performed in the CMS "My Profile" section (`admin/myprofile`). 
 * Group-related configuration like `[api:HTMLEditorField]` settings can be found in the "Security" section (`admin/security`).
 * Site-wide settings like page titles can be set (and extended) on the root tree element in the CMS "Content" section (through the [siteconfig](/reference/siteconfig) API).

## _ss_environment.php

See [environment-management](/topics/environment-management).


## User-level: Member-object

All user-related preferences are stored as a property of the `[api:Member]`-class (and as a database-column in the
*Member*-table). You can "mix in" your custom preferences by using `[api:DataObject]` for details.

## Permissions

See [security](/topics/security) and [permission](/reference/permission)

## Resource Usage (Memory and CPU)

SilverStripe tries to keep its resource usage within the documented limits (see our [server requirements](../installation/server-requirements)).
These limits are defined through `memory_limit` and `max_execution_time` in the PHP configuration.
They can be overwritten through `ini_set()`, unless PHP is running with the [Suhoshin Patches](http://www.hardened-php.net/)
or in "[safe mode](http://php.net/manual/en/features.safe-mode.php)".
Most shared hosting providers will have maximum values that can't be altered.

For certain tasks like synchronizing a large `assets/` folder with all file and folder entries in the database,
more resources are required temporarily. In general, we recommend running resource intensive tasks
through the [commandline](../topics/commandline), where configuration defaults for these settings are higher or even unlimited.

SilverStripe can request more resources through `increase_memory_limit_to()` and `increase_time_limit_to()`.
If you are concerned about resource usage on a dedicated server (without restrictions imposed through shared hosting providers), you can set a hard limit to these increases through
`set_increase_memory_limit_max()` and `set_increase_time_limit_max()`.
These values will just be used for specific scripts (e.g. `[api:Filesystem::sync()]`),
to raise the limits for all executed scripts please use `ini_set('memory_limit', <value>)`
and `ini_set('max_execution_time', <value>)` in your own `_config.php`.

## See Also

[Config Cheat sheet](http://www.ssbits.com/a-config-php-cheatsheet/)
