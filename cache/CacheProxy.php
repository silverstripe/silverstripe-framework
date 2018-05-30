<?php

require_once 'Zend/Cache.php';

/**
 * A decorator for a Zend_Cache_Backend cache service that mutates cache keys
 * dynamically depending on versioned state
 */
class CacheProxy extends Zend_Cache_Core {
    /**
     * @var Zend_Cache_Backend|Zend_Cache_Backend_ExtendedInterface
     */
    protected $cache;

    /**
     * CacheProxy constructor.
     * @param Zend_Cache_Core $cache
     */
    public function __construct(Zend_Cache_Core $cache) {
        $this->cache = $cache;

        parent::__construct();
    }

    public function setDirectives($directives) {
        $this->cache->setDirectives($directives);
    }

    public function setConfig(Zend_Config $config) {
        return $this->cache->setConfig($config);
    }

    public function setBackend(Zend_Cache_Backend $backendObject) {
        return $this->cache->setBackend($backendObject);
    }

    public function getBackend() {
        return $this->cache->getBackend();
    }

    public function setOption($name, $value) {
        $this->cache->setOption($name, $value);
    }

    public function getOption($name) {
        return $this->cache->getOption($name);
    }

    public function setLifetime($newLifetime) {
        return $this->cache->setLifetime($newLifetime);
    }

    public function getIds() {
        return $this->cache->getIds();
    }

    public function getTags() {
        return $this->cache->getTags();
    }

    public function getIdsMatchingTags($tags = array()) {
        return $this->cache->getIdsMatchingTags($tags);
    }

    public function getIdsNotMatchingTags($tags = array()) {
        return $this->cache->getIdsNotMatchingTags($tags);
    }

    public function getIdsMatchingAnyTags($tags = array()) {
        return $this->cache->getIdsMatchingAnyTags($tags);
    }

    public function getFillingPercentage() {
        return $this->cache->getFillingPercentage();
    }

    public function getMetadatas($id) {
        return $this->cache->getMetadatas($this->getKeyID($id));
    }

    public function touch($id, $extraLifetime) {
        return $this->cache->touch($this->getKeyID($id), $extraLifetime);
    }

    public function load($id, $doNotTestCacheValidity = false, $doNotUnserialize = false) {
        return $this->cache->load($this->getKeyID($id), $doNotTestCacheValidity, $doNotUnserialize);
    }

    public function test($id) {
        return $this->cache->test($this->getKeyID($id));
    }

    public function save($data, $id = null, $tags = array(), $specificLifetime = false, $priority = 8) {
        return $this->cache->save(
            $data,
            $this->getKeyID($id),
            $tags,
            $specificLifetime,
            $priority
        );
    }

    public function remove($id) {
        return $this->cache->remove($this->getKeyID($id));
    }

    public function clean($mode = Zend_Cache::CLEANING_MODE_ALL, $tags = array()) {
        return $this->cache->clean($mode, $tags);
    }

    /**
     * Creates a dynamic key based on versioned state
     * @param string $key
     * @return string
     */
    protected function getKeyID($key) {
        $state = Versioned::get_reading_mode();
        if ($state) {
            return $key . '_' . md5($state);
        }
        return $key;
    }
}