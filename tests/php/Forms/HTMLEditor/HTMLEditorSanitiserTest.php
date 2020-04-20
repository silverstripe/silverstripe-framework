<?php

namespace SilverStripe\Forms\Tests\HTMLEditor;

use SilverStripe\Core\Config\Config;
use SilverStripe\Dev\FunctionalTest;
use SilverStripe\Forms\HTMLEditor\HTMLEditorConfig;
use SilverStripe\Forms\HTMLEditor\HTMLEditorSanitiser;
use SilverStripe\View\Parsers\HTMLValue;

class HTMLEditorSanitiserTest extends FunctionalTest
{

    public function testSanitisation()
    {
        $tests = [
            [
                'p,strong',
                '<p>Leave Alone</p><div>Strip parent<strong>But keep children</strong> in order</div>',
                '<p>Leave Alone</p>Strip parent<strong>But keep children</strong> in order',
                'Non-whitelisted elements are stripped, but children are kept'
            ],
            [
                'p,strong',
                '<div>A <strong>B <div>Nested elements are still filtered</div> C</strong> D</div>',
                'A <strong>B Nested elements are still filtered C</strong> D',
                'Non-whitelisted elements are stripped even when children of non-whitelisted elements'
            ],
            [
                'p',
                '<p>Keep</p><script>Strip <strong>including children</strong></script>',
                '<p>Keep</p>',
                'Non-whitelisted script elements are totally stripped, including any children'
            ],
            [
                'p[id]',
                '<p id="keep" bad="strip">Test</p>',
                '<p id="keep">Test</p>',
                'Non-whitelisted attributes are stripped'
            ],
            [
                'p[default1=default1|default2=default2|force1:force1|force2:force2]',
                '<p default1="specific1" force1="specific1">Test</p>',
                '<p default1="specific1" force1="force1" default2="default2" force2="force2">Test</p>',
                'Default attributes are set when not present in input, forced attributes are always set'
            ],
            [
                'a[href|target|rel]',
                '<a href="/test" target="_blank">Test</a>',
                '<a href="/test" target="_blank" rel="noopener noreferrer">Test</a>',
                'noopener rel attribute is added when target attribute is set'
            ],
            [
                'a[href|target|rel]',
                '<a href="/test" target="_top">Test</a>',
                '<a href="/test" target="_top" rel="noopener noreferrer">Test</a>',
                'noopener rel attribute is added when target is _top instead of _blank'
            ],
            [
                'a[href|target|rel]',
                '<a href="/test" rel="noopener noreferrer">Test</a>',
                '<a href="/test">Test</a>',
                'noopener rel attribute is removed when target is not set'
            ],
            [
                'a[href|target|rel]',
                '<a href="/test" rel="noopener noreferrer" target="_blank">Test</a>',
                '<a href="/test" target="_blank">Test</a>',
                'noopener rel attribute is removed when link_rel_value is an empty string'
            ],
            [
                'a[href|target|rel]',
                '<a href="/test" target="_blank">Test</a>',
                '<a href="/test" target="_blank">Test</a>',
                'noopener rel attribute is unchanged when link_rel_value is null'
            ],
        ];

        $config = HTMLEditorConfig::get('htmleditorsanitisertest');

        foreach ($tests as $test) {
            list($validElements, $input, $output, $desc) = $test;

            $config->setOptions(['valid_elements' => $validElements]);
            $sanitiser = new HtmlEditorSanitiser($config);

            $value = 'noopener noreferrer';
            if (strpos($desc, 'link_rel_value is an empty string') !== false) {
                $value = '';
            } elseif (strpos($desc, 'link_rel_value is null') !== false) {
                $value = null;
            }
            Config::inst()->set(HTMLEditorSanitiser::class, 'link_rel_value', $value);

            $htmlValue = HTMLValue::create($input);
            $sanitiser->sanitise($htmlValue);

            $this->assertEquals($output, $htmlValue->getContent(), $desc);
        }
    }
}
