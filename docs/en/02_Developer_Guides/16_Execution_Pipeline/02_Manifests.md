title: Manifests
summary: Manage caches of file path maps and other expensive information

# Manifests

## Purpose

Manifests help to cache information which is too expensive to generate on each request.
Some manifests generate maps, e.g. class names to filesystem locations.
Others store aggregate information like nested configuration graphs.

## Storage

By default, manifests are stored on the local filesystem through PHP's `serialize()` method. 
Combined with PHP opcode caching this provides fast access.
In order to share manifests between servers, or centralise cache management,
other storage adapters are available. These can be configured by a `SS_MANIFESTCACHE` constant,
placed in your `_ss_environment.php`.

 * `ManifestCache_File`: The default adapter using PHP's `serialize()`
 * `ManifestCache_File_PHP`: Using `var_export()`, which is faster when a PHP opcode cache is installed
 * `ManifestCache_APC`: Use PHP's [APC object cache](http://php.net/manual/en/book.apc.php)

You can write your own adapters by implementing the `ManifestCache` interface.

## Traversing the Filesystem

Since manifests usually extract their information from files in the webroot,
they require a powerful traversal tool: `[api:SS_FileFinder]`.
The class provides filtering abilities for files and folders, as well as
callbacks for recursive traversal. Each manifest has its own implementation,
for example `[api:ManifestFileFinder]`, adding more domain specific filtering
like including themes, or excluding testss.

## PHP Class Manifest

The `[api:ClassManifest]` builds a manifest of all classes, interfaces and some
additional items present in a directory, and caches it.

It finds the following information:

 * Class and interface names and paths
 * All direct and indirect descendants of a class
 * All implementors of an interface
 * All module configuration files

The gathered information can be accessed through `[api:SS_ClassLoader::instance()]`,
as well as `[api:ClassInfo]`. Some useful commands of the `ClassInfo` API:

 * `ClassInfo::subclassesFor($class)`: Returns a list of classes that inherit from the given class
 * `ClassInfo::ancestry($class)`: Returns the passed class name along with all its parent class names
 * `ClassInfo::implementorsOf($interfaceName)`: Returns all classes implementing the passed in interface

In the absence of a generic module API, it is also the primary way to identify
which modules are installed, through `[api:ClassManifest::getModules()]`.
A module is defined as a toplevel folder in the webroot which contains
either a `_config.php` file, or a `_config/` folder. Modules can be specifically
excluded from manifests by creating a blank `_manifest_exclude` file in the module folder.

By default, the finder implementation will exclude any classes stored in files within
a `tests/` folder, unless tests are executed.

## Template Manifest

The `[api:SS_TemplateManifest]` class builds a manifest of all templates present in a directory,
in both modules and themes. Templates in `tests/` folders are automatically excluded.
The chapter on [template inheritance](../templates/template-inheritance) provides more details
on its operation.

## Config Manifest

The `[api:SS_ConfigManifest]` loads builds a manifest of configuration items,
for both PHP and YAML. It also takes care of ordering and merging configuration fragments.
The chapter on [configuration](/topics/configuration) has more details.

## Flushing

If a `?flush=1` query parameter is added to a URL, a call to `flush()` will be triggered
on any classes that implement the [Flushable](flushable) interface.
This enables developers to clear [manifest caches](manifests),
for example when adding new templates or PHP classes.
Note that you need to be in [dev mode](/getting_started/environment_management)
or logged-in as an administrator for flushing to take effect.