<?php

namespace SilverStripe\Model\Tests\ModelDataTest;

use SilverStripe\Core\Extension;
use SilverStripe\Dev\TestOnly;

class ModelDataTestExtension extends Extension implements TestOnly
{
    private function privateMethodFromExtension(): string
    {
        return 'Private function';
    }

    protected function protectedMethodFromExtension(): string
    {
        return 'Protected function';
    }

    public function publicMethodFromExtension(): string
    {
        return 'Public function';
    }

    public function updateStatusFlags(array &$flags): void
    {
        $flags['myKey1'] = 'some flag';
        $flags['myKey2'] = [
            'text' => 'another flag',
            'title' => 'title attr',
        ];
    }
}
