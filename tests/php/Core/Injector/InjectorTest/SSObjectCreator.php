<?php

namespace SilverStripe\Core\Tests\Injector\InjectorTest;

use SilverStripe\Core\ClassInfo;
use SilverStripe\Core\Injector\InjectionCreator;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Dev\TestOnly;

/**
 * An example object creator that uses the SilverStripe class(arguments) mechanism for
 * creating new objects
 *
 * @see https://github.com/silverstripe/sapphire
 */
class SSObjectCreator extends InjectionCreator implements TestOnly
{
    /**
     * @var Injector
     */
    private $injector;

    public function __construct($injector)
    {
        $this->injector = $injector;
    }

    public function create($class, array $params = array())
    {
        if (strpos($class, '(') === false) {
            return parent::create($class, $params);
        } else {
            list($class, $params) = ClassInfo::parse_class_spec($class);
            $params = $this->injector->convertServiceProperty($params);
            return parent::create($class, $params);
        }
    }
}
