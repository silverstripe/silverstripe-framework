<?php

namespace SilverStripe\Forms\Tests\ValidatorTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\Forms\Form;
use SilverStripe\Forms\CompositeValidator;

/**
 * Class TestCompositeValidator
 *
 * @package SilverStripe\Forms\Tests\ValidatorTest
 */
class TestCompositeValidator extends CompositeValidator implements TestOnly
{
    /**
     * Allow us to access the form for test purposes.
     *
     * @return Form|null
     */
    public function getForm(): ?Form
    {
        return $this->form;
    }
}
