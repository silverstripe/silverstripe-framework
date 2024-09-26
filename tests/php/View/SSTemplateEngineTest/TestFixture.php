<?php

namespace SilverStripe\View\Tests\SSTemplateEngineTest;

use ReflectionClass;
use SilverStripe\Dev\TestOnly;
use SilverStripe\View\SSViewer_Scope;
use Stringable;

/**
 * A test fixture that will echo back the template item
 */
class TestFixture implements TestOnly, Stringable
{
    private ?string $name;

    public function __construct($name = null)
    {
        $this->name = $name;
    }

    public function __call(string $name, array $arguments = []): static|array|null
    {
        return $this->getValue($name, $arguments);
    }

    public function __get(string $name): static|array|null
    {
        return $this->getValue($name);
    }

    public function __isset(string $name): bool
    {
        if (preg_match('/NotSet/i', $name)) {
            return false;
        }
        $reflectionScope = new ReflectionClass(SSViewer_Scope::class);
        $globalProperties = $reflectionScope->getStaticPropertyValue('globalProperties');
        if (array_key_exists($name, $globalProperties)) {
            return false;
        }
        return true;
    }

    public function __toString(): string
    {
        if (preg_match('/NotSet/i', $this->name ?? '')) {
            return '';
        }
        if (preg_match('/Raw/i', $this->name ?? '')) {
            return $this->name ?? '';
        }
        return '[out:' . $this->name . ']';
    }

    private function getValue(string $name, array $arguments = []): static|array|null
    {
        $childName = $this->argedName($name, $arguments);

        // Special field name Loop### to create a list
        if (preg_match('/^Loop([0-9]+)$/', $name ?? '', $matches)) {
            $output = [];
            for ($i = 0; $i < $matches[1]; $i++) {
                $output[] = new TestFixture($childName);
            }
            return $output;
        }

        if (preg_match('/NotSet/i', $name)) {
            return null;
        }

        return new TestFixture($childName);
    }

    private function argedName(string $fieldName, array $arguments): string
    {
        $childName = $this->name ? "$this->name.$fieldName" : $fieldName;
        if ($arguments) {
            return $childName . '(' . implode(',', $arguments) . ')';
        } else {
            return $childName;
        }
    }
}
