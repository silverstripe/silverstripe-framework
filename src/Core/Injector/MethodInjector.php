<?php
namespace SilverStripe\Core\Injector;

use ReflectionMethod;

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
        return preg_match('/^\s*\*\s*@' . static::TAG_INJECTABLE . '\s*$/m', $this->method->getDocComment()) > 0;
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
                if (false !== strpos($class, $attribute['class'])) {
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
        $docblock = $this->method->getDocComment();

        preg_match_all('/^\s*\*\s*@' . static::TAG_ATTRIBUTE . '\s(.+)$/m', $docblock, $matches);

        if (!isset($matches[1])) {
            return [];
        }

        $attributes = [];
        foreach ($matches[1] as $definition) {
            if (false !== ($attribute = $this->parseAttributeDefinition($definition))) {
                $attributes[] = $attribute;
            }
        }

        return $attributes;
    }

    protected function parseAttributeDefinition($definition)
    {
        $parts = explode(' ', $definition);

        if (count($parts) !== 2) {
            return false;
        }

        return array_combine(['class', 'key'], $parts);
    }
}
