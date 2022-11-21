<?php

namespace SilverStripe\Dev\Tests;

use ReflectionMethod;
use SilverStripe\Core\BaseKernel;
use SilverStripe\Core\CoreKernel;
use SilverStripe\Core\Kernel;
use SilverStripe\Dev\Backtrace;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\ORM\DataObject;

class BacktraceTest extends SapphireTest
{

    public function testFullFuncNameWithArgsAndCustomCharLimit()
    {
        $func = [
            'class' => 'MyClass',
            'type' => '->',
            'file' => 'MyFile.php',
            'line' => 99,
            'function' => 'myFunction',
            'args' => [
                'number' => 1,
                'mylongstring' => 'more than 20 characters 1234567890',
            ]
        ];
        $this->assertEquals(
            'MyClass->myFunction(1, more than 20 charact...)',
            Backtrace::full_func_name($func, true, 20)
        );
    }

    public function testIgnoredFunctionArgs()
    {
        $bt = [
            [
                'type' => '->',
                'file' => 'MyFile.php',
                'line' => 99,
                'function' => 'myIgnoredGlobalFunction',
                'args' => ['password' => 'secred',]
            ],
            [
                'class' => 'MyClass',
                'type' => '->',
                'file' => 'MyFile.php',
                'line' => 99,
                'function' => 'myIgnoredClassFunction',
                'args' => ['password' => 'secred',]
            ],
            [
                'class' => 'MyClass',
                'type' => '->',
                'file' => 'MyFile.php',
                'line' => 99,
                'function' => 'myFunction',
                'args' => ['myarg' => 'myval']
            ]
        ];
        Backtrace::config()->merge(
            'ignore_function_args',
            [
                ['MyClass', 'myIgnoredClassFunction'],
                'myIgnoredGlobalFunction'
            ]
        );

        $filtered = Backtrace::filter_backtrace($bt);

        $this->assertEquals('<filtered>', $filtered[0]['args']['password'], 'Filters global functions');
        $this->assertEquals('<filtered>', $filtered[1]['args']['password'], 'Filters class functions');
        $this->assertEquals('myval', $filtered[2]['args']['myarg'], 'Doesnt filter other functions');
    }

    public function testFilteredWildCard()
    {
        $bt = [
            [
                'type' => '->',
                'file' => 'MyFile.php',
                'line' => 99,
                'function' => 'myIgnoredGlobalFunction',
                'args' => ['password' => 'secred',]
            ],
            [
                'class' => 'MyClass',
                'type' => '->',
                'file' => 'MyFile.php',
                'line' => 99,
                'function' => 'myIgnoredClassFunction',
                'args' => ['password' => 'secred',]
            ],
            [
                'class' => 'MyClass',
                'type' => '->',
                'file' => 'MyFile.php',
                'line' => 99,
                'function' => 'myFunction',
                'args' => ['myarg' => 'myval']
            ]
        ];
        Backtrace::config()->merge(
            'ignore_function_args',
            [
                ['*', 'myIgnoredClassFunction'],
            ]
        );

        $filtered = Backtrace::filter_backtrace($bt);

        $this->assertEquals('secred', $filtered[0]['args']['password']);
        $this->assertEquals('<filtered>', $filtered[1]['args']['password']);
        $this->assertEquals('myval', $filtered[2]['args']['myarg']);
    }

    public function matchesFilterableClassProvider(): array
    {
        return [
            [
                'anything',
                '*',
                true,
                'Wildcard counts as a match',
            ],
            [
                DataObject::class,
                BaseKernel::class,
                false,
                'No match',
            ],
            [
                DataObject::class,
                DataObject::class,
                true,
                'Exact match',
            ],
            [
                CoreKernel::class,
                BaseKernel::class,
                true,
                'Subclass counts as a match',
            ],
            [
                BaseKernel::class,
                CoreKernel::class,
                false,
                'Superclass does not count as a match',
            ],
            [
                CoreKernel::class,
                Kernel::class,
                true,
                'Implements interface counts as a match',
            ],
        ];
    }

    /**
     * @dataProvider matchesFilterableClassProvider
     */
    public function testMatchesFilterableClass(string $className, string $filterableClass, bool $expected, string $message): void
    {
        $reflectionMethod = new ReflectionMethod(Backtrace::class . '::matchesFilterableClass');
        $reflectionMethod->setAccessible(true);
        $this->assertSame($expected, $reflectionMethod->invoke(null, $className, $filterableClass), $message);
    }
}
