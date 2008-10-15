<?php
/**
 * @package sapphire
 * @subpackage tests
 * 
 * @todo tests for addStaticVars()
 * @todo tests for setting statics which are not defined on the object as built-in PHP statics
 * @todo tests for setting statics through decorators (#2387)
 */
class ObjectTest extends SapphireTest {
	
	function testHasmethodBehaviour() {
		/* SiteTree should have all of the methods that Versioned has, because Versioned is listed in SiteTree's
		 * extensions */
		$st = new SiteTree();
		$cc = new ContentController($st);

		$this->assertTrue($st->hasMethod('publish'), "Test SiteTree has publish");
		$this->assertTrue($st->hasMethod('migrateVersion'), "Test SiteTree has migrateVersion");
		
		/* This relationship should be case-insensitive, too */
		$this->assertTrue($st->hasMethod('PuBliSh'), "Test SiteTree has PuBliSh");
		$this->assertTrue($st->hasMethod('MiGratEVersIOn'), "Test SiteTree has MiGratEVersIOn");
		
		/* In a similar manner, all of SiteTree's methods should be available on ContentController, because $failover is set */
		$this->assertTrue($cc->hasMethod('canView'), "Test ContentController has canView");
		$this->assertTrue($cc->hasMethod('linkorcurrent'), "Test ContentController has linkorcurrent");
		
		/* This 'method copying' is transitive, so all of Versioned's methods should be available on ContentControler.
		 * Once again, this is case-insensitive */
		$this->assertTrue($cc->hasMethod('MiGratEVersIOn'), "Test ContentController has MiGratEVersIOn");
		
		/* The above examples make use of SiteTree, Versioned and ContentController.  Let's test defineMethods() more
		 * directly, with some sample objects */
		$objs = array();
		$objs[] = new ObjectTest_T2();
		$objs[] = new ObjectTest_T2();
		$objs[] = new ObjectTest_T2();
		
		// All these methods should exist and return true
		$trueMethods = array('testMethod','otherMethod','someMethod','t1cMethod','normalMethod');
		
		foreach($objs as $i => $obj) {
			foreach($trueMethods as $method) {
				$methodU = strtoupper($method);
				$methodL = strtoupper($method);
				$this->assertTrue($obj->hasMethod($method), "Test that obj#$i has method $method");
				$this->assertTrue($obj->hasMethod($methodU), "Test that obj#$i has method $methodU");
				$this->assertTrue($obj->hasMethod($methodL), "Test that obj#$i has method $methodL");

				$this->assertTrue($obj->$method(), "Test that obj#$i can call method $method");
				$this->assertTrue($obj->$methodU(), "Test that obj#$i can call method $methodU");
				$this->assertTrue($obj->$methodL(), "Test that obj#$i can call method $methodL");
			}
			
			$this->assertTrue($obj->hasMethod('Wrapping'), "Test that obj#$i has method Wrapping");
			$this->assertTrue($obj->hasMethod('WRAPPING'), "Test that obj#$i has method WRAPPING");
			$this->assertTrue($obj->hasMethod('wrapping'), "Test that obj#$i has method wrapping");
			
			$this->assertEquals("Wrapping", $obj->Wrapping(), "Test that obj#$i can call method Wrapping");
			$this->assertEquals("Wrapping", $obj->WRAPPING(), "Test that obj#$i can call method WRAPPIGN");
			$this->assertEquals("Wrapping", $obj->wrapping(), "Test that obj#$i can call method wrapping");
		}
		
	}
	
	function testSingletonCreation() {
		$myObject = singleton('ObjectTest_MyObject');
		$this->assertEquals($myObject->class, 'ObjectTest_MyObject', 'singletons are creating a correct class instance');
		$this->assertEquals(get_class($myObject), 'ObjectTest_MyObject', 'singletons are creating a correct class instance');
		
		$mySubObject = singleton('ObjectTest_MySubObject');
		$this->assertEquals($mySubObject->class, 'ObjectTest_MySubObject', 'singletons are creating a correct subclass instance');
		$this->assertEquals(get_class($mySubObject), 'ObjectTest_MySubObject', 'singletons are creating a correct subclass instance');
		
		$myFirstObject = singleton('ObjectTest_MyObject');
		$mySecondObject = singleton('ObjectTest_MyObject');
		$this->assertTrue($myFirstObject === $mySecondObject, 'singletons are using the same object on subsequent calls');
	}
	
