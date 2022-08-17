<?php

namespace SilverStripe\Dev;

use SilverStripe\Assets\File;

class BehatFixtureFactory extends FixtureFactory
{
    public function createObject(string $name, string $identifier, array $data = null): Page
    {
        if (!$data) {
            $data = [];
        }

        // Copy identifier to some visible property unless its already defined.
        // Exclude files, since they generate their own named based on the file path.
        if (!is_a($name, File::class, true)) {
            foreach (['Name', 'Title'] as $fieldName) {
                if (singleton($name)->hasField($fieldName) && !isset($data[$fieldName])) {
                    $data[$fieldName] = $identifier;
                    break;
                }
            }
        }

        return parent::createObject($name, $identifier, $data);
    }
}
