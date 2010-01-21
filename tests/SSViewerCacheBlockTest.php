<?php

// Not actually a data object, we just want a ViewableData object that's just for us
class SSViewerCacheBlockTest_Model extends DataObject implements TestOnly {
	
	function Test($arg = null) {
		return $this;
	}
	
	function Foo() {
		return 'Bar';
	}
	
}

class SSViewerCacheBlockTest extends SapphireTest {
	
	protected $extraDataObjects = array('SSViewerCacheBlockTest_Model');
	
	protected $data = null;
	
	protected function _reset($cacheOn = true) {
		$this->data = new SSViewerCacheBlockTest_Model();
		
		Cache::factory('cacheblock')->clean();
		Cache::set_cache_lifetime('cacheblock', $cacheOn ? 600 : -1);
	}
	
	protected function _runtemplate($template, $data = null) {
		if ($data === null) $data = $this->data;
		if (is_array($data)) $data = new ArrayData($data);
		
		$viewer = SSViewer::fromString($template);
		return $viewer->process($data);
	}
	
	function testParsing() {
		// Make sure an empty cacheblock parses
		$this->_reset();
		$this->assertEquals($this->_runtemplate('<% cacheblock %><% end_cacheblock %>'), '');
		
		// Make sure a simple cacheblock parses
		$this->_reset();
		$this->assertEquals($this->_runtemplate('<% cacheblock %>Yay<% end_cacheblock %>'), 'Yay');

		// Make sure a moderately complicated cacheblock parses
		$this->_reset();
		$this->assertEquals($this->_runtemplate('<% cacheblock \'block\', Foo, "jumping" %>Yay<% end_cacheblock %>'), 'Yay');
		
		// Make sure a complicated cacheblock parses
		$this->_reset();
		$this->assertEquals($this->_runtemplate('<% cacheblock \'block\', Foo, Test.Test(4).Test(jumping).Foo %>Yay<% end_cacheblock %>'), 'Yay');
	}

	/**
	 * Test that cacheblocks actually cache
	 */
	function testBlocksCache() {
		// First, run twice without caching, to prove $Increment actually increments
		$this->_reset(false);
				
		$this->assertEquals($this->_runtemplate('<% cacheblock %>$Foo<% end_cacheblock %>', array('Foo' => 1)), '1');
		$this->assertEquals($this->_runtemplate('<% cacheblock %>$Foo<% end_cacheblock %>', array('Foo' => 2)), '2');
		
		// Then twice with caching, should get same result each time
		$this->_reset(true);
				
		$this->assertEquals($this->_runtemplate('<% cacheblock %>$Foo<% end_cacheblock %>', array('Foo' => 1)), '1');
		$this->assertEquals($this->_runtemplate('<% cacheblock %>$Foo<% end_cacheblock %>', array('Foo' => 2)), '1');
	}
	
}