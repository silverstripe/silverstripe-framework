<?php

namespace SilverStripe\i18n\Messages;

use SilverStripe\Assets\Filesystem;
use Symfony\Component\Yaml\Dumper;
use SilverStripe\i18n\Messages\Symfony\ModuleYamlLoader;
use LogicException;

/**
 * Write yml files compatible with ModuleYamlLoader
 *
 * @see ModuleYamlLoader
 */
class YamlWriter implements Writer
{
    /**
     * @var Dumper
     */
    protected $dumper = null;

    /**
     * @return Dumper
     */
    protected function getDumper()
    {
        if (!$this->dumper) {
            $this->dumper = new Dumper();
            $this->dumper->setIndentation(2);
        }
        return $this->dumper;
    }


    public function write($messages, $locale, $path)
    {
        // Skip empty entities
        if (empty($messages)) {
            return;
        }

        // Create folder for lang files
        $langFolder = $path . '/lang';
        if (!file_exists($langFolder)) {
            Filesystem::makeFolder($langFolder);
            touch($langFolder . '/_manifest_exclude');
        }

        // De-normalise messages and convert to yml
        $content = $this->getYaml($messages, $locale);

        // Open the English file and write the Master String Table
        $langFile = $langFolder . '/' . $locale . '.yml';
        if ($fh = fopen($langFile, "w")) {
            fwrite($fh, $content);
            fclose($fh);
        } else {
            throw new LogicException("Cannot write language file! Please check permissions of $langFile");
        }
    }

    /**
     * Explodes [class.key1 => value1, class.key2 => value2] into [class => [ key1 => value1, key2 => value2]]
     *
     * Inverse of YamlReader::normaliseMessages()
     *
     * @param array $messages
     * @return array
     */
    protected function denormaliseMessages($messages)
    {
        $entities = [];
        foreach ($messages as $entity => $value) {
            // Skip un-namespaced keys
            if (strstr($entity, '.') === false) {
                $entities[$entity] = $value;
                continue;
            }
            $parts = explode('.', $entity);
            $class = array_shift($parts);

            // Ensure the `.ss` suffix gets added to the top level class rather than the key
            if (count($parts) > 1 && reset($parts) === 'ss') {
                $class .= '.ss';
                array_shift($parts);
            }
            $key = implode('.', $parts);
            if (!isset($entities[$class])) {
                $entities[$class] = [];
            }
            $entities[$class][$key] = $value;
        }
        return $entities;
    }

    /**
     * Convert messages to yml ready to write
     *
     * @param array $messages
     * @param string $locale
     * @return string
     */
    public function getYaml($messages, $locale)
    {
        $entities = $this->denormaliseMessages($messages);
        $content = $this->getDumper()->dump([
            $locale => $entities
        ], 99);
        return $content;
    }
}
