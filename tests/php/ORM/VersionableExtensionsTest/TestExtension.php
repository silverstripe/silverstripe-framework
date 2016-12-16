<?php

namespace SilverStripe\ORM\Tests\VersionableExtensionsTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataExtension;
use SilverStripe\ORM\Versioning\VersionableExtension;

class TestExtension extends DataExtension implements VersionableExtension, TestOnly
{
    public function isVersionedTable($table)
    {
        return true;
    }

    /**
     * Update fields and indexes for the versonable suffix table
     *
     * @param string $suffix  Table suffix being built
     * @param array  $fields  List of fields in this model
     * @param array  $indexes List of indexes in this model
     */
    public function updateVersionableFields($suffix, &$fields, &$indexes)
    {
        $indexes['ExtraField'] = true;
        $fields['ExtraField'] = 'Varchar()';
    }
}
