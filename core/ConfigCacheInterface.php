<?php

interface ConfigCacheInterface {

	/**
	 * @param string $key
	 * @param mixed $val
	 * @param array $tags
	 */
	public function set($key, $val, $tags = array());

	/**
	 * @param string $key
	 */
	public function get($key);

	/**
	 * @param string|null $tag
	 */
	public function clean($tag = null); 

	/**
	 * Cache hit/miss stats
	 *
	 * @return int|float
	 */
	public function stats();

}

