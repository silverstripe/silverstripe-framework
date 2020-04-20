<?php

namespace SilverStripe\Dev\Tests;

use SilverStripe\Dev\Backtrace;
use SilverStripe\Dev\SapphireTest;

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
        Backtrace::config()->update(
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
        Backtrace::config()->update(
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
}
