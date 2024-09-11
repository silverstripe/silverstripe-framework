<?php

namespace SilverStripe\Dev\Tests;

use PHPUnit\Framework\ExpectationFailedException;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Model\List\ArrayList;
use SilverStripe\Security\Member;
use SilverStripe\Security\Permission;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\DataProviderExternal;
use SilverStripe\Dev\Exceptions\ExpectedErrorException;
use SilverStripe\Dev\Exceptions\ExpectedNoticeException;
use SilverStripe\Dev\Exceptions\ExpectedWarningException;

/**
 * @sometag This is a test annotation used in the testGetAnnotations test
 */
class SapphireTestTest extends SapphireTest
{

    /**
     * @return array
     */
    public static function provideResolveFixturePath()
    {
        return [
            'sameDirectory' => [
                __DIR__ . '/CsvBulkLoaderTest.yml',
                './CsvBulkLoaderTest.yml',
                'Could not resolve fixture path relative from same directory',
            ],
            'filenameOnly' => [
                __DIR__ . '/CsvBulkLoaderTest.yml',
                'CsvBulkLoaderTest.yml',
                'Could not resolve fixture path from filename only',
            ],
            'parentPath' => [
                dirname(__DIR__) . '/ORM/DataObjectTest.yml',
                '../ORM/DataObjectTest.yml',
                'Could not resolve fixture path from parent path',
            ],
            'absolutePath' => [
                dirname(__DIR__) . '/ORM/DataObjectTest.yml',
                dirname(__DIR__) . '/ORM/DataObjectTest.yml',
                'Could not relsolve fixture path from absolute path',
            ],
        ];
    }

    #[DataProvider('provideResolveFixturePath')]
    public function testResolveFixturePath($expected, $path, $message)
    {
        $this->assertEquals(
            $expected,
            $this->resolveFixturePath($path),
            $message
        );
    }

    /**
     * @useDatabase
     */
    public function testActWithPermission()
    {
        $this->logOut();
        $this->assertFalse(Permission::check('ADMIN'));
        $this->actWithPermission('ADMIN', function () {
            $this->assertTrue(Permission::check('ADMIN'), 'Member should now have ADMIN role');
            // check nested actAs calls work as expected
            Member::actAs(null, function () {
                $this->assertFalse(Permission::check('ADMIN'), 'Member should not act as ADMIN any more after reset');
            });
        });
    }

    /**
     * @useDatabase
     */
    public function testCreateMemberWithPermission()
    {
        $this->assertEmpty(
            Member::get()->filter(['Email' => 'TESTPERM@example.org']),
            'DB should not have the test member created when the test starts'
        );
        $this->createMemberWithPermission('TESTPERM');
        $this->assertCount(
            1,
            Member::get()->filter(['Email' => 'TESTPERM@example.org']),
            'Database should now contain the test member'
        );
    }

    /**
     * @param $match
     * @param $itemsForList
     */
    #[DataProviderExternal('\SilverStripe\Dev\Tests\SapphireTestTest\DataProvider', 'provideAllMatchingList')]
    public function testAssertListAllMatch($match, $itemsForList, $message)
    {
        $list = $this->generateArrayListFromItems($itemsForList);

        $this->assertListAllMatch($match, $list, $message);
    }

    /**
     * generate SS_List as this is not possible in dataProvider
     *
     * @param array $itemsForList
     *
     * @return ArrayList
     */
    private function generateArrayListFromItems($itemsForList)
    {
        $list = ArrayList::create();
        foreach ($itemsForList as $data) {
            $list->push(Member::create($data));
        }
        return $list;
    }

    /**
     * @param $match
     * @param $itemsForList
     */
    #[DataProviderExternal('\SilverStripe\Dev\Tests\SapphireTestTest\DataProvider', 'provideNotMatchingList')]
    public function testAssertListAllMatchFailsWhenNotMatchingAllItems($match, $itemsForList)
    {
        $this->expectException(ExpectationFailedException::class);
        $list = $this->generateArrayListFromItems($itemsForList);

        $this->assertListAllMatch($match, $list);
    }

    /**
     * @param $matches
     * @param $itemsForList
     */
    #[DataProviderExternal('\SilverStripe\Dev\Tests\SapphireTestTest\DataProvider', 'provideEqualListsWithEmptyList')]
    public function testAssertListContains($matches, $itemsForList)
    {
        $list = $this->generateArrayListFromItems($itemsForList);
        $list->push(Member::create(['FirstName' => 'Foo', 'Surname' => 'Foo']));
        $list->push(Member::create(['FirstName' => 'Bar', 'Surname' => 'Bar']));
        $list->push(Member::create(['FirstName' => 'Baz', 'Surname' => 'Baz']));

        $this->assertListContains($matches, $list, 'The list does not contain the expected items');
    }

