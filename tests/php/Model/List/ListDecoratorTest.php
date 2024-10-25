<?php

namespace SilverStripe\Model\Tests\List;

use ArrayIterator;
use LogicException;
use PHPUnit\Framework\MockObject\MockObject;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Model\List\ArrayList;
use SilverStripe\Model\List\ListDecorator;
use SilverStripe\Model\List\SS_List;
use PHPUnit\Framework\Attributes\DataProvider;
use SilverStripe\Model\List\Map;

/**
 * This test class is testing that ListDecorator correctly proxies its calls through to the underlying SS_List
 */
class ListDecoratorTest extends SapphireTest
{
    /**
     * @var ArrayList|MockObject
     */
    protected $list;

    /**
     * @var ListDecorator|MockObject
     */
    protected $decorator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->list = $this->createMock(ArrayList::class);
        // ListDecorator is an abstract class so cannot be instantiated, though has no abstract methods
        $this->decorator = new class ($this->list) extends ListDecorator {
        };
    }

    public function testGetIterator()
    {
        $iterator = new ArrayIterator();
        $this->list->expects($this->once())->method('getIterator')->willReturn($iterator);
        $this->assertSame($iterator, $this->decorator->getIterator());
    }

    public function testCanSortBy()
    {
        $this->list->expects($this->once())->method('canSortBy')->with('foo')->willReturn(true);
        $this->assertTrue($this->decorator->canSortBy('foo'));
    }

    public function testRemove()
    {
        $this->list->expects($this->once())->method('remove')->with('foo');
        $this->decorator->remove('foo');
    }

    /**
     * @param array $input
     */
    #[DataProvider('filterProvider')]
    public function testExclude($input)
    {
        $mock = $this->createMock(ArrayList::class);
        $this->list->expects($this->once())->method('exclude')->with($input)->willReturn($mock);
        $this->assertSame($mock, $this->decorator->exclude($input));
    }

    /**
     * @param array $input
     */
    #[DataProvider('filterProvider')]
    public function testExcludeAny($input)
    {
        $mock = $this->createMock(ArrayList::class);
        $this->list->expects($this->once())->method('excludeAny')->with($input)->willReturn($mock);
        $this->assertSame($mock, $this->decorator->excludeAny($input));
    }

    /**
     * @param array $input
     */
    #[DataProvider('filterProvider')]
    public function testFilter($input)
    {
        $mock = $this->createMock(ArrayList::class);
        $this->list->expects($this->once())->method('filter')->with($input)->willReturn($mock);
        $this->assertSame($mock, $this->decorator->filter($input));
    }

    /**
     * @param array $input
     */
    #[DataProvider('filterProvider')]
    public function testFilterAny($input)
    {
        $mock = $this->createMock(ArrayList::class);
        $this->list->expects($this->once())->method('filterAny')->with($input)->willReturn($mock);
        $this->assertSame($mock, $this->decorator->filterAny($input));
    }

    /**
     * @param array $input
     */
    #[DataProvider('filterProvider')]
    public function testSort($input)
    {
        $mock = $this->createMock(ArrayList::class);
        $this->list->expects($this->once())->method('sort')->with($input)->willReturn($mock);
        $this->assertSame($mock, $this->decorator->sort($input));
    }

    /**
     * @return array[]
     */
    public static function filterProvider()
    {
        return [
            ['Name', 'Bob'],
            ['Name', ['aziz', 'Bob']],
            [['Name' =>'bob', 'Age' => 21]],
            [['Name' =>'bob', 'Age' => [21, 43]]],
        ];
    }

    public function testCanFilterBy()
    {
        $this->list->expects($this->once())->method('canFilterBy')->with('Title')->willReturn(false);
        $this->assertFalse($this->decorator->canFilterBy('Title'));
    }

    public function testFilterByCallback()
    {
        $input = new ArrayList([
            ['Name' => 'Leslie'],
            ['Name' => 'Maxime'],
            ['Name' => 'Sal'],
        ]);

        $callback = function ($item, SS_List $list) {
            return $item->Name === 'Maxime';
        };

        $this->decorator->setList($input);
        $result = $this->decorator->filterByCallback($callback);

        $this->assertCount(1, $result);
        $this->assertSame('Maxime', $result->first()->Name);
    }

    public function testFind()
    {
        $this->list->expects($this->once())->method('find')->with('foo', 'bar')->willReturn('mock');
        $this->assertSame('mock', $this->decorator->find('foo', 'bar'));
    }

    public function testDebug()
    {
        $this->list->expects($this->once())->method('debug')->willReturn('mock');
        $this->assertSame('mock', $this->decorator->debug());
    }

    public function testCount()
    {
        $this->list->expects($this->once())->method('count')->willReturn(5);
        $this->assertSame(5, $this->decorator->Count());
    }

    public function testEach()
    {
        $callable = function () {
            // noop
        };
        $mock = $this->createMock(ArrayList::class);
        $this->list->expects($this->once())->method('each')->with($callable)->willReturn($mock);
        $this->assertSame($mock, $this->decorator->each($callable));
    }

    public function testOffsetExists()
    {
        $this->list->expects($this->once())->method('offsetExists')->with('foo')->willReturn(true);
        $this->assertSame(true, $this->decorator->offsetExists('foo'));
    }

    public function testGetList()
    {
        $this->assertSame($this->list, $this->decorator->getList());
    }

    public function testColumnUnique()
    {
        $this->list->expects($this->once())->method('columnUnique')->with('ID')->willReturn(['foo']);
        $this->assertSame(['foo'], $this->decorator->columnUnique('ID'));
    }

    public function testMap()
    {
        $return = new Map(new ArrayList());
        $this->list->expects($this->once())->method('map')->with('ID', 'Title')->willReturn($return);
        $this->assertSame($return, $this->decorator->map('ID', 'Title'));
    }

    public function testReverse()
    {
        $mock = $this->createMock(ArrayList::class);
        $this->list->expects($this->once())->method('reverse')->willReturn($mock);
        $this->assertSame($mock, $this->decorator->reverse());
    }

    public function testOffsetGet()
    {
        $this->list->expects($this->once())->method('offsetGet')->with(2)->willReturn('mock');
        $this->assertSame('mock', $this->decorator->offsetGet(2));
    }

    public function testExists()
    {
        $this->list->expects($this->once())->method('exists')->willReturn(false);
        $this->assertFalse($this->decorator->exists());
    }

    public function testByID()
    {
        $this->list->expects($this->once())->method('byID')->with(123)->willReturn('mock');
        $this->assertSame('mock', $this->decorator->byID(123));
    }

    public function testByIDs()
    {
        $mock = $this->createMock(ArrayList::class);
        $this->list->expects($this->once())->method('byIDs')->with([1, 2])->willReturn($mock);
        $this->assertSame($mock, $this->decorator->byIDs([1, 2]));
    }

    public function testToArray()
    {
        $this->list->expects($this->once())->method('toArray')->willReturn(['foo', 'bar']);
        $this->assertSame(['foo', 'bar'], $this->decorator->toArray());
    }

    public function testToNestedArray()
    {
        $this->list->expects($this->once())->method('toNestedArray')->willReturn(['foo', 'bar']);
        $this->assertSame(['foo', 'bar'], $this->decorator->toNestedArray());
    }

    public function testOffsetSet()
    {
        $this->list->expects($this->once())->method('offsetSet')->with('foo', 'bar');
        $this->decorator->offsetSet('foo', 'bar');
    }

    public function testOffsetUnset()
    {
        $this->list->expects($this->once())->method('offsetUnset')->with('foo');
        $this->decorator->offsetUnset('foo');
    }

    public function testLimit()
    {
        $mockLimitedList = $this->list;
        $this->list->expects($this->once())->method('limit')->with(5, 3)->willReturn($mockLimitedList);
        $this->assertSame($mockLimitedList, $this->decorator->limit(5, 3));
    }

    public function testTotalItems()
    {
        $this->list->expects($this->once())->method('count')->willReturn(5);
        $this->assertSame(5, $this->decorator->TotalItems());
    }

    public function testAdd()
    {
        $this->list->expects($this->once())->method('add')->with('foo');
        $this->decorator->add('foo');
    }

    public function testFirst()
    {
        $this->list->expects($this->once())->method('first')->willReturn(1);
        $this->assertSame(1, $this->decorator->first());
    }

    public function testLast()
    {
        $this->list->expects($this->once())->method('last')->willReturn(10);
        $this->assertSame(10, $this->decorator->last());
    }

    public function testColumn()
    {
        $this->list->expects($this->once())->method('column')->with('DOB')->willReturn(['foo']);
        $this->assertSame(['foo'], $this->decorator->column('DOB'));
    }
}
