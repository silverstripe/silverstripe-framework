<?php

namespace SilverStripe\View\Tests\SSViewerTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\View\TemplateGlobalProvider;

class TestGlobalProvider implements TemplateGlobalProvider, TestOnly
{

    public static function get_template_global_variables()
    {
        return array(
            'SSViewerTest_GlobalHTMLFragment' => array('method' => 'get_html', 'casting' => 'HTMLFragment'),
            'SSViewerTest_GlobalHTMLEscaped' => array('method' => 'get_html'),

            'SSViewerTest_GlobalAutomatic',
            'SSViewerTest_GlobalReferencedByString' => 'get_reference',
            'SSViewerTest_GlobalReferencedInArray' => array('method' => 'get_reference'),

            'SSViewerTest_GlobalThatTakesArguments' => array('method' => 'get_argmix', 'casting' => 'HTMLFragment')

        );
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
}