    /**
     * @param $matches
     * @param $itemsForList array
     */
    #[DataProviderExternal('\SilverStripe\Dev\Tests\SapphireTestTest\DataProvider', 'provideNotContainingList')]
    public function testAssertListContainsFailsIfListDoesNotContainMatch($matches, $itemsForList)
    {
        $this->expectException(ExpectationFailedException::class);
        $list = $this->generateArrayListFromItems($itemsForList);
        $list->push(Member::create(['FirstName' => 'Foo', 'Surname' => 'Foo']));
        $list->push(Member::create(['FirstName' => 'Bar', 'Surname' => 'Bar']));
        $list->push(Member::create(['FirstName' => 'Baz', 'Surname' => 'Baz']));

        $this->assertListContains($matches, $list);
    }

    /**
     * @param $matches
     * @param $itemsForList
     */
    #[DataProviderExternal('\SilverStripe\Dev\Tests\SapphireTestTest\DataProvider', 'provideNotContainingList')]
    public function testAssertListNotContains($matches, $itemsForList)
    {
        $list = $this->generateArrayListFromItems($itemsForList);

        $this->assertListNotContains($matches, $list, 'List contains forbidden items');
    }

    /**
     * @param $matches
     * @param $itemsForList
     */
    #[DataProviderExternal('\SilverStripe\Dev\Tests\SapphireTestTest\DataProvider', 'provideEqualLists')]
    public function testAssertListNotContainsFailsWhenListContainsAMatch($matches, $itemsForList)
    {
        $this->expectException(ExpectationFailedException::class);
        $list = $this->generateArrayListFromItems($itemsForList);
        $list->push(Member::create(['FirstName' => 'Foo', 'Surname' => 'Foo']));
        $list->push(Member::create(['FirstName' => 'Bar', 'Surname' => 'Bar']));
        $list->push(Member::create(['FirstName' => 'Baz', 'Surname' => 'Baz']));

        $this->assertListNotContains($matches, $list);
    }

    /**
     * @param $matches
     * @param $itemsForList
     */
    #[DataProviderExternal('\SilverStripe\Dev\Tests\SapphireTestTest\DataProvider', 'provideEqualListsWithEmptyList')]
    public function testAssertListEquals($matches, $itemsForList)
    {
        $list = $this->generateArrayListFromItems($itemsForList);

        $this->assertListEquals($matches, $list, 'Lists do not equal');
    }

    /**
     * @param $matches
     * @param $itemsForList
     */
    #[DataProviderExternal('\SilverStripe\Dev\Tests\SapphireTestTest\DataProvider', 'provideNonEqualLists')]
    public function testAssertListEqualsFailsOnNonEqualLists($matches, $itemsForList)
    {
        $this->expectException(ExpectationFailedException::class);
        $list = $this->generateArrayListFromItems($itemsForList);

        $this->assertListEquals($matches, $list);
    }

    /**
     * This test intentionally has non-sensical annotations to test the parser
     *
     * @lorem ipsum
     * @param $one something
     * @param $two else
     */
    public function testGetAnnotations(): void
    {
        $this->assertSame([
            'method' => [
                'lorem' => [
                    'ipsum'
                ],
                'param' => [
                    '$one something',
                    '$two else',
                ],
            ],
            'class' => [
                'sometag' => [
                    'This is a test annotation used in the testGetAnnotations test'
                ],
            ],
        ], $this->getAnnotations());
    }

    #[DataProvider('provideEnableErrorHandler')]
    public function testEnableErrorHandler(int $errno, ?string $expectedClass): void
    {
        $this->enableErrorHandler();
        $bool = false;
        if ($expectedClass) {
            $this->expectException($expectedClass);
            $this->expectExceptionMessage('test');
        }
        if ($errno === E_USER_DEPRECATED) {
            // Prevent deprecation notices from being displayed
            set_error_handler(function ($errno, $errstr) use (&$bool) {
                if ($errno === E_USER_DEPRECATED) {
                    $bool = true;
                }
            });
            trigger_error('test', $errno);
        }
        trigger_error('test', $errno);
        if ($errno === E_USER_DEPRECATED) {
            restore_error_handler();
            $this->assertTrue($bool);
        }
    }

    public static function provideEnableErrorHandler(): array
    {
        // Only E_USER_* errors can be triggered, so that's all that's being tested
        return [
            'error' => [
                'errno' => E_USER_ERROR,
                'expectedClass' => ExpectedErrorException::class,
            ],
            'notice' => [
                'errno' => E_USER_NOTICE,
                'expectedClass' => ExpectedNoticeException::class,
            ],
            'warning' => [
                'errno' => E_USER_WARNING,
                'expectedClass' => ExpectedWarningException::class,
            ],
            'deprecated' => [
                'errno' => E_USER_DEPRECATED,
                'expectedClass' => null,
            ],
        ];
    }
}
