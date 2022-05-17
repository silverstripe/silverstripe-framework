<?php

namespace SilverStripe\i18n\Messages;

use SilverStripe\Dev\Debug;
use Symfony\Component\Translation\Exception\InvalidResourceException;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Parser;

class YamlReader implements Reader
{
    /**
     * @var Parser
     */
    protected $parser = null;

    /**
     * @return Parser
     */
    protected function getParser()
    {
        if (!$this->parser) {
            $this->parser = new Parser();
        }
        return $this->parser;
    }

    public function read($locale, $path)
    {
        try {
            if (!file_exists($path ?? '')) {
                return [];
            }
            // Load
            $yaml = $this->getParser()->parse(file_get_contents($path ?? ''));
            if (empty($yaml[$locale])) {
                return [];
            }
            // Normalise messages
            return $this->normaliseMessages($yaml[$locale]);
        } catch (ParseException $exception) {
            throw new InvalidResourceException(sprintf('Error parsing YAML, invalid file "%s". Message: %s', $path, $exception->getMessage()), 0, $exception);
        }
    }

    /**
     * Flatten [class => [ key1 => value1, key2 => value2]] into [class.key1 => value1, class.key2 => value2]
     *
     * Inverse of YamlWriter::denormaliseMessages()
     *
     * @param array $entities
     * @return mixed
     */
    protected function normaliseMessages($entities)
    {
        $messages = [];
        // Squash second and third levels together (class.key)
        foreach ($entities as $class => $keys) {
            // Check if namespace omits class
            if (!is_array($keys)) {
                $messages[$class] = $keys;
            } else {
                foreach ($keys as $key => $value) {
                    $fullKey = "{$class}.{$key}";
                    $messages[$fullKey] = $value;
                }
            }
        }
        ksort($messages);
        return $messages;
    }
}
