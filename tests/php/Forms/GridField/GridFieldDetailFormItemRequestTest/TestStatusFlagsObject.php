<?php

namespace SilverStripe\Forms\Tests\GridField\GridFieldDetailFormItemRequestTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\Model\ModelData;

class TestStatusFlagsObject extends ModelData implements TestOnly
{
    public function getTitle()
    {
        return 'My test object';
    }

    public function getAnotherField()
    {
        return 'Another test field';
    }

    public function getStatusFlags(bool $cached = true): array
    {
        $flags = parent::getStatusFlags($cached);
        $flags['f1'] = [
            'title' => 'a flag',
            'text' => 'flag1',
        ];
        $flags['f2'] = 'flag2';
        return $flags;
    }
}
