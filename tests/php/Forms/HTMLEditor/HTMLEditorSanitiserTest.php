<?php

namespace SilverStripe\Forms\Tests\HTMLEditor;

use SilverStripe\Dev\FunctionalTest;
use SilverStripe\Forms\HTMLEditor\HTMLEditorConfig;
use SilverStripe\Forms\HTMLEditor\HTMLEditorSanitiser;
use SilverStripe\View\Parsers\HTMLValue;
use PHPUnit\Framework\Attributes\DataProvider;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Forms\HTMLEditor\HTMLEditorElementRule;
use SilverStripe\Forms\HTMLEditor\TextAreaConfig;

class HTMLEditorSanitiserTest extends FunctionalTest
{
    // This is the backspace character. It needs to be escaped in double-quotes.
    private const CHAR_BACKSPACE = "\x08";

    protected function setUp(): void
    {
        parent::setUp();
        // Make sure we're using the TextAreaConfig even if a module provides an alternative.
        Injector::inst()->load([
            HTMLEditorConfig::class => [
                'class' => TextAreaConfig::class,
            ]
        ]);
    }

    public static function provideSanitise(): array
    {
        return [
            'no allowed elements' => [
                'validElements' => [],
                'input' => '<p>No elements</p><div> are allowed <strong>isnt that</strong> just sad</div>',
                'expected' => 'No elements are allowed isnt that just sad',
            ],
            'remove not-allowed but keep allowed children' => [
                'validElements' => ['p' => true, 'strong' => true],
                'input' => '<p>Leave Alone</p><div>Strip parent<strong>But keep children</strong> in order</div>',
                'expected' => '<p>Leave Alone</p>Strip parent<strong>But keep children</strong> in order',
            ],
            'remove not-allowed even if child of allowed' => [
                'validElements' => ['p' => true, 'strong' => true],
                'input' => '<div>A <strong>B <div>Nested elements are still filtered</div> C</strong> D</div><strong></strong>',
                'expected' => 'A <strong>B Nested elements are still filtered C</strong> D<strong></strong>',
            ],
            'remove not allowed even if child of NOT allowed' => [
                'validElements' => ['p' => true, 'strong' => ['removeIfEmpty' => true]],
                'input' => '<div>A <strong>B <div>Nested elements are still filtered</div> C</strong> D</div><strong></strong>',
                'expected' => 'A <strong>B Nested elements are still filtered C</strong> D',
            ],
            'another check for removing not allowed elements' => [
                'validElements' => ['p' => true],
                'input' => '<p>Keep</p><script>Strip <strong>including children</strong></script>',
                'expected' => '<p>Keep</p>',
            ],
            'pad empty element' => [
                'validElements' => ['p' => true, 'strong' => ['padEmpty' => true]],
                'input' => '<div>A <strong>B <div>Nested elements are still filtered</div> C</strong> D</div><strong></strong>',
                'expected' => 'A <strong>B Nested elements are still filtered C</strong> D<strong>&nbsp;</strong>',
            ],
            'keep attrs that are allowed' => [
                'validElements' => ['p' => ['attributes' => ['id' => true]]],
                'input' => '<p id="keep" bad="strip">Test</p><p>no id is fine too</p>',
                'expected' => '<p id="keep">Test</p><p>no id is fine too</p>',
            ],
            'keep attrs that are globally allowed' => [
                'validElements' => [HTMLEditorElementRule::GLOBAL_NAME => ['attributes' => ['id' => true]], 'p' => true],
                'input' => '<p id="keep" bad="strip">Test</p><p>no id is fine too</p>',
                'expected' => '<p id="keep">Test</p><p>no id is fine too</p>',
            ],
            'remove element if missing a required attribute' => [
                // Note that only one required attribute has to be present for the element to be allowed
                'validElements' => ['p' => ['attributes' => ['id' => ['isRequired' => true], 'style' => ['isRequired' => true]]]],
                'input' => '<p style="wow">with style is okay</p><p id="keep" bad="strip">Test</p><p>no p if no id</p>',
                'expected' => '<p style="wow">with style is okay</p><p id="keep">Test</p>no p if no id',
            ],
            'remove element with no attributes' => [
                'validElements' => ['p' => ['removeIfNoAttributes' => true, 'attributes' => ['id' => true]]],
                'input' => '<p id="keep" bad="strip">Test</p><p>no attributes, no p tag</p>',
                'expected' => '<p id="keep">Test</p>no attributes, no p tag',
            ],
            'set default and forced values' => [
                'validElements' => ['p' => ['attributes' => [
                    'default1' => ['value' => 'default1', 'valueType' => 'default'],
                    'default2' => ['value' => 'default2', 'valueType' => 'default'],
                    'force1' => ['value' => 'force1', 'valueType' => 'forced'],
                    'force2' => ['value' => 'force2', 'valueType' => 'forced'],
                ]]],
                'input' => '<p default1="specific1" force1="specific1">Test</p>',
                'expected' => '<p default1="specific1" force1="force1" default2="default2" force2="force2">Test</p>',
            ],
            'set empty default and forced values' => [
                'validElements' => ['p' => ['attributes' => [
                    'default1' => ['value' => '', 'valueType' => 'default'],
                    'force1' => ['value' => '', 'valueType' => 'forced'],
                ]]],
                'input' => '<p force1="specific1">Test</p>',
                'expected' => '<p force1="" default1="">Test</p>',
            ],
            'set null default and forced values' => [
                'validElements' => ['p' => ['attributes' => [
                    'default1' => ['value' => null, 'valueType' => 'default'],
                    'force1' => ['value' => null, 'valueType' => 'forced'],
                ]]],
                'input' => '<p force1="specific1">Test</p>',
                'expected' => '<p force1="specific1">Test</p>',
            ],
            'validate attribute values' => [
                'validElements' => ['p' => ['attributes' => [
                    'id' => ['value' => ['v1', 'v2'], 'valueType' => 'valid'],
                    'class' => ['value' => 'oooh', 'valueType' => 'valid'],
                ]]],
                'input' => '<p id="v1" class="oooh">Test</p><p id="v2" class="what">Test</p><p id="v3">Test</p>',
                'expected' => '<p id="v1" class="oooh">Test</p><p id="v2">Test</p><p>Test</p>',
            ],
            'substitute element tags' => [
                'validElements' => [
                    'strong' => ['attributes' => ['id' => true]],
                    'b' => ['convertTo' => 'strong'],
                ],
                'input' => '<b id="1">Test</b><strong id="2">Boldened</strong>',
                'expected' => '<strong id="1">Test</strong><strong id="2">Boldened</strong>',
            ],
            'chained substitute element tags' => [
                'validElements' => [
                    'strong' => ['attributes' => ['id' => true]],
                    'span' => ['convertTo' => 'b'],
                    'b' => ['convertTo' => 'strong'],
                    'a' => ['convertTo' => 'strong'],
                ],
                'input' => '<b id="1">Test</b><strong id="2">Boldened</strong><a>link is strong</a><span>span is strong</span>',
                'expected' => '<strong id="1">Test</strong><strong id="2">Boldened</strong><strong>link is strong</strong><strong>span is strong</strong>',
            ],
            'chained substitute element tags different order' => [
                'validElements' => [
                    'b' => ['convertTo' => 'strong'],
                    'span' => ['convertTo' => 'b'],
                    'a' => ['convertTo' => 'strong'],
                    'strong' => ['attributes' => ['id' => true]],
                ],
                'input' => '<b id="1">Test</b><strong id="2">Boldened</strong><a>link is strong</a><span>span is strong</span>',
                'expected' => '<strong id="1">Test</strong><strong id="2">Boldened</strong><strong>link is strong</strong><strong>span is strong</strong>',
            ],
            'remove JS in link href' => [
                'validElements' => ['a' => ['attributes' => ['href' => true, 'target' => true, 'rel' => true]]],
                'input' => '<a href="javascript:alert(0);">Test</a>',
                'expected' => '<a>Test</a>',
            ],
            'remove JS with preceding backspace char in link href' => [
                'validElements' => ['a' => ['attributes' => ['href' => true, 'target' => true, 'rel' => true]]],
                'input' => '<a href="' . HTMLEditorSanitiserTest::CHAR_BACKSPACE . 'javascript:alert(0);">Test</a>',
                'expected' => '<a>Test</a>',
            ],
            'remove JS with trailing backspace char in link href' => [
                'validElements' => ['a' => ['attributes' => ['href' => true, 'target' => true, 'rel' => true]]],
                'input' => '<a href="javascript:alert(0);' . HTMLEditorSanitiserTest::CHAR_BACKSPACE . '">Test</a>',
                'expected' => '<a>Test</a>',
            ],
            'remove multiline JS in link href' => [
                'validElements' => ['a' => ['attributes' => ['href' => true, 'target' => true, 'rel' => true]]],
                'input' => '<a href="' . implode("\n", str_split(' javascript:')) . '">Test</a>',
                'expected' => '<a>Test</a>',
            ],
            'remove JS in area href' => [
                'validElements' => [
                    'map' => ['attributes' => ['name' => true]],
                    'area' => ['attributes' => ['href' => true, 'shape' => true, 'coords' => true]]
                ],
                'input' => '<map name="test"><area shape="rect" coords="34,44,270,350" href="javascript:alert(0);"></map>',
                'expected' => '<map name="test"><area shape="rect" coords="34,44,270,350"></map>',
            ],
            'area href not hardcoded to be removed' => [
                'validElements' => [
                    'map' => ['attributes' => ['name' => true]],
                    'area' => ['attributes' => ['href' => true, 'shape' => true, 'coords' => true]]
                ],
                'input' => '<map name="test"><area shape="rect" coords="34,44,270,350" href="valid-href"></map>',
                'expected' => '<map name="test"><area shape="rect" coords="34,44,270,350" href="valid-href"></map>',
            ],
            'remove JS in iframe src' => [
                'validElements' => ['iframe' => ['attributes' => ['src' => true]]],
                'input' => '<iframe src="javascript:alert(0);"></iframe>',
                'expected' => '<iframe></iframe>',
            ],
            'remove mixed case JS in iframe src' => [
                'validElements' => ['iframe' => ['attributes' => ['src' => true]]],
                'input' => '<iframe src="jAvAsCrIpT:alert(0);"></iframe>',
                'expected' => '<iframe></iframe>',
            ],
            'remove tabbed JS in iframe src' => [
                'validElements' => ['iframe' => ['attributes' => ['src' => true]]],
                'input' => "<iframe src=\"java\tscript:alert(0);\"></iframe>",
                'expected' => '<iframe></iframe>',
            ],
            'remove js with backspace char in iframe src' => [
                'validElements' => ['iframe' => ['attributes' => ['src' => true]]],
                'input' => '<iframe src="' . HTMLEditorSanitiserTest::CHAR_BACKSPACE . 'javascript:alert(0);"></iframe>',
                'expected' => '<iframe></iframe>',
            ],
            'keep safe data attributes when allowed' => [
                'validElements' => ['object' => ['attributes' => ['data' => true]]],
                'input' => '<object data="OK"></object>',
                'expected' => '<object data="OK"></object>',
            ],
            'remove JS in data attribute' => [
                'validElements' => ['object' => ['attributes' => ['data' => true]]],
                'input' => '<object data=javascript:alert()>',
                'expected' => '<object></object>',
            ],
            'remove JS from data attribute with quotes' => [
                'validElements' => ['object' => ['attributes' => ['data' => true]]],
                'input' => '<object data="javascript:alert()">',
                'expected' => '<object></object>',
            ],
            'remove JS with backspace char from data attribute' => [
                'validElements' => ['object' => ['attributes' => ['data' => true]]],
                'input' => '<object data="' . HTMLEditorSanitiserTest::CHAR_BACKSPACE . 'javascript:alert()">',
                'expected' => '<object></object>',
            ],
            'remove text/html from data attribute' => [
                'validElements' => ['object' => ['attributes' => ['data' => true]]],
                'input' => '<object data="data:text/html;base64,PHNjcmlwdD5hbGVydChkb2N1bWVudC5sb2NhdGlvbik8L3NjcmlwdD4=">',
                'expected' => '<object></object>',
            ],
            'remove weirdly formatted text/html from data attribute' => [
                'validElements' => ['object' => ['attributes' => ['data' => true]]],
                'input' => '<object data="' . implode("\n", str_split(' DATA:TEXT/HTML;')) . 'base64,PHNjcmlwdD5hbGVydChkb2N1bWVudC5sb2NhdGlvbik8L3NjcmlwdD4=">',
                'expected' => '<object></object>',
            ],
            'keep text/xml content in data attribute' => [
                'validElements' => ['object' => ['attributes' => ['data' => true]]],
                'input' => '<object data="data:text/xml;base64,PHNjcmlwdD5hbGVydChkb2N1bWVudC5sb2NhdGlvbik8L3NjcmlwdD4=">',
                'expected' => '<object data="data:text/xml;base64,PHNjcmlwdD5hbGVydChkb2N1bWVudC5sb2NhdGlvbik8L3NjcmlwdD4="></object>',
            ],
        ];
    }

    #[DataProvider('provideSanitise')]
    public function testSanitise(array $validElements, string $input, string $expected): void
    {
        $config = HTMLEditorConfig::get('htmleditorsanitisertest');
        $config->setElementRulesFromArray($validElements);
        $sanitiser = new HTMLEditorSanitiser($config);
        $htmlValue = new HTMLValue($input);

        $sanitiser->sanitise($htmlValue);
        $this->assertEquals($expected, $htmlValue->getContent());
    }

    public static function provideSanitiseLinkRel(): array
    {
        return [
            'rel attr added if target is set' => [
                'validElements' => ['a' => ['attributes' => ['href' => true, 'target' => true, 'rel' => true]]],
                'linkRelValue' => 'noopener noreferrer',
                'input' => '<a href="/test" target="_blank">Test</a>',
                'expected' => '<a href="/test" target="_blank" rel="noopener noreferrer">Test</a>',
            ],
            'rel attr added regardless of target value' => [
                'validElements' => ['a' => ['attributes' => ['href' => true, 'target' => true, 'rel' => true]]],
                'linkRelValue' => 'noopener noreferrer',
                'input' => '<a href="/test" target="_top">Test</a>',
                'expected' => '<a href="/test" target="_top" rel="noopener noreferrer">Test</a>',
            ],
            'rel attr removed if target is not set' => [
                'validElements' => ['a' => ['attributes' => ['href' => true, 'target' => true, 'rel' => true]]],
                'linkRelValue' => 'noopener noreferrer',
                'input' => '<a href="/test" rel="noopener noreferrer">Test</a>',
                'expected' => '<a href="/test">Test</a>',
            ],
            'rel attr removed if relVal is empty string' => [
                'validElements' => ['a' => ['attributes' => ['href' => true, 'target' => true, 'rel' => true]]],
                'linkRelValue' => '',
                'input' => '<a href="/test" rel="noopener noreferrer" target="_blank">Test</a>',
                'expected' => '<a href="/test" target="_blank">Test</a>',
            ],
            'rel attr unchanged if relVal is null' => [
                'validElements' => ['a' => ['attributes' => ['href' => true, 'target' => true, 'rel' => true]]],
                'linkRelValue' => null,
                'input' => '<a href="/test" target="_blank">Test</a>',
                'expected' => '<a href="/test" target="_blank">Test</a>',
            ],
        ];
    }

    #[DataProvider('provideSanitiseLinkRel')]
    public function testSanitiseLinkRel(array $validElements, ?string $linkRelValue, string $input, string $expected): void
    {
        $config = HTMLEditorConfig::get('htmleditorsanitisertest');
        $config->setElementRulesFromArray($validElements);
        $sanitiser = new HTMLEditorSanitiser($config);
        HTMLEditorSanitiser::config()->set('link_rel_value', $linkRelValue);
        $htmlValue = new HTMLValue($input);

        $sanitiser->sanitise($htmlValue);
        $this->assertEquals($expected, $htmlValue->getContent());
    }
}
