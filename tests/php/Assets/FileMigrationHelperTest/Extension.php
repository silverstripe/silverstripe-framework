<?php

namespace SilverStripe\Assets\Tests\FileMigrationHelperTest;

use SilverStripe\Assets\File;
use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataExtension;

/**
 * @property File $owner
 */
class Extension extends DataExtension implements TestOnly
{
    /**
     * Ensure that File dataobject has the legacy "Filename" field
     */
    private static $db = array(
        "Filename" => "Text",
    );

    public function onBeforeWrite()
    {
        // Ensure underlying filename field is written to the database
        $this->owner->setField('Filename', 'assets/' . $this->owner->generateFilename());
    }
}
