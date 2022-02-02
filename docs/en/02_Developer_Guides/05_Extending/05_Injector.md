---
title: Injector
summary: Introduction to using Dependency Injection within Silverstripe CMS.
icon: code
---

# Injector

The [Injector](api:SilverStripe\Core\Injector\Injector) class is the central manager of inter-class dependencies in Silverstripe CMS. It offers developers the 
ability to declare the dependencies a class type has, or to change the nature of the dependencies defined by other 
developers. 

Some of the goals of dependency injection are:

* Simplified instantiation of objects
* Providing a uniform way of declaring and managing inter-object dependencies
* Making class dependencies configurable
* Simplifying the process of overriding or replacing core behaviour
* Improve testability of code
* Promoting abstraction of logic

The following sums up the simplest usage of the `Injector` it creates a new object of type `MyClassName` through `create`


```php
use SilverStripe\Core\Injector\Injector;

$object = Injector::inst()->create('MyClassName');
```

The benefit of constructing objects through this syntax is `ClassName` can be swapped out using the 
[Configuration API](../configuration) by developers.

**app/_config/app.yml**

```yml
SilverStripe\Core\Injector\Injector:
  MyClassName:
    class: MyBetterClassName
```

Repeated calls to `create()` create a new object each time.


```php
$object = Injector::inst()->create('MyClassName');
$object2 = Injector::inst()->create('MyClassName');

echo $object !== $object2;

// returns true;
```

## Singleton Pattern

The `Injector` API can be used for the singleton pattern through `get()`. Subsequent calls to `get` return the same 
object instance as the first call.


```php
// sets up MyClassName as a singleton
$object = Injector::inst()->get('MyClassName');
$object2 = Injector::inst()->get('MyClassName');

echo ($object === $object2);

// returns true;
```

## Dependencies

The `Injector` API can be used to define the types of `$dependencies` that an object requires.


```php
use SilverStripe\Control\Controller;

class MyController extends Controller 
{

    // both of these properties will be automatically
    // set by the injector on object creation
    public $permissions;
    public $textProperty;

    // we declare the types for each of the properties on the object. Anything we pass in via the Injector API must
    // match these data types.
    static $dependencies = [
        'textProperty'        => 'a string value',
        'permissions'        => '%$PermissionService',
    ];
}
```

When creating a new instance of `MyController` the dependencies on that class will be met.


```php
$object = Injector::inst()->get('MyController');

echo ($object->permissions instanceof PermissionService);
// returns true;

echo (is_string($object->textProperty));
// returns true;
```

The [Configuration YAML](../configuration) does the hard work of configuring those `$dependencies` for us.

**app/_config/app.yml**

```yml
SilverStripe\Core\Injector\Injector:
  PermissionService:
    class: MyCustomPermissionService
  MyController:
    properties:
      textProperty: 'My Text Value'
```

Now the dependencies will be replaced with our configuration.

```php
$object = Injector::inst()->get('MyController');

echo ($object->permissions instanceof MyCustomPermissionService);
// returns true;

echo ($object->textProperty == 'My Text Value');
// returns true;
```

As well as properties, method calls can also be specified:


```yml
SilverStripe\Core\Injector\Injector:
  Logger:
    class: Monolog\Logger
    calls:
      - [ pushHandler, [ %$DefaultHandler ] ]
```

[info]
### Special YML Syntax

You can use the special `%$` prefix in the configuration yml to fetch items via the Injector. For example:

```yml
App\Services\MediumQueuedJobService:
    properties:
      queueRunner: '%$App\Tasks\Engines\MediumQueueAsyncRunner'
```

