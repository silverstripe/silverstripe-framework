<?php

/**
 * Tests to cover the {@link Session} class
 *
 * @package framework
 * @subpackage tests
 */

class SessionTest extends SapphireTest {
	
	public function testGetSetBasics() {
		Session::set('Test', 'Test');
		
		$this->assertEquals(Session::get('Test'), 'Test');
	}
	
	public function testClearElement() {
		Session::set('Test', 'Test');
		Session::clear('Test');
		
		$this->assertEquals(Session::get('Test'), '');
	}
	
	public function testClearAllElements() {
		Session::set('Test', 'Test');
		Session::set('Test-1', 'Test-1');
		
		Session::clear_all();
		
		// should session get return null? The array key should probably be
		// unset from the data array
		$this->assertEquals(Session::get('Test'), '');
		$this->assertEquals(Session::get('Test-1'), '');
	}
	
	public function testGetAllElements() {
		Session::clear_all(); // Remove all session that might've been set by the test harness
		
		Session::set('Test', 'Test');
		Session::set('Test-2', 'Test-2');
		
		$session = Session::get_all();
		
		$this->assertEquals($session, array('Test' => 'Test', 'Test-2' => 'Test-2'));
	}

	public function testSettingExistingDoesntClear() {
		$s = new Session(array('something' => array('does' => 'exist')));

		$s->inst_set('something.does', 'exist');
		$this->assertEquals(array(), $s->inst_changedData());
	}

	/**
	 * Check that changedData isn't populated with junk when clearing non-existent entries.
	 */
	public function testClearElementThatDoesntExist() {
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
	public function testClearElementThatDoesExist() {
		$s = new Session(array('something' => array('does' => 'exist')));

		$s->inst_clear('something.does');
		$this->assertEquals(array('something' => array('does' => null)), $s->inst_changedData());
	}

	public function testNonStandardPath(){
		Session::set_session_store_path(realpath(dirname($_SERVER['DOCUMENT_ROOT']) . '/../session'));
		Session::start();

		$this->assertEquals(Session::get_session_store_path(), '');
	}

	public function testBasicBackend() {
		Session::set_backend_class('SessionTest_DummyBackend');
		Controller::curr()->setSession(new Session(array()));

		$this->assertEquals('SessionTest_DummyBackend', Session::get_backend_class());
		$this->assertEquals('Dummy', Session::get('Anything'));
		Session::set_backend_class('Session_Backend_Standard');
	}
}

class SessionTest_DummyBackend implements TestOnly, Session_Backend {
	public function __construct($data) {}
	public static function start($sid = null) {
		$_SESSION = array();
	}

	public static function destroy($removeCookie = true) {
		unset($_SESSION);
	}

	public function set($name, $val) {}
	public function addToArray($name, $val) {}
	public function get($name) {
		return 'Dummy';
	}
	public function getAll() {
		return array();
	}
	public function save() {}
	public function clear($name) { }
	public function clearAll() { }
}
