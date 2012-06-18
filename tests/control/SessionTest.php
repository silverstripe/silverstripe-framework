<?php

/**
 * Tests to cover the {@link Session} class
 *
 * @package framework
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
		
		Session::clear_all();
		
		// should session get return null? The array key should probably be
		// unset from the data array
		$this->assertEquals(Session::get('Test'), '');
		$this->assertEquals(Session::get('Test-1'), '');
	}
	
	function testGetAllElements() {
		Session::clear_all(); // Remove all session that might've been set by the test harness
		
		Session::set('Test', 'Test');
		Session::set('Test-2', 'Test-2');
		
		$session = Session::get_all();
		
		$this->assertEquals($session, array('Test' => 'Test', 'Test-2' => 'Test-2'));
	}

	function testSettingExistingDoesntClear() {
		$s = new Session(array('something' => array('does' => 'exist')));

		$s->inst_set('something.does', 'exist');
		$this->assertEquals(array(), $s->inst_changedData());
	}

	/**
	 * Check that changedData isn't populated with junk when clearing non-existent entries.
	 */
	function testClearElementThatDoesntExist() {
		$s = new Session(array('something' => array('does' => 'exist')));

		$s->inst_clear('something.doesnt.exist');
		$this->assertEquals(array(), $s->inst_changedData());

		$s->inst_set('something-else', 'val');
		$s->inst_clear('something-new');
		$this->assertEquals(array('something-else' => 'val'), $s->inst_changedData());
	}

	/**
	 * Check that changedData is populated with clearing data.
	 */
	function testClearElementThatDoesExist() {
		$s = new Session(array('something' => array('does' => 'exist')));

		$s->inst_clear('something.does');
		$this->assertEquals(array('something' => array('does' => null)), $s->inst_changedData());
	}

	function testNonStandardPath(){
		Session::set_session_store_path(realpath(dirname($_SERVER['DOCUMENT_ROOT']) . '/../session'));
		Session::start();

		$this->assertEquals(Session::get_session_store_path(), '');
	}
}
