<?php

namespace SilverStripe\Core\Tests\ObjectTest;

class Extending extends BaseObject
{

    private static $extensions = array(
        Extending_Extension::class,
    );

    public function getResults(&$first, &$second, &$third)
    {
        // Before extending should be invoked second
        $this->beforeExtending(
            'updateResult',
            function (&$first, &$second, &$third) {
                if ($first === 1 && $second === 2 && $third === 3) {
                    $first = 11;
                    $second = 12;
                    $third = 13;
                    return 'before';
                }
                return 'before-error';
            }
        );

        // After extending should be invoked fourth
        $this->afterExtending(
            'updateResult',
            function (&$first, &$second, &$third) {
                if ($first === 21 && $second === 22 && $third = 23) {
                    $first = 31;
                    $second = 32;
                    $third = 33;
                    return 'after';
                }
                return 'after-error';
            }
        );

        // Function body invoked first
        $result = $this->extend('updateResult', $first, $second, $third);
        return array($result);
    }
}