	function testStaticGetterMethod() {
		static $_SINGLETONS;
		$_SINGLETONS= null;
		
		$obj = singleton('ObjectTest_MyObject');
		$this->assertEquals(
			ObjectTest_MyObject::$mystaticProperty,
			$obj->stat('mystaticProperty'),
			'Uninherited statics through stat() on a singleton behave the same as built-in PHP statics'
		);
		/*
		$this->assertNull(
			$obj->stat('mystaticSubProperty'),
			'Statics through stat() on a singleton dont inherit statics from child classes'
		);
		*/
	}
	
	function testStaticInheritanceGetters() {
		static $_SINGLETONS;
		$_SINGLETONS = null;
		
		$obj = singleton('ObjectTest_MyObject');
		$subObj = singleton('ObjectTest_MyObject');
		$this->assertEquals(
			$subObj->stat('mystaticProperty'),
			'MyObject',
			'Statics defined on a parent class are available through stat() on a subclass'
		);
		/*
		$this->assertEquals(
			$subObj->uninherited('mystaticProperty'),
			'MySubObject',
			'Statics re-defined on a subclass are available through uninherited()'
		);
		*/
	}
	
	function testStaticInheritanceSetters() {
		static $_SINGLETONS;
		$_SINGLETONS = null;
		
		$obj = singleton('ObjectTest_MyObject');
		$subObj = singleton('ObjectTest_MyObject');
		$subObj->set_uninherited('mystaticProperty', 'MySubObject_changed');
		$this->assertEquals(
			$obj->stat('mystaticProperty'),
			'MyObject',
			'Statics set on a subclass with set_uninherited() dont influence parent class'
		);
		/*
		$this->assertEquals(
			$subObj->stat('mystaticProperty'),
			'MySubObject_changed',
			'Statics set on a subclass with set_uninherited() are changed on subclass when using stat()'
		);
		*/
		$this->assertEquals(
			$subObj->uninherited('mystaticProperty'),
			'MySubObject_changed',
			'Statics set on a subclass with set_uninherited() are changed on subclass when using uninherited()'
		);
	}
	
	function testStaticSettingOnSingletons() {
		static $_SINGLETONS;
		$_SINGLETONS = null;
		
		$singleton1 = singleton('ObjectTest_MyObject');
		$singleton2 = singleton('ObjectTest_MyObject');
		$singleton1->set_stat('mystaticProperty', 'changed');
		$this->assertEquals(
			$singleton2->stat('mystaticProperty'),
			'changed',
			'Statics setting is populated throughout singletons without explicitly clearing cache'
		);
	}
	
	function testStaticSettingOnInstances() {
		static $_SINGLETONS;
		$_SINGLETONS = null;
		
		$instance1 = new ObjectTest_MyObject();
		$instance2 = new ObjectTest_MyObject();
		$instance1->set_stat('mystaticProperty', 'changed');
		$this->assertEquals(
			$instance2->stat('mystaticProperty'),
			'changed',
			'Statics setting through set_stat() is populated throughout instances without explicitly clearing cache'
		);
		/*
		$this->assertEquals(
			ObjectTest_MyObject::$mystaticProperty,
			'changed',
			'Statics setting through set_stat() reflects on PHP built-in statics on the class'
		);
		*/
	}
}

class ObjectTest_T1A extends Object {
	function testMethod() {
		return true;
	}
	function otherMethod() {
		return true;
	}
}

class ObjectTest_T1B extends Object {
	function someMethod() {
		return true;
	}
}

class ObjectTest_T1C extends Object {
	function t1cMethod() {
		return true;
	}
}

class ObjectTest_T2 extends Object {
	protected $failover;
	protected $failoverArr = array();
	
	function __construct() {
		$this->failover = new ObjectTest_T1A();
		$this->failoverArr[0] = new ObjectTest_T1B();
		$this->failoverArr[1] = new ObjectTest_T1C();
		
		parent::__construct();
	}

	function defineMethods() {
		$this->addWrapperMethod('Wrapping', 'wrappedMethod');
		
		$this->addMethodsFrom('failover');
		$this->addMethodsFrom('failoverArr',0);
		$this->addMethodsFrom('failoverArr',1);
		
		$this->createMethod('testCreateMethod', 'return "created";');
	}
	
	function wrappedMethod($val) {
		return $val;		
	}
	
	function normalMethod() {
		return true;
	}
	
}

class ObjectTest_MyObject extends Object {
	public $title = 'my object';
	static $mystaticProperty = "MyObject";
	static $mystaticArray = array('one');
}

class ObjectTest_MySubObject extends ObjectTest_MyObject {
	public $title = 'my subobject';
	static $mystaticProperty = "MySubObject";
	static $mystaticSubProperty = "MySubObject";
	static $mystaticArray = array('two');
}
