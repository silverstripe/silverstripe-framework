<?php

namespace SilverStripe\View\Tests\AttributesHTMLTest;

use SilverStripe\View\AttributesHTML;
use SilverStripe\Dev\TestOnly;

/**
 * This call is used to test the AttributesHTML trait
 */
class DummyAttributesHTML implements TestOnly
{
    use AttributesHTML;

    private array $defaultAttributes = [];

    /**
     * Trait requires this method to prepopulate the attributes
     */
    protected function getDefaultAttributes(): array
    {
        return $this->defaultAttributes;
    }

    /**
     * This method is only there to allow to explicitly set the default attributes in the test.
     */
    public function setDefaultAttributes(array $attributes): void
    {
        $this->defaultAttributes = $attributes;
    }
}
