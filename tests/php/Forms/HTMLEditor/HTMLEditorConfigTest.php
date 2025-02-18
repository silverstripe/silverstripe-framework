<?php

namespace SilverStripe\Forms\Tests\HTMLEditor;

use PHPUnit\Framework\Attributes\DataProvider;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Forms\HTMLEditor\HTMLEditorAttributeRule;
use SilverStripe\Forms\HTMLEditor\HTMLEditorConfig;
use SilverStripe\Forms\HTMLEditor\HTMLEditorElementRule;
use SilverStripe\Forms\HTMLEditor\HTMLEditorRuleSet;
use SilverStripe\Forms\HTMLEditor\TextAreaConfig;
use SilverStripe\Forms\Tests\HTMLEditor\HTMLEditorFieldTest\NullEditorConfig;

class HTMLEditorConfigTest extends SapphireTest
{

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

    public static function provideGet(): array
    {
        $configs = [
            'empty-config' => ['elementRules' => []],
            'special' => [
                'elementRules' => [
                    'p' => ['attributes' => ['id' => true]],
                    'div' => true,
                    'span' => [
                        'removeIfEmpty' => true,
                    ],
                ],
                'extraElementRules' => [
                    // this 'p' overrides the main one, note they don't get merged!
                    'p' => ['padEmpty' => true],
                    // REMOVES the span! Not allowed anymore.
                    'span' => null,
                    'table' => true,
                ],
                'options' => [
                    'something' => 'value',
                    'somethingelse' => null,
                    'another thing' => ['whatever'],
                    'ints too' => 123,
                ],
            ],
            'different-class-and-allow-all' => [
                'configClass' => NullEditorConfig::class,
                'elementRules' => [
                    '_global' => ['attributes' => ['*' => true]],
                    '*' => true,
                ],
            ],
        ];
        return [
            'basically nothing set' => [
                'predefinedConfigs' => $configs,
                'identifier' => 'empty-config',
                'expectedClass' => TextAreaConfig::class,
                'expectedOptions' => [
                    'editorIdentifier' => 'empty-config',
                ],
                'expectedElementRules' => [],
            ],
            'set all the things' => [
                'predefinedConfigs' => $configs,
                'identifier' => 'special',
                'expectedClass' => TextAreaConfig::class,
                'expectedOptions' => [
                    'something' => 'value',
                    'somethingelse' => null,
                    'another thing' => ['whatever'],
                    'ints too' => 123,
                    'editorIdentifier' => 'special',
                ],
                'expectedElementRules' => [
                    'p' => [
                        'nameIsPattern' => false,
                        'padEmpty' => true,
                        'removeIfEmpty' => false,
                        'removeIfNoAttributes' => false,
                        'attributes' => [],
                    ],
                    'div' => [
                        'nameIsPattern' => false,
                        'padEmpty' => false,
                        'removeIfEmpty' => false,
                        'removeIfNoAttributes' => false,
                        'attributes' => [],
                    ],
                    'table' => [
                        'nameIsPattern' => false,
                        'padEmpty' => false,
                        'removeIfEmpty' => false,
                        'removeIfNoAttributes' => false,
                        'attributes' => [],
                    ],
                ],
            ],
            'different class defined' => [
                'predefinedConfigs' => $configs,
                'identifier' => 'different-class-and-allow-all',
                'expectedClass' => NullEditorConfig::class,
                'expectedOptions' => [
                    'editorIdentifier' => 'different-class-and-allow-all',
                ],
                'expectedElementRules' => [
                    '_global' => [
                        'attributes' => [
                            '/^.*$/' => [
                                'nameIsPattern' => true,
                                'value' => [],
                                'valueType' => HTMLEditorAttributeRule::VALUE_VALID,
                            ],
                        ],
                    ],
                    '/^.*$/' => [
                        'nameIsPattern' => true,
                        'padEmpty' => false,
                        'removeIfEmpty' => false,
                        'removeIfNoAttributes' => false,
                        'attributes' => [],
                    ],
                ],
            ],
        ];
    }

    #[DataProvider('provideGet')]
    public function testGet(
        array $predefinedConfigs,
        string $identifier,
        string $expectedClass,
        array $expectedOptions,
        array $expectedElementRules
    ): void {
        HTMLEditorConfig::config()->set('default_config_definitions', $predefinedConfigs);
        $config = HTMLEditorConfig::get($identifier);

        $this->assertSame($expectedClass, get_class($config));
        $this->assertSame($expectedOptions, $config->getOptions());
        $this->assertSame($expectedElementRules, $this->getElementRulesAsArray($config->getElementRuleSet()));
    }

    public static function provideSetElementRulesFromArray(): array
    {
        return [
            'empty set' => [
                'rulesArray' => [],
                'expected' => [],
            ],
            'various rules 1' => [
                'rulesArray' => [
                    HTMLEditorElementRule::GLOBAL_NAME => ['attributes' => [
                        'id' => true,
                        'dir' => ['value' => ['ltr', 'rtl'], 'valueType' => HTMLEditorAttributeRule::VALUE_VALID]
                    ]],
                    'div' => true,
                    'p' => ['convertTo' => 'div'],
                    // Note "iframe" isn't included in $expected because "object" isn't an allowed element
                    'iframe' => ['convertTo' => 'object'],
                    'span' => [
                        'padEmpty' => true,
                        'removeIfEmpty' => true,
                        'removeIfNoAttributes' => false,
                    ],
                    'dagger' => [
                        'removeIfNoAttributes' => true,
                        'attributes' => [
                            'data-*' => true,
                            'test' => ['value' => 'test', 'valueType' => HTMLEditorAttributeRule::VALUE_FORCED],
                        ],
                    ],
                ],
                'expected' => [
                    HTMLEditorElementRule::GLOBAL_NAME => ['attributes' => [
                        'id' => [
                            'nameIsPattern' => false,
                            'value' => [],
                            'valueType' => HTMLEditorAttributeRule::VALUE_VALID,
                        ],
                        'dir' => [
                            'nameIsPattern' => false,
                            'value' => ['ltr', 'rtl'],
                            'valueType' => HTMLEditorAttributeRule::VALUE_VALID,
                        ],
                    ]],
                    'p' => ['convertTo' => 'div'],
                    'div' => [
                        'nameIsPattern' => false,
                        'padEmpty' => false,
                        'removeIfEmpty' => false,
                        'removeIfNoAttributes' => false,
                        'attributes' => [],
                    ],
                    'span' => [
                        'nameIsPattern' => false,
                        'padEmpty' => true,
                        'removeIfEmpty' => true,
                        'removeIfNoAttributes' => false,
                        'attributes' => [],
                    ],
                    'dagger' => [
                        'nameIsPattern' => false,
                        'padEmpty' => false,
                        'removeIfEmpty' => false,
                        'removeIfNoAttributes' => true,
                        'attributes' => [
                            'test' => [
                                'nameIsPattern' => false,
                                'value' => 'test',
                                'valueType' => HTMLEditorAttributeRule::VALUE_FORCED,
                            ],
                            '/^data-.*$/' => [
                                'nameIsPattern' => true,
                                'value' => [],
                                'valueType' => HTMLEditorAttributeRule::VALUE_VALID,
                            ],
                        ],
                    ],
                ],
            ],
            'various rules 2' => [
                'rulesArray' => [
                    'div' => null,
                    'span' => false,
                    'p' => [
                        'attributes' => [
                            // These don't get EXPLICITLY disallowed i.e. the global rules still apply
                            // and may allow them separately.
                            'id' => null,
                            'class' => false,
                            'style' => [
                                'isRequired' => true,
                                'value' => 'display: none;',
                                'valueType' => HTMLEditorAttributeRule::VALUE_DEFAULT,
                            ],
                        ],
                    ],
                    's?met+g' => [],
                ],
                'expected' => [
                    'p' => [
                        'nameIsPattern' => false,
                        'padEmpty' => false,
                        'removeIfEmpty' => false,
                        'removeIfNoAttributes' => false,
                        'attributes' => [
                            'style' => [
                                'nameIsPattern' => false,
                                'value' => 'display: none;',
                                'valueType' => HTMLEditorAttributeRule::VALUE_DEFAULT,
                                'isRequired' => true,
                            ],
                        ],
                    ],
                    '/^s.?met.+g$/' => [
                        'nameIsPattern' => true,
                        'padEmpty' => false,
                        'removeIfEmpty' => false,
                        'removeIfNoAttributes' => false,
                        'attributes' => [],
                    ],
                ],
            ],
        ];
    }

    #[DataProvider('provideSetElementRulesFromArray')]
    public function testSetElementRulesFromArray(array $rulesArray, array $expected): void
    {
        $config = new NullEditorConfig();
        $config->setElementRulesFromArray($rulesArray);
        $this->assertSame($expected, $this->getElementRulesAsArray($config->getElementRuleSet()));
    }

    private function getElementRulesAsArray(HTMLEditorRuleSet $ruleset): array
    {
        $elementRules = [
            HTMLEditorElementRule::GLOBAL_NAME => [
                'attributes' => $this->getAttributeRulesAsArray($ruleset->getGlobalRule()),
            ],
        ];
        if (empty($elementRules[HTMLEditorElementRule::GLOBAL_NAME]['attributes'])) {
            unset($elementRules[HTMLEditorElementRule::GLOBAL_NAME]);
        }
        foreach ($ruleset->getElementSubstitutionRules() as $from => $to) {
            $elementRules[$from] = ['convertTo' => $to];
        }
        foreach ($ruleset->getElementRules() as $name => $elementRule) {
            $elementRules[$name] = [
                'nameIsPattern' => $elementRule->getNameIsPattern(),
                'padEmpty' => $elementRule->getPadEmpty(),
                'removeIfEmpty' => $elementRule->getRemoveIfEmpty(),
                'removeIfNoAttributes' => $elementRule->getRemoveIfNoAttributes(),
                'attributes' => $this->getAttributeRulesAsArray($elementRule),
            ];
        }
        return $elementRules;
    }

    private function getAttributeRulesAsArray(HTMLEditorElementRule $elementRule): array
    {
        $attributeRules = [];
        foreach ($elementRule->getAttributeRules() as $name => $attributeRule) {
            $defaultValue = $attributeRule->getDefaultValue();
            $forcedValue = $attributeRule->getForcedValue();
            $validValues = $attributeRule->getValidValues();
            if ($defaultValue) {
                $value = $defaultValue;
                $valueType = HTMLEditorAttributeRule::VALUE_DEFAULT;
            } elseif ($forcedValue) {
                $value = $forcedValue;
                $valueType = HTMLEditorAttributeRule::VALUE_FORCED;
            } else {
                $value = $validValues;
                $valueType = HTMLEditorAttributeRule::VALUE_VALID;
            }
            $attributeRules[$name] = [
                'nameIsPattern' => $attributeRule->getNameIsPattern(),
                'value' => $value,
                'valueType' => $valueType,
            ];
            if ($attributeRule->getIsRequired()) {
                $attributeRules[$name]['isRequired'] = true;
            }
        }
        return $attributeRules;
    }
}
