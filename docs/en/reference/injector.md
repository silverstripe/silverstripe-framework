# Injector

## Introduction

The `[api:Injector]` class is the central manager of inter-class dependencies
in the SilverStripe Framework. In its simplest form it can be considered as
a replacement for Object::create and singleton() calls, but also offers 
developers the ability to declare the dependencies a class type has, or
to change the nature of the dependencies defined by other developers. 

Some of the goals of dependency injection are

* Simplified instantiation of objects
* Providing a uniform way of declaring and managing inter-object dependencies
* Making class dependencies configurable
* Simplifying the process of overriding or replacing core behaviour
* Improve testability of code
* Promoting abstraction of logic

A key concept of the injector is whether the object should be managed as

* A pseudo-singleton, in that only one item will be created for a particular
  identifier (but the same class could be used for multiple identifiers)
* A prototype, where the same configuration is used, but a new object is
  created each time
* unmanaged, in which case a new object is created and injected, but no 
  information about its state is managed.

These concepts will be discussed further below

## Some simple examples

The following sums up the simplest usage of the injector

Assuming no other configuration is specified

	:::php
	$object = Injector::inst()->create('ClassName');

Creates a new object of type ClassName

	:::php
	$object = Injector::inst()->create('ClassName');
	$object2 = Injector::inst()->create('ClassName');
	$object !== $object2;

Repeated calls to create() create a new class each time. To create a singleton
object instead, use **get()**

	:::php
	// sets up ClassName as a singleton
	$object = Injector::inst()->get('ClassName');
	$object2 = Injector::inst()->get('ClassName');
	$object === $object2;

The subsequent call returns the SAME object as the first call.

	:::php 
	class MyController extends Controller {
		// both of these properties will be automatically
		// set by the injector on object creation
		public $permissions;
		public $textProperty;

		static $dependencies = array(
			'textProperty'		=> 'a string value',
			'permissions'		=> '%$PermissionService',
		);
	}

	$object = Injector::inst()->get('MyController');
	
	// results in 
	$object->permissions instanceof PermissionService;
	$object->textProperty == 'a string value';

In this case, on creation of the MyController object, the injector will 
automatically instantiate the PermissionService object and set it as
the **permissions** property. 


## Configuring objects managed by the dependency injector

The above declarative style of dependency management would cover a large
portion of usecases, but more complex dependency structures can be defined
via configuration files. 

Configuration can be specified for two areas of dependency management

* Defining dependency overrides for individual classes
* Injector managed 'services' 

### Dependency overrides

To override the **static $dependency;** declaration for a class, you could 
define the following configuration file (module/_config/MyController.yml)

	name: MyController
	---
	MyController:
	  dependencies:
		textProperty: a string value
		permissions: %$PermissionService

At runtime, the **dependencies** configuration would be read and used in 
place of that declared on the object.

### Managed objects

Simple dependencies can be specified by the **dependencies**, but more complex
configurations are possible by specifying constructor arguments, or by 
specifying more complex properties such as lists.

These more complex configurations are defined in 'Injector' configuration 
blocks and are read by the injector at runtime

Assuming a class structure such as

	:::php
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

and the following configuration

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

calling 

	:::php
	// sets up ClassName as a singleton
	$controller = Injector::inst()->get('MyController');

would 

* Create an object of type MyController
* Look through the **dependencies** and call get('PermissionService')
* Load the configuration for PermissionService, and create an object of 
  type RestrictivePermissionService
* Look at the properties to be injected and look for the config for 
  MySQLDatabase
* Create a MySQLDatabase class, passing dbusername and dbpassword as the 
  parameters to the constructor


### What are Services?

Without diving too deep down the rabbit hole, the term 'Service' is commonly
used to describe a piece of code that acts as an interface between the 
controller layer and model layer of an MVC architecture. Rather than having
a controller action directly operate on data objects, a service layer provides
that logic abstraction, stopping controllers from implementing business logic, 
and keeping that logic packaged in a way that is easily reused from other
classes. 

By default, objects are managed like a singleton, in that there is only one
object instance used for a named service, and all references to that service
are returned the same object. 