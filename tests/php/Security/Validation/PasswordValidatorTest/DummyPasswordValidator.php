<?php

namespace SilverStripe\Security\Tests\Validation\RulesPasswordValidatorTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\Security\Validation\PasswordValidator;

class DummyPasswordValidator extends PasswordValidator implements TestOnly
{
    // no-op, just need a concrete class instead of an abstract one.
}
