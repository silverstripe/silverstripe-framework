<?php

/**
 * Tests to cover the {@link Session} class
 *
 * @package sapphire
 * @subpackage tests
 */

class SessionTest extends SapphireTest {
	
	function testGetSetBasics() {
		Session::set('Test', 'Test');
		
		$this->assertEquals(Session::get('Test'), 'Test');
	}
	
	function testClearElement() {
		Session::set('Test', 'Test');
		Session::clear('Test');
		
		$this->assertEquals(Session::get('Test'), '');
	}
	
	function testClearAllElements() {
		Session::set('Test', 'Test');
		Session::set('Test-1', 'Test-1');
		
		Session::clearAll();
		
		// should session get return null? The array key should probably be
		// unset from the data array
		$this->assertEquals(Session::get('Test'), '');
		$this->assertEquals(Session::get('Test-1'), '');
	}
	
	function testGetAllElements() {
		Session::set('Test', 'Test');
		Session::set('Test-2', 'Test-2');
		
		$session = Session::getAll();
		
		$this->assertEquals($session, array('Test' => 'Test', 'Test-2' => 'Test-2'));
	}
}