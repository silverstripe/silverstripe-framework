<?php

require_once 'Zend/Cache.php';

class CacheProxy extends Zend_Cache_Core
{
    /**
     * @var Zend_Cache_Backend|Zend_Cache_Backend_ExtendedInterface
     */
    protected $container;

    /**
     * CacheProxy constructor.
     * @param Zend_Cache_Core $container
     */
    public function __construct(Zend_Cache_Core $container)
    {
        $this->container = $container;

        parent::__construct();
    }

    /**
     * @param array $directives
     */
    public function setDirectives($directives)
    {
        $this->container->setDirectives($directives);
    }

    public function setConfig(Zend_Config $config)
    {
        return $this->container->setConfig($config);
    }

    public function setBackend(Zend_Cache_Backend $backendObject)
    {
        return $this->container->setBackend($backendObject);
    }

    public function getBackend()
    {
        return $this->container->getBackend();
    }

    /**
     * @param string $name
     * @param mixed $value
     */
    public function setOption($name, $value)
    {
        $this->container->setOption($name, $value);
    }

    public function getOption($name)
    {
        return $this->container->getOption($name);
    }

    public function setLifetime($newLifetime)
    {
        return $this->container->setLifetime($newLifetime);
    }

    public function getIds()
    {
        return $this->container->getIds();
    }

    public function getTags()
    {
        return $this->container->getTags();
    }

    public function getIdsMatchingTags($tags = array())
    {
        return $this->container->getIdsMatchingTags($tags);
    }

    public function getIdsNotMatchingTags($tags = array())
    {
        return $this->container->getIdsNotMatchingTags($tags);
    }

    public function getIdsMatchingAnyTags($tags = array())
    {
        return $this->container->getIdsMatchingAnyTags($tags);
    }

    public function getFillingPercentage()
    {
        return $this->container->getFillingPercentage();
    }

    public function getMetadatas($id)
    {
        return $this->container->getMetadatas($this->createKey($id));
    }

    public function touch($id, $extraLifetime)
    {
        return $this->container->touch($this->createKey($id), $extraLifetime);
    }

    public function load($id, $doNotTestCacheValidity = false, $doNotUnserialize = false)
    {
        return $this->container->load($this->createKey($id), $doNotTestCacheValidity, $doNotUnserialize);
    }

    public function test($id)
    {
        return $this->container->test($this->createKey($id));
    }

    public function save($data, $id = null, $tags = array(), $specificLifetime = false, $priority = 8)
    {
        return $this->container->save(
            $data,
            $this->createKey($id),
            $tags,
            $specificLifetime,
            $priority
        );
    }

    public function remove($id)
    {
        return $this->container->remove($this->createKey($id));
    }

    public function clean($mode = Zend_Cache::CLEANING_MODE_ALL, $tags = array())
    {
        return $this->container->clean($mode, $tags);
    }

    /**
     * Creates a dynamic key based on versioned state
     * @param $key
     * @return string
     */
    protected function createKey($key)
    {
        $state = Versioned::get_reading_mode();
        if ($state) {
            return $key . '_' . md5($state);
        }
        return $key;
    }
}