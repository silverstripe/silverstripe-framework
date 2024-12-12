<?php

namespace SilverStripe\Forms\Tests\FormMessageTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\Forms\FormMessage;

/**
 * FormMessage is a trait, so we need a class that uses it to test it.
 */
class TestFormMessage implements TestOnly
{
    use FormMessage;
}
