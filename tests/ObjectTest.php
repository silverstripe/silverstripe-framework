<?php

class ObjectTest extends SapphireTest {
	function testHasMethod() {
		$st = new SiteTree();
		$cc = new ContentController($st);

		// Check that Versiond methods exist on SiteTree		
		$this->assertTrue($st->hasMethod('publish'), "Test SiteTree has publish");
		$this->assertTrue($st->hasMethod('migrateVersion'), "Test SiteTree has migrateVersion");
		
		// Check for different casing
		$this->assertTrue($st->hasMethod('PuBliSh'), "Test SiteTree has PuBliSh");
		$this->assertTrue($st->hasMethod('MiGratEVersIOn'), "Test SiteTree has MiGratEVersIOn");
		
		// Check that SiteTree methods exist on ContentController (test failover)
		$this->assertTrue($cc->hasMethod('canView'), "Test ContentController has canView");
		$this->assertTrue($cc->hasMethod('linkorcurrent'), "Test ContentController has linkorcurrent");
		$this->assertTrue($cc->hasMethod('MiGratEVersIOn'), "Test ContentController has MiGratEVersIOn");
		
		// Make use of the test object below.  1st instantiation is different from subsequent, so create a few
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
