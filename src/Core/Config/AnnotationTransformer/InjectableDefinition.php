<?php declare(strict_types=1);

namespace SilverStripe\Core\Config\AnnotationTransformer;

use ReflectionClass;
use ReflectionMethod;
use SilverStripe\Config\Transformer\AnnotationTransformer\AnnotationDefinitionInterface;
use SilverStripe\Core\Injector\MethodInjector;

class InjectableDefinition implements AnnotationDefinitionInterface
{
    /**
     * Return a bitwise integer combining COLLECT_* constants indicating what doc blocks to collect annotations from
     *
     * @return int
     */
    public function defineCollectionScopes(): int
    {
        return AnnotationDefinitionInterface::COLLECT_CONSTRUCTOR;
    }

    /**
     * Indicates whether annotations should be collected from the given class
     *
     * @param string $className
     * @return bool
     */
    public function shouldCollect(string $className): bool
    {
        return true;
    }

    /**
     * Indicates whether annotations should be collected from the given method within the given class
     *
     * @param ReflectionClass $class
     * @param ReflectionMethod $method
     * @return bool
     */
    public function shouldCollectFromMethod(ReflectionClass $class, ReflectionMethod $method): bool
    {
        return false;
    }

    /**
     * Get an array of annotations to look for. For example 'Foo' would indicate that '@Foo' should be matched
     *
     * @return array
     */
    public function getAnnotationStrings(): array
    {
        return [MethodInjector::TAG_INJECTABLE, MethodInjector::TAG_ATTRIBUTE];
    }

    /**
     * Create config from a matched annotation.
     *
     * @param string $annotation The annotation string that was matched (defined in @see getAnnotationStrings)
     * @param array $arguments An array of strings that were passed as arguments (eg. @Foo(argument1,argument2)
     * @param int $context A COLLECT_* constant that indicates what context this annotation was found in
     * @param string|null $contextDetail A method name, provided the context of the annotation was a method
     * @return array
     */
    public function createConfigForAnnotation(
        string $annotation,
        array $arguments,
        int $context,
        ?string $contextDetail = null
    ): array {
        // Check if this is just being tagged as injectable...
        if ($annotation === MethodInjector::TAG_INJECTABLE) {
            return ['injectable' => true];
        }

        // Otherwise this is naming services for injection. This should be provided as [$className, $serviceName]
        if (count($arguments) !== 2) {
            throw new InvalidAnnotationException(sprintf(
                '@%s annotations must provide two arguments, the classname and the service name. For example '
                . '@%s(MyClass, test) references MyClass.test from Injector.',
                MethodInjector::TAG_ATTRIBUTE,
                MethodInjector::TAG_ATTRIBUTE
            ));
        }

        return ['named_injections' => [['class' => $arguments[0], 'key' => $arguments[1]]]];
    }
}
