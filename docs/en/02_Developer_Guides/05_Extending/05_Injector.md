title: Injector
summary: Introduction to using Dependency Injection within SilverStripe.

# Injector

The [api:Injector] class is the central manager of inter-class dependencies in SilverStripe. It offers developers the 
ability to declare the dependencies a class type has, or to change the nature of the dependencies defined by other 
developers. 

Some of the goals of dependency injection are:

* Simplified instantiation of objects
* Providing a uniform way of declaring and managing inter-object dependencies
* Making class dependencies configurable
* Simplifying the process of overriding or replacing core behaviour
* Improve testability of code
* Promoting abstraction of logic

The following sums up the simplest usage of the `Injector` it creates a new object of type `ClassName` through `create`

	:::php
	$object = Injector::inst()->create('MyClassName');

The benefit of constructing objects through this syntax is `ClassName` can be swapped out using the 
[Configuration API](../configuration) by developers.

**mysite/_config/app.yml**
	
	:::yml
	Injector:
	  MyClassName:
	    class: MyBetterClassName

Repeated calls to `create()` create a new object each time.

	:::php
	$object = Injector::inst()->create('MyClassName');
	$object2 = Injector::inst()->create('MyClassName');

	echo $object !== $object2;

	// returns true;

## Singleton Pattern

The `Injector` API can be used for the singleton pattern through `get()`. Subsequent calls to `get` return the same 
object instance as the first call.

	:::php
	// sets up MyClassName as a singleton
	$object = Injector::inst()->get('MyClassName');
	$object2 = Injector::inst()->get('MyClassName');

	echo ($object === $object2);

	// returns true;

## Dependencies

The `Injector` API can be used to define the types of `$dependancies` that an object requires.

	:::php 
	<?php

	class MyController extends Controller {
	
		// both of these properties will be automatically
		// set by the injector on object creation
		public $permissions;
		public $textProperty;
	
		// we declare the types for each of the properties on the object. Anything we pass in via the Injector API must
		// match these data types.
		static $dependencies = array(
			'textProperty'		=> 'a string value',
			'permissions'		=> '%$PermissionService',
		);
	}

When creating a new instance of `MyController` the dependencies on that class will be met.

	:::php
	$object = Injector::inst()->get('MyController');
	
	echo ($object->permissions instanceof PermissionService);
	// returns true;

	echo (is_string($object->textProperty));
	// returns true;

The [Configuration YAML](../configuration) does the hard work of configuring those `$dependancies` for us.

**mysite/_config/app.yml**
	
	:::yml
	Injector:
	  PermissionService:
	    class: MyCustomPermissionService
	  MyController
	    properties:
	      textProperty: 'My Text Value'

Now the dependencies will be replaced with our configuration.

	:::php
	$object = Injector::inst()->get('MyController');
	
	echo ($object->permissions instanceof MyCustomPermissionService);
	// returns true;

	echo ($object->textProperty == 'My Text Value');
	// returns true;

## Factories

Some services require non-trivial construction which means they must be created by a factory class. To do this, create
a factory class which implements the [api:SilverStripe\Framework\Injector\Factory] interface. You can then specify
the `factory` key in the service definition, and the factory service will be used.

An example using the `MyFactory` service to create instances of the `MyService` service is shown below:

**mysite/_config/app.yml**

	:::yml
	Injector:
	  MyService:
	    factory: MyFactory

**mysite/code/MyFactory.php**

	:::php
	<?php

	class MyFactory implements SilverStripe\Framework\Injector\Factory {

		public function create($service, array $params = array()) {
			return new MyServiceImplementation();
		}
	}

	// Will use MyFactoryImplementation::create() to create the service instance.
	$instance = Injector::inst()->get('MyService');

## Dependency overrides

To override the `$dependency` declaration for a class, define the following configuration file.

**mysite/_config/app.yml**

	MyController:
	  dependencies:
		textProperty: a string value
		permissions: %$PermissionService

## Managed objects

Simple dependencies can be specified by the `$dependencies`, but more complex configurations are possible by specifying 
constructor arguments, or by specifying more complex properties such as lists.

These more complex configurations are defined in `Injector` configuration blocks and are read by the `Injector` at 
runtime.

Assuming a class structure such as

	:::php
	<?php

	class RestrictivePermissionService {
		private $database;

		public function setDatabase($d) {	
			$this->database = $d;
		}
	}
	
	class MySQLDatabase {
		private $username;
		private $password;
		
		public function __construct($username, $password) {
			$this->username = $username;
			$this->password = $password;
		}
	}

And the following configuration..

	:::yml
	name: MyController
	---
	MyController:
	  dependencies:
	    permissions: %$PermissionService
	Injector:
	  PermissionService:
	    class: RestrictivePermissionService
	    properties:
	      database: %$MySQLDatabase
	  MySQLDatabase
	    constructor:
	      0: 'dbusername'
	      1: 'dbpassword'

Calling..

	:::php
	// sets up ClassName as a singleton
	$controller = Injector::inst()->get('MyController');

Would setup the following

* Create an object of type `MyController`
* Look through the **dependencies** and call get('PermissionService')
* Load the configuration for PermissionService, and create an object of type `RestrictivePermissionService`
* Look at the properties to be injected and look for the config for `MySQLDatabase`
* Create a MySQLDatabase class, passing dbusername and dbpassword as the parameters to the constructor.


## Testing with Injector

In situations where injector states must be temporarily overridden, it is possible to create nested Injector instances 
which may be later discarded, reverting the application to the original state. This is done through `nest` and `unnest`.

This is useful when writing test cases, as certain services may be necessary to override for a single method call.

	:::php
	// Setup default service
	Injector::inst()->registerService(new LiveService(), 'ServiceName');

	// Test substitute service temporarily
	Injector::nest();

	Injector::inst()->registerService(new TestingService(), 'ServiceName');
	$service = Injector::inst()->get('ServiceName');
	// ... do something with $service

	// revert changes
	Injector::unnest();


## API Documentation

* [api:Injector]
* [api:Factory]
