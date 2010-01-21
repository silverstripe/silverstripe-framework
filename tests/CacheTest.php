<?php 

class CacheTest extends SapphireTest {

	function testCacheBasics() {
		$cache = Cache::factory('test');
		
		$cache->save('Good', 'cachekey');
		$this->assertEquals('Good', $cache->load('cachekey'));
	}
	
	function testCacheCanBeDisabled() {
		Cache::set_cache_lifetime('test', -1, 10);
		
		$cache = Cache::factory('test');
		
		$cache->save('Good', 'cachekey');
		$this->assertFalse($cache->load('cachekey'));
	}
	
	function testCacheLifetime() {
		Cache::set_cache_lifetime('test', 4, 20);
		
		$cache = Cache::factory('test');
		
		$cache->save('Good', 'cachekey');
		$this->assertEquals('Good', $cache->load('cachekey'));
		
		sleep(8);
		
		$this->assertFalse($cache->load('cachekey'));
	}
	
	function testCacheSeperation() {
		$cache1 = Cache::factory('test1');
		$cache2 = Cache::factory('test2');
		
		$cache1->save('Foo', 'cachekey');
		$cache2->save('Bar', 'cachekey');
		$this->assertEquals('Foo', $cache1->load('cachekey'));
		$this->assertEquals('Bar', $cache2->load('cachekey'));
		
		$cache1->remove('cachekey');
		$this->assertFalse($cache1->load('cachekey'));
		$this->assertEquals('Bar', $cache2->load('cachekey'));
	}
}
	