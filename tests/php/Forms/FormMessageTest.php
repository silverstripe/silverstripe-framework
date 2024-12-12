<?php

namespace SilverStripe\Forms\Tests;

use SilverStripe\Core\Injector\Injector;
use SilverStripe\Core\XssSanitiser;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Forms\Tests\FormMessageTest\TestFormMessage;
use SilverStripe\ORM\ValidationResult;

class FormMessageTest extends SapphireTest
{
    protected $usesDatabase = false;

    public function provideGetMessage(): array
    {
        return [
            'empty HTML' => [
                'message' => '',
                'type' => '',
                'casting' => ValidationResult::CAST_HTML,
                'expected' => '',
            ],
            'empty plain text' => [
                'message' => '',
                'type' => '',
                'casting' => ValidationResult::CAST_TEXT,
                'expected' => '',
            ],
            'plain HTML' => [
                'message' => 'just some text',
                'type' => '',
                'casting' => ValidationResult::CAST_HTML,
                'expected' => 'just some text',
            ],
            'plain plain text' => [
                'message' => 'just some text',
                'type' => '',
                'casting' => ValidationResult::CAST_TEXT,
                'expected' => 'just some text',
            ],
            'HTML in HTML' => [
                'message' => '<div class="js-my-div"><a href="https://example.com">link</a></div>',
                'type' => '',
                'casting' => ValidationResult::CAST_HTML,
                'expected' => '<div class="js-my-div"><a href="https://example.com">link</a></div>',
            ],
            'HTML in plain text' => [
                'message' => '<div class="js-my-div"><a href="https://example.com">link</a></div>',
                'type' => '',
                'casting' => ValidationResult::CAST_TEXT,
                'expected' => '<div class="js-my-div"><a href="https://example.com">link</a></div>',
            ],
            'Type doesnt matter HTML' => [
                'message' => '<div class="js-my-div"><a href="https://example.com">link</a></div>',
                'type' => 'an arbitrary string here',
                'casting' => ValidationResult::CAST_HTML,
                'expected' => '<div class="js-my-div"><a href="https://example.com">link</a></div>',
            ],
            'Type doesnt matter text' => [
                'message' => '<div class="js-my-div"><a href="https://example.com">link</a></div>',
                'type' => 'an arbitrary string here',
                'casting' => ValidationResult::CAST_TEXT,
                'expected' => '<div class="js-my-div"><a href="https://example.com">link</a></div>',
            ],
        ];
    }

    /**
     * Test that getMessage() generally works and calls the sanitiser as appropriate.
     * Note we don't actually test the sanitisation here, as that is handled by the sanitiser's unit tests.
     * @dataProvider provideGetMessage
     */
    public function testGetMessage(string $message, string $type, string $casting, string $expected): void
    {
        $mockSanitiserClass = get_class(new class extends XssSanitiser {
            public static int $called = 0;
            public function sanitiseString(string $html): string
            {
                static::$called++;
                return parent::sanitiseString($html);
            }
        });
        Injector::inst()->load([
            XssSanitiser::class => [
                'class' => $mockSanitiserClass,
            ],
        ]);
        $expectedSanitisationCount = $casting === ValidationResult::CAST_HTML ? 1 : 0;

        try {
            $formMessage = new TestFormMessage();
            $formMessage->setMessage($message, $type, $casting);
            $this->assertSame($expected, $formMessage->getMessage());
            $this->assertSame($expectedSanitisationCount, $mockSanitiserClass::$called);
        } finally {
            $mockSanitiserClass::$called = 0;
        }
    }
}
