<?php

namespace SilverStripe\Dev\Tests\Tasks\i18nTextCollectorTaskTest;

use SilverStripe\i18n\Messages\Writer;

class TestWriter implements Writer
{
    private static bool $wasConstructed = false;

    public function __construct(string $argument = '')
    {
        TestWriter::$wasConstructed = true;
    }

    public static function wasConstructed(): bool
    {
        return TestWriter::$wasConstructed;
    }

    public static function reset(): void
    {
        TestWriter::$wasConstructed = false;
    }

    public function write($messages, $locale, $path)
    {
    }
}
