<?php

class CacheTest extends SapphireTest {

	public function testCacheBasics() {
		$cache = SS_Cache::factory('test');

		$cache->save('Good', 'cachekey');
		$this->assertEquals('Good', $cache->load('cachekey'));
	}

	public function testCacheCanBeDisabled() {
		SS_Cache::set_cache_lifetime('test', -1, 10);

		$cache = SS_Cache::factory('test');

		$cache->save('Good', 'cachekey');
		$this->assertFalse($cache->load('cachekey'));
	}

	public function testCacheLifetime() {
		SS_Cache::set_cache_lifetime('test', 0.5, 20);

		$cache = SS_Cache::factory('test');
		$this->assertEquals(0.5, $cache->getOption('lifetime'));

		$cache->save('Good', 'cachekey');
		$this->assertEquals('Good', $cache->load('cachekey'));

		// As per documentation, sleep may not sleep for the amount of time you tell it to sleep for
		// This loop can make sure it *does* sleep for that long
		$endtime = time() + 2;
		while (time() < $endtime) {
			// Sleep for another 2 seconds!
			// This may end up sleeping for 4 seconds, but it's awwwwwwwright.
			sleep(2);
		}

		$this->assertFalse($cache->load('cachekey'));
	}

	public function testCacheSeperation() {
		$cache1 = SS_Cache::factory('test1');
		$cache2 = SS_Cache::factory('test2');

		$cache1->save('Foo', 'cachekey');
		$cache2->save('Bar', 'cachekey');
		$this->assertEquals('Foo', $cache1->load('cachekey'));
		$this->assertEquals('Bar', $cache2->load('cachekey'));

		$cache1->remove('cachekey');
		$this->assertFalse($cache1->load('cachekey'));
		$this->assertEquals('Bar', $cache2->load('cachekey'));
	}

	public function testCacheDefault() {
		SS_Cache::set_cache_lifetime('default', 1200);
		$default = SS_Cache::get_cache_lifetime('default');

		$this->assertEquals(1200, $default['lifetime']);

		$cache = SS_Cache::factory('somethingnew');

		$this->assertEquals(1200, $cache->getOption('lifetime'));
	}

}

