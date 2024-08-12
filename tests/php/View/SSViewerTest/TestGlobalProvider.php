<?php

namespace SilverStripe\View\Tests\SSViewerTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\View\TemplateGlobalProvider;

class TestGlobalProvider implements TemplateGlobalProvider, TestOnly
{

    public static function get_template_global_variables()
    {
        return [
            'SSViewerTest_GlobalHTMLFragment' => ['method' => 'get_html', 'casting' => 'HTMLFragment'],
            'SSViewerTest_GlobalHTMLEscaped' => ['method' => 'get_html'],

            'SSViewerTest_GlobalAutomatic',
            'SSViewerTest_GlobalReferencedByString' => 'get_reference',
            'SSViewerTest_GlobalReferencedInArray' => ['method' => 'get_reference'],

            'SSViewerTest_GlobalThatTakesArguments' => ['method' => 'get_argmix', 'casting' => 'HTMLFragment'],
            'SSViewerTest_GlobalReturnsNull' => 'getNull',
        ];
    }

    public static function get_html()
    {
        return '<div></div>';
    }

    public static function SSViewerTest_GlobalAutomatic()
    {
        return 'automatic';
    }

    public static function get_reference()
    {
        return 'reference';
    }

    public static function get_argmix()
    {
        $args = func_get_args();
        return 'z' . implode(':', $args) . 'z';
    }

    public static function getNull()
    {
        return null;
    }
}
