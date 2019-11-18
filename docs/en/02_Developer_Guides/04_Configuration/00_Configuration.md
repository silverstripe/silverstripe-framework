---
title: Configuration API
summary: Silverstripe CMS's YAML based Configuration API for setting runtime configuration.
icon: laptop-code
---

# Configuration API

SilverStripe comes with a comprehensive code based configuration system through the [Config](api:SilverStripe\Core\Config\Config) class. It primarily 
relies on declarative [YAML](http://en.wikipedia.org/wiki/YAML) files, and falls back to procedural PHP code, as well 
as PHP static variables. This is provided by the [silverstripe/config](https://github.com/silverstripe/silverstripe-config)
library.

The Configuration API can be seen as separate from other forms of variables in the SilverStripe system due to three 
properties API:

  - Configuration is **per class**, not per instance.
  - Configuration is normally set once during initialization and then not changed.
  - Configuration is normally set by a knowledgeable technical user, such as a developer, not the end user.

[notice]
For providing content editors or CMS users a place to manage configuration see the [SiteConfig](siteconfig) module.
[/notice]

## Configuration Properties

Configuration values are static properties on any SilverStripe class. These should be at the top of the class and 
marked with a `@config` docblock. The API documentation will also list the static properties for the class. They should
be marked `private static` and follow the `lower_case_with_underscores` structure.

**app/code/MyClass.php**


```php
class MyClass extends Page 
{

    /**
     * @config
     */
    private static $option_one = true;

    /**
     * @config
     */
    private static $option_two = [];
}

```

## Accessing and Setting Configuration Properties

This can be done by calling the static method [Config::inst()](api:SilverStripe\Core\Config\Config::inst()), like so:


```php
$config = Config::inst()->get('MyClass', 'property');
```

Or through the `config()` method on the class.

```php
$config = $this->config()->get('property');
```

You may need to apply the [Configurable](api:SilverStripe\Core\Config\Configurable) trait in order to access the `config()` method.

**app/code/MyOtherClass.php**

```php
use SilverStripe\Core\Config\Configurable;

class MyOtherClass 
{
    use Configurable;
 
}
```


Note that by default `Config::inst()` returns only an immutable version of config. Use `Config::modify()`
if it's necessary to alter class config. This is generally undesirable in most applications, as modification
of the config can immediately have performance implications, so this should be used sparingly, or
during testing to modify state.

Note that while both objects have similar methods the APIs differ slightly. The below actions are equivalent:

  * `Config::inst()->get('Class', 'property');` or `Class::config()->get('property')`
  * `Config::inst()->uninherited('Class', 'property');` or `Class::config()->get('property', Config::UNINHERITED)`
  * `Config::inst()->exists('Class', 'property');` or `Class::config()->exists('property')`
  
And mutable methods:

  * `Config::modify()->merge('Class', 'property', 'newvalue');` or `Class::config()->merge('property', 'newvalue')`
  * `Config::modify()->set('Class', 'property', 'newvalue');` or `Class::config()->set('property', 'newvalue')`
  * `Config::modify()->remove('Class', 'property');` or `Class::config()->remove('property')`

To set those configuration options on our previously defined class we can define it in a `YAML` file.

**app/_config/app.yml**


```yml
MyClass:
  option_one: false
  option_two:
    - Foo
    - Bar
    - Baz
```

To use those variables in your application code:


```php
$me = new MyClass();

echo $me->config()->option_one;
// returns false

echo implode(', ', $me->config()->option_two);
// returns 'Foo, Bar, Baz'

echo Config::inst()->get('MyClass', 'option_one');
// returns false

echo implode(', ', Config::inst()->get('MyClass', 'option_two'));
// returns 'Foo, Bar, Baz'

Config::modify()->set('MyClass', 'option_one', true);

echo Config::inst()->get('MyClass', 'option_one');
// returns true

// You can also use the static version
MyClass::config()->option_two = [
    'Qux'
];

echo implode(', ', MyClass::config()->option_one);
// returns 'Qux'

```

[notice]
There is no way currently to restrict read or write access to any configuration property, or influence/check the values 
being read or written.
[/notice]

## Configuration Values

Each configuration property can contain either a literal value (`'foo'`), integer (`2`), boolean (`true`) or an array. 
If the value is an array, each value in the array may also be one of those types.

The value of any specific class configuration property comes from several sources. These sources do not override each 
other - instead the values from each source are merged together to give the final configuration value, using these 
rules:

- If the value is an array, each array is added to the _beginning_ of the composite array in ascending priority order.
  If a higher priority item has a non-integer key which is the same as a lower priority item, the value of those items
  is merged using these same rules, and the result of the merge is located in the same location the higher priority item
  would be if there was no key clash. Other than in this key-clash situation, within the particular array, order is preserved. To override a value that is an array, the value must first be set to `null`, and then set again to the new array.
```yml
---
Name: arrayreset
---
Class\With\Array\Config:
  an_array: null
---
Name: array
---
Class\With\Array\Config:
  an_array: ['value_a', 'value_b']
```

- If the value is not an array, the highest priority value is used without any attempt to merge


[alert]
The exception to this is "false-ish" values - empty arrays, empty strings, etc. When merging a non-false-ish value with 
a false-ish value, the result will be the non-false-ish value regardless of priority. When merging two false-ish values
the result will be the higher priority false-ish value.
[/alert]

The locations that configuration values are taken from in highest -> lowest priority order are:

- Runtime modifications, ie: any values set via a call to `Config::inst()->update()`
- The configuration values taken from the YAML files in `_config/` directories (internally sorted in before / after 
order, where the item that is latest is highest priority)
- Any static set on the class named the same as the name of the property
- The composite configuration value of the parent class of this class
- Any static set on an "additional static source" class (such as an extension) named the same as the name of the property

[notice]
It is an error to have mixed types of the same named property in different locations. An error will not necessarily
be raised due to optimizations in the lookup code.
[/notice]

## Configuration Masks

At some of these levels you can also set masks. These remove values from the composite value at their priority point 
rather than add.

```php
$actionsWithoutExtra = $this->config()->get(
    'allowed_actions', Config::UNINHERITED
);
```

Available masks include:

  * Config::UNINHERITED - Exclude config inherited from parent classes
  * Config::EXCLUDE_EXTRA_SOURCES - Exclude config applied by extensions

You can also pass in literal `true` to disable all extra sources, or merge config options with
bitwise `|` operator.

## Configuration YAML Syntax and Rules

[alert]
As of Silverstripe 4, YAML files can no longer be placed any deeper than 2 directories deep. As this was an unintended bug, this change will only affect you if you nest your modules deeper than the top level of your project.
[/alert]

Each module can have a directory immediately underneath the main module directory called `_config/`. Inside this 
directory you can add YAML files that contain values for the configuration system. 

[info]
The name of the files within the applications `_config` directly are arbitrary. Our examples use 
`app/_config/app.yml` but you can break this file down into smaller files, or clearer patterns like `extensions.yml`, 
`email.yml` if you want. For add-on's and modules, it is recommended that you name them with `<module_name>.yml`.
[/info]

The structure of each YAML file is a series of headers and values separated by YAML document separators.

```yml
---
Name: adminroutes
After:
    - '#rootroutes'
    - '#coreroutes'
---
SilverStripe\Control\Director:
  rules:
    'admin': 'SilverStripe\Admin\AdminRootController'
---
```

[info]
If there is only one set of values the header can be omitted.
[/info]

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


```yml
---
Name: adminroutes
After:
    - '#rootroutes'
    - '#coreroutes'
---
SilverStripe\Control\Director:
  rules:
    'admin': 'SilverStripe\Admin\AdminRootController'
---
```

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

[alert]
It is possible to create chains that are unsolvable. For instance, A must be before B, B must be before C, C must be 
before A. In this case you will get an error when accessing your site.
[/alert]

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
  - 'moduleexists', in which case the value(s) should be modules that must exist. This supports either folder
    name or composer `vendor/name` format.
  - 'environment', in which case the value(s) should be one of "live", "test" or "dev" to indicate the SilverStripe
    mode the site must be in
  - 'envvarset', in which case the value(s) should be environment variables that must be set
  - 'constantdefined', in which case the value(s) should be constants that must be defined
  - 'envorconstant' A variable which should be defined either via environment vars or constants
  - 'extensionloaded', in which case the PHP extension(s) must be loaded

For instance, to add a property to "foo" when a module exists, and "bar" otherwise, you could do this:


```yml
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
```

Multiple conditions of the same type can be declared via array format

```yaml
---
Only:
  moduleexists:
    - 'silverstripe/blog'
    - 'silverstripe/lumberjack'
---
```

[alert]
When you have more than one rule for a nested fragment, they're joined like 
`FRAGMENT_INCLUDED = (ONLY && ONLY) && !(EXCEPT && EXCEPT)`. 
That is, the fragment will be included if all Only rules match, except if all Except rules match.
[/alert]

## Unit tests

Sometimes, it's necessary to change a configuration value in your unit tests.
One way to do this is to use the `withConfig` method.
This is especially handy when using data providers.
Example below shows one unit test using a data provider.
This unit test changes configuration before testing functionality.
The test will run three times, each run with different configuration value.
Note that the configuration change is active only within the callback function.

```php
/**
 * @dataProvider testValuesProvider
 * @param string $value
 * @param string $expected
 */
public function testConfigValues($value, $expected)
{
    $result = Config::withConfig(function(MutableConfigCollectionInterface $config) use ($value) {
        // update your config
        $config->set(MyService::class, 'some_setting', $value);

        // your test code goes here and it runs with your changed config
        return MyService::singleton()->executeSomeFunction();
    });

    // your config change no longer applies here as it's outside of callback

    // assertions can be done here but also inside the callback function
    $this->assertEquals($expected, $result);
}

public function testValuesProvider(): array
{
    return [
        ['test value 1', 'expected value 1'],
        ['test value 2', 'expected value 2'],
        ['test value 3', 'expected value 3'],
    ];
}
```

## API Documentation

* [Config](api:SilverStripe\Core\Config\Config)

## Related Lessons
* [DataExtensions and SiteConfig](https://www.silverstripe.org/learn/lessons/v4/data-extensions-and-siteconfig-1)
