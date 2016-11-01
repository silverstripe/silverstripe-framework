<?php

namespace SilverStripe\Core\Config;

class Config_MemCache
{
	protected $cache;

	protected $i = 0;
	protected $c = 0;
	protected $tags = array();

	public function __construct()
	{
		$this->cache = array();
	}

	public function set($key, $val, $tags = array())
	{
		foreach ($tags as $t) {
			if (!isset($this->tags[$t])) {
				$this->tags[$t] = array();
			}
			$this->tags[$t][$key] = true;
		}

		$this->cache[$key] = array($val, $tags);
	}

	private $hit = 0;
	private $miss = 0;

	public function stats()
	{
		return $this->miss ? ($this->hit / $this->miss) : 0;
	}

	public function get($key)
	{
		list($hit, $result) = $this->checkAndGet($key);
		return $hit ? $result : false;
	}

	/**
	 * Checks for a cache hit and returns the value as a multi-value return
	 *
	 * @param string $key
	 * @return array First element boolean, isHit. Second element the actual result.
	 */
	public function checkAndGet($key)
	{
		if (array_key_exists($key, $this->cache)) {
			++$this->hit;
			return array(true, $this->cache[$key][0]);
		} else {
			++$this->miss;
			return array(false, null);
		}
	}

	public function clean($tag = null)
	{
		if ($tag) {
			if (isset($this->tags[$tag])) {
				foreach ($this->tags[$tag] as $k => $dud) {
					// Remove the key from everywhere else it is tagged
					$ts = $this->cache[$k][1];
					foreach ($ts as $t) {
						unset($this->tags[$t][$k]);
					}
					unset($this->cache[$k]);
				}
				unset($this->tags[$tag]);
			}
		} else {
			$this->cache = array();
			$this->tags = array();
		}
	}
}
