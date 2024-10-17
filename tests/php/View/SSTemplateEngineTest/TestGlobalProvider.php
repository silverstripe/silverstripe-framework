<?php

namespace SilverStripe\View\Tests\SSTemplateEngineTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\View\TemplateGlobalProvider;

class TestGlobalProvider implements TemplateGlobalProvider, TestOnly
{

    public static function get_template_global_variables()
    {
        return [
            'SSTemplateEngineTest_GlobalHTMLFragment' => ['method' => 'get_html', 'casting' => 'HTMLFragment'],
            'SSTemplateEngineTest_GlobalHTMLEscaped' => ['method' => 'get_html'],

            'SSTemplateEngineTest_GlobalAutomatic',
            'SSTemplateEngineTest_GlobalReferencedByString' => 'get_reference',
            'SSTemplateEngineTest_GlobalReferencedInArray' => ['method' => 'get_reference'],

            'SSTemplateEngineTest_GlobalThatTakesArguments' => ['method' => 'get_argmix', 'casting' => 'HTMLFragment'],
            'SSTemplateEngineTest_GlobalReturnsNull' => 'getNull',
        ];
    }

    public static function get_html()
    {
        return '<div></div>';
    }

    public static function SSTemplateEngineTest_GlobalAutomatic()
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