It is equivalent of calling `Injector::get()->instance(MediumQueueAsyncRunner::class)` and assigning the result to the `MediumQueuedJobService::queueRunner` property. This can be useful as these properties can easily updated if provided in a module or be changed for unit testing. It can also be used to provide constructor arguments such as [this example from the assets module](https://github.com/silverstripe/silverstripe-assets/blob/1/_config/asset.yml):

```yml
SilverStripe\Core\Injector\Injector:
  # Define the secondary adapter for protected assets
  SilverStripe\Assets\Flysystem\ProtectedAdapter:
    class: SilverStripe\Assets\Flysystem\ProtectedAssetAdapter
  # Define the secondary filesystem for protected assets
  League\Flysystem\Filesystem.protected:
    class: League\Flysystem\Filesystem
    constructor:
      FilesystemAdapter: '%$SilverStripe\Assets\Flysystem\ProtectedAdapter'
```
[/info]

## Using constants and environment variables

Any of the core constants can be used as a service argument by quoting with back ticks "`". Please ensure you also quote the entire value (see below).

```yaml
CachingService:
  class: SilverStripe\Cache\CacheProvider
  properties:
    CacheDir: '`TEMP_DIR`'
```

Environment variables can be used in the same way:

```yml
App\Services\MyService:
    class: App\Services\MyService
    constructor:
      baseURL: '`SS_API_URI`'
      credentials:
        id: '`SS_API_CLIENT_ID`'
        secret: '`SS_API_CLIENT_SECRET`'
```

Note: undefined variables will be replaced with null.


## Factories

Some services require non-trivial construction which means they must be created by a factory class. To do this, create
a factory class which implements the [Factory](api:SilverStripe\Framework\Injector\Factory) interface. You can then specify
the `factory` key in the service definition, and the factory service will be used.

An example using the `MyFactory` service to create instances of the `MyService` service is shown below:

**app/_config/app.yml**

```yml
SilverStripe\Core\Injector\Injector:
  MyService:
    factory: MyFactory
```

**app/src/MyFactory.php**


```php
class MyFactory implements SilverStripe\Core\Injector\Factory 
{

    public function create($service, array $params = []) 
    {
        return new MyServiceImplementation();
    }
}

// Will use MyFactoryImplementation::create() to create the service instance.
$instance = Injector::inst()->get('MyService');
```

## Dependency overrides

To override the `$dependency` declaration for a class, define the following configuration file.

**app/_config/app.yml**

```yml
MyController:
  dependencies:
    textProperty: a string value
    permissions: %$PermissionService
```

## Managed objects

Simple dependencies can be specified by the `$dependencies`, but more complex configurations are possible by specifying 
constructor arguments, or by specifying more complex properties such as lists.

These more complex configurations are defined in `Injector` configuration blocks and are read by the `Injector` at 
runtime.

Assuming a class structure such as

```php
class RestrictivePermissionService 
{
    private $database;

    public function setDatabase($d) 
    {    
        $this->database = $d;
    }
}
class MySQLDatabase 
{
    private $username;
    private $password;
    
    public function __construct($username, $password) 
    {
        $this->username = $username;
        $this->password = $password;
    }
}
```

And the following configuration..


```yml
---
name: MyController
---
MyController:
  dependencies:
    permissions: %$PermissionService
SilverStripe\Core\Injector\Injector:
  PermissionService:
    class: RestrictivePermissionService
    properties:
      database: %$MySQLDatabase
  MySQLDatabase:
    constructor:
      0: 'dbusername'
      1: 'dbpassword'
```

Calling..

```php
// sets up ClassName as a singleton
$controller = Injector::inst()->get('MyController');
```

Would setup the following

* Create an object of type `MyController`
* Look through the **dependencies** and call get('PermissionService')
* Load the configuration for PermissionService, and create an object of type `RestrictivePermissionService`
* Look at the properties to be injected and look for the config for `MySQLDatabase`
* Create a MySQLDatabase class, passing dbusername and dbpassword as the parameters to the constructor.

## Service inheritance

By default, services registered with Injector do not inherit from one another; This is because it registers
named services, which may not be actual classes, and thus should not behave as though they were.

Thus if you want an object to have the injected dependencies of a service of another name, you must
assign a reference to that service. References are denoted by using a percent and dollar sign, like in the 
YAML configuration example below.

```yaml
SilverStripe\Core\Injector\Injector:
  JSONServiceDefinition:
    class: JSONServiceImplementor
    properties:
      Serialiser: JSONSerialiser
  GZIPJSONProvider: %$JSONServiceDefinition
```

`Injector::inst()->get('GZIPJSONProvider')` will then be an instance of `JSONServiceImplementor` with the injected
properties.

It is important here to note that the 'class' property of the parent service will be inherited directly as well.
If class is not specified, then the class will be inherited from the outer service name, not the inner service name.

For example with this config:

```yml
SilverStripe\Core\Injector\Injector:
  Connector:
    properties:
      AsString: true
  ServiceConnector: %$Connector
```

Both `Connector` and `ServiceConnector` will have the `AsString` property set to true, but the resulting
instances will be classes which match their respective service names, due to the lack of a `class` specification. 

## Testing with Injector

In situations where injector states must be temporarily overridden, it is possible to create nested Injector instances 
which may be later discarded, reverting the application to the original state. This is done through `nest` and `unnest`.

This is useful when writing test cases, as certain services may be necessary to override for a single method call.


```php
use SilverStripe\Core\Injector\Injector;

// Setup default service
Injector::inst()->registerService(new LiveService(), 'ServiceName');

// Test substitute service temporarily
Injector::nest();

Injector::inst()->registerService(new TestingService(), 'ServiceName');
$service = Injector::inst()->get('ServiceName');
// ... do something with $service

// revert changes
Injector::unnest();
```

## API Documentation

* [Injector](api:SilverStripe\Core\Injector\Injector)
* [Factory](api:SilverStripe\Core\Injector\Factory)
