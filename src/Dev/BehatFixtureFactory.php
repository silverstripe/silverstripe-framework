<?php

namespace SilverStripe\Dev;

use SilverStripe\Assets\File;

class BehatFixtureFactory extends FixtureFactory
{
    public function createObject($name, $identifier, $data = null)
    {
        if (!$data) {
            $data = array();
        }

        // Copy identifier to some visible property unless its already defined.
        // Exclude files, since they generate their own named based on the file path.
        if (!is_a($name, File::class, true)) {
            foreach (array('Name', 'Title') as $fieldName) {
                if (singleton($name)->hasField($fieldName) && !isset($data[$fieldName])) {
                    $data[$fieldName] = $identifier;
                    break;
                }
            }
        }

        return parent::createObject($name, $identifier, $data);
    }
}
