<?php

namespace SilverStripe\Forms\Tests\HTMLEditor;

use SilverStripe\Core\Config\Config;
use SilverStripe\Dev\FunctionalTest;
use SilverStripe\Forms\HTMLEditor\HTMLEditorConfig;
use SilverStripe\Forms\HTMLEditor\HTMLEditorSanitiser;
use SilverStripe\View\Parsers\HTMLValue;

class HTMLEditorSanitiserTest extends FunctionalTest
{

    public function provideSanitise(): array
    {
        return [
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
            [
                'a[href|target|rel]',
                '<a href="javascript:alert(0);">Test</a>',
                '<a>Test</a>',
                'Javascript in the href attribute of a link is completely removed'
            ],
            [
                'a[href|target|rel]',
                '<a href="' . implode("\n", str_split(' javascript:')) . '">Test</a>',
                '<a>Test</a>',
                'Javascript in the href attribute of a link is completely removed even for multiline markup'
            ],
            [
                'map[name],area[href|shape|coords]',
                '<map name="test"><area shape="rect" coords="34,44,270,350" href="javascript:alert(0);"></map>',
                '<map name="test"><area shape="rect" coords="34,44,270,350"></map>',
                'Javascript in the href attribute of a map\'s clickable area is completely removed'
            ],
            [
                'iframe[src]',
                '<iframe src="javascript:alert(0);"></iframe>',
                '<iframe></iframe>',
                'Javascript in the src attribute of an iframe is completely removed'
            ],
            [
                'iframe[src]',
                '<iframe src="jAvAsCrIpT:alert(0);"></iframe>',
                '<iframe></iframe>',
                'Mixed case javascript in the src attribute of an iframe is completely removed'
            ],
            [
                'iframe[src]',
                "<iframe src=\"java\tscript:alert(0);\"></iframe>",
                '<iframe></iframe>',
                'Javascript with tab elements the src attribute of an iframe is completely removed'
            ],
            [
                'object[data]',
                '<object data="OK"></object>',
                '<object data="OK"></object>',
                'Object with OK content in the data attribute is retained'
            ],
            [
                'object[data]',
                '<object data=javascript:alert()>',
                '<object></object>',
                'Object with dangerous javascript content in data attribute is completely removed'
            ],
            [
                'object[data]',
                '<object data="javascript:alert()">',
                '<object></object>',
                'Object with dangerous javascript content in data attribute with quotes is completely removed'
            ],
            [
                'object[data]',
                '<object data="data:text/html;base64,PHNjcmlwdD5hbGVydChkb2N1bWVudC5sb2NhdGlvbik8L3NjcmlwdD4=">',
                '<object></object>',
                'Object with dangerous html content in data attribute is completely removed'
            ],
            [
                'object[data]',
                '<object data="' . implode("\n", str_split(' DATA:TEXT/HTML;')) . 'base64,PHNjcmlwdD5hbGVydChkb2N1bWVudC5sb2NhdGlvbik8L3NjcmlwdD4=">',
                '<object></object>',
                'Object with split upper-case dangerous html content in data attribute is completely removed'
            ],
            [
                'object[data]',
                '<object data="data:text/xml;base64,PHNjcmlwdD5hbGVydChkb2N1bWVudC5sb2NhdGlvbik8L3NjcmlwdD4=">',
                '<object data="data:text/xml;base64,PHNjcmlwdD5hbGVydChkb2N1bWVudC5sb2NhdGlvbik8L3NjcmlwdD4="></object>',
                'Object with safe xml content in data attribute is retained'
            ],
            [
                'img[src]',
                '<img src="https://owasp.org/myimage.jpg" style="url:xss" onerror="alert(1)">',
                '<img src="https://owasp.org/myimage.jpg">',
                'XSS vulnerable attributes starting with on or style are removed via configuration'
            ],
        ];
    }

    /**
     * @dataProvider provideSanitise
     */
    public function testSanitisation(string $validElements, string $input, string $output, string $desc): void
    {
        foreach (['valid_elements', 'extended_valid_elements'] as $configType) {
            $config = HTMLEditorConfig::get('htmleditorsanitisertest_' . $configType);
            $config->setOptions([$configType => $validElements]);
            // Remove default valid elements if we're testing extended valid elements
            if ($configType !== 'valid_elements') {
                $config->setOptions(['valid_elements' => '']);
            }
            $sanitiser = new HtmlEditorSanitiser($config);

            $value = 'noopener noreferrer';
            if (strpos($desc ?? '', 'link_rel_value is an empty string') !== false) {
                $value = '';
            } elseif (strpos($desc ?? '', 'link_rel_value is null') !== false) {
                $value = null;
            }

            HTMLEditorSanitiser::config()->set('link_rel_value', $value);

            $htmlValue = HTMLValue::create($input);
            $sanitiser->sanitise($htmlValue);

            $this->assertEquals($output, $htmlValue->getContent(), "{$desc} - using config type: {$configType}");
        }
    }

    /**
     * Ensure that when there are no valid elements at all for a configuration set,
     * nothing is allowed.
     */
    public function testSanitiseNoValidElements(): void
    {
        $config = HTMLEditorConfig::get('htmleditorsanitisertest');
        $config->setOptions(['valid_elements' => '']);
        $config->setOptions(['extended_valid_elements' => '']);
        $sanitiser = new HtmlEditorSanitiser($config);

        $htmlValue = HTMLValue::create('<p>standard text</p><table><tbody><tr><th><a href="some-link">text</a></th></tr><tr><td>Header</td></tr></tbody></table>');
        $sanitiser->sanitise($htmlValue);

        $this->assertEquals('standard texttextHeader', $htmlValue->getContent());
    }
}
