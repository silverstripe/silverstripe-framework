<?php

namespace SilverStripe\i18n\Messages;

use SilverStripe\Assets\Filesystem;
use SilverStripe\Core\Path;
use SilverStripe\i18n\i18n;
use Symfony\Component\Yaml\Dumper;
use SilverStripe\i18n\Messages\Symfony\ModuleYamlLoader;
use LogicException;

/**
 * Write yml files compatible with ModuleYamlLoader
 *
 * Note: YamlWriter may not correctly denormalise plural strings if writing outside of the
 * default locale (en).
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
            $this->dumper = new Dumper(2);
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
        $langFolder = Path::join($path, 'lang');
        if (!file_exists($langFolder ?? '')) {
            Filesystem::makeFolder($langFolder);
            touch(Path::join($langFolder, '_manifest_exclude'));
        }

        // De-normalise messages and convert to yml
        $content = $this->getYaml($messages, $locale);

        // Open the English file and write the Master String Table
        $langFile = Path::join($langFolder, $locale . '.yml');
        if ($fh = fopen($langFile ?? '', "w")) {
            fwrite($fh, $content ?? '');
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
        // Sort prior to denormalisation
        ksort($messages);
        $entities = [];
        foreach ($messages as $entity => $value) {
            // Skip un-namespaced keys
            $value = $this->denormaliseValue($value);

            // Non-nested key
            if (strstr($entity ?? '', '.') === false) {
                $entities[$entity] = $value;
                continue;
            }

            // Get key nested within class
            list($class, $key) = $this->getClassKey($entity);
            if (!isset($entities[$class])) {
                $entities[$class] = [];
            }

            $entities[$class][$key] = $value;
        }
        return $entities;
    }

    /**
     * Convert entities array format into yml-ready string / array
     *
     * @param array|string $value Input value
     * @return array|string denormalised value
     */
    protected function denormaliseValue($value)
    {
        // Check plural form
        $plurals = $this->getPluralForm($value);
        if ($plurals) {
            return $plurals;
        }

        // Non-plural non-array is already denormalised
        if (!is_array($value)) {
            return $value;
        }

        // Denormalise from default key
        if (!empty($value['default'])) {
            return $this->denormaliseValue($value['default']);
        }

        // No value
        return null;
    }

    /**
     * Get array-plural form for any value
     *
     * @param array|string $value
     * @return array List of plural forms, or empty array if not plural
     */
    protected function getPluralForm($value)
    {
        // Strip non-plural keys away
        if (is_array($value)) {
            $forms = i18n::config()->uninherited('plurals');
            $forms = array_combine($forms ?? [], $forms ?? []);
            return array_intersect_key($value ?? [], $forms);
        }

        // Parse from string
        // Note: Risky outside of 'en' locale.
        return i18n::parse_plurals($value);
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

    /**
     * Determine class and key for a localisation entity
     *
     * @param string $entity
     * @return array Two-length array with class and key as elements
     */
    protected function getClassKey($entity)
    {
        $parts = explode('.', $entity ?? '');
        $class = array_shift($parts);

        // Ensure the `.ss` suffix gets added to the top level class rather than the key
        if (count($parts ?? []) > 1 && reset($parts) === 'ss') {
            $class .= '.ss';
            array_shift($parts);
        }
        $key = implode('.', $parts);
        return [$class, $key];
    }
}
