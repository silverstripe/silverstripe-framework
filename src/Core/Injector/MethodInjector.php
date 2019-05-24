<?php
namespace SilverStripe\Core\Injector;

use ReflectionMethod;
use SilverStripe\Core\Config\Config;

/**
 * Parses method reflections and attempts to auro-wire parameters for injection
 */
class MethodInjector
{
    const TAG_INJECTABLE = 'Injectable';
    const TAG_ATTRIBUTE = 'Inject';

    /**
     * @var ReflectionMethod
     */
    protected $method;

    /**
     * MethodInjector constructor.
     * @param ReflectionMethod $method
     */
    public function __construct(ReflectionMethod $method)
    {
        $this->method = $method;
    }

    /**
     * Indicates that the method is marked as injectable in the doc-comment for the method (with @Injectable)
     *
     * @return bool
     */
    public function isMarkedInjectable()
    {
        return (bool) Config::inst()->get($this->method->getDeclaringClass()->getName(), 'injectable');
    }

    public function provideMethodParams(array $providedParams = [])
    {
        $attributes = $this->getInjectorAttributes();
        $reflectedParams = $this->method->getParameters();
        $params = [];

        foreach ($reflectedParams as $param) {
            if (!$param->getClass()) {
                break;
            }

            $class = $param->getClass()->getName();

            // Look in params for this
            foreach ($providedParams as $key => $candidate) {
                if ($candidate instanceof $class) {
                    $params[] = $candidate;
                    unset($providedParams[$key]);
                    continue 2;
                }
            }

            // Do we have a specific key for this?
            $key = '';
            foreach ($attributes as $attribute) {
                // Check if the definition matches the _end_ of the class name
                $classLength = strlen($attribute['class']);

                if ($attribute['class'] === substr($class, -$classLength)) {
                    $key = '.' . $attribute['key'];
                }
            }

            $params[] = Injector::inst()->get($class . $key);
        }

        if (count($providedParams)) {
            $params = array_merge($params, $providedParams);
        }

        return $params;
    }

    protected function getInjectorAttributes()
    {
        return Config::inst()->get($this->method->getDeclaringClass()->getName(), 'named_injections') ?: [];
    }
}
