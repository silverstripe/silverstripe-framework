<?php

namespace SilverStripe\Forms\Tests\GridField;

use LogicException;
use ReflectionMethod;
use ReflectionProperty;
use SilverStripe\Control\Controller;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridFieldConfig_Base;
use SilverStripe\Forms\GridField\GridFieldDetailForm;
use SilverStripe\Forms\GridField\GridFieldDetailForm_ItemRequest;
use SilverStripe\ORM\ArrayList;
use SilverStripe\View\ArrayData;

class GridFieldDetailForm_ItemRequestTest extends SapphireTest
{
    protected $usesDatabase = false;

    public function testItemEditFormThrowsException()
    {
        $gridField = new GridField('dummy', 'dummy', new ArrayList(), new GridFieldConfig_Base());
        $modelClass = ArrayData::class;
        $gridField->setModelClass($modelClass);
        $itemRequest = new GridFieldDetailForm_ItemRequest($gridField, new GridFieldDetailForm(), new ArrayData(), new Controller(), '');

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage(
            'Cannot dynamically determine form fields. Pass the fields to GridFieldDetailForm::setFields()'
            . " or implement a getCMSFields() method on $modelClass"
        );

        $itemRequest->ItemEditForm();
    }

    public function testGetGridFieldItemAdjacencies(): void
    {
        $list = new ArrayList([
            new ArrayData(['ID' => 101]),
            new ArrayData(['ID' => 102]),
            new ArrayData(['ID' => 103]),
            new ArrayData(['ID' => 104]),
            new ArrayData(['ID' => 105]),
            new ArrayData(['ID' => 106]),
            new ArrayData(['ID' => 107]),
            new ArrayData(['ID' => 108]),
            new ArrayData(['ID' => 109]),
        ]);
        $gridField = new GridField('dummy', 'dummy', $list, new GridFieldConfig_Base());
        $modelClass = ArrayData::class;
        $gridField->setModelClass($modelClass);
        $itemRequest = new GridFieldDetailForm_ItemRequest($gridField, new GridFieldDetailForm(), new ArrayData(), new Controller(), '');
        $request = new HTTPRequest('GET', '/', [
            'gridState-dummy-0' => '{"GridFieldPaginator":{"currentPage":2,"itemsPerPage":3}}'
        ]);
        $itemRequest->setRequest($request);
        // Assert method
        $refl = new ReflectionMethod($itemRequest, 'getGridFieldItemAdjacencies');
        $refl->setAccessible(true);
        $expectedData = [103, 104, 105, 106, 107];
        $this->assertSame($expectedData, $refl->invoke($itemRequest));
        // Assert cache
        $refl = new ReflectionProperty($itemRequest, 'cachedGridFieldItemAdjacencies');
        $refl->setAccessible(true);
        $this->assertSame(
            ['9cffefcf339420d11129b3220eccf141' => $expectedData],
            $refl->getValue($itemRequest)
        );
    }
}
