<?php

namespace SilverStripe\ORM\Tests;

use SilverStripe\ORM\FieldType\DBField;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\ORM\FieldType\DBLocale;

class DBLocaleTest extends SapphireTest
{
    public function testNice()
    {
        /** @var DBLocale $locale */
        $locale = DBField::create_field('Locale', 'de_DE');
        $this->assertEquals('German', $locale->Nice());
    }

    public function testNiceNative()
    {
        /** @var DBLocale $locale */
        $locale = DBField::create_field('Locale', 'de_DE');
        $this->assertEquals('Deutsch', $locale->Nice(true));
    }

    public function testNativeName()
    {
        /** @var DBLocale $locale */
        $locale = DBField::create_field('Locale', 'de_DE');
        $this->assertEquals('Deutsch', $locale->getNativeName());
    }

    public function testShortName()
    {
        /** @var DBLocale $locale */
        $locale = DBField::create_field('Locale', 'de_DE');
        $this->assertEquals('German', $locale->getShortName());
    }

    public function testLongName()
    {
        /** @var DBLocale $locale */
        $locale = DBField::create_field('Locale', 'de_DE');
        $this->assertEquals('German (Germany)', $locale->getLongName());
    }
}
