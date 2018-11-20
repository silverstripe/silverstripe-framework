<?php

namespace SilverStripe\Forms\Tests;

use SilverStripe\Assets\File;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Forms\TreeMultiselectField;

class TreeMultiselectFieldTest extends SapphireTest
{
    protected static $fixture_file = 'TreeDropdownFieldTest.yml';

    public function testEmptyChoiceReadonly()
    {
        $field = new TreeMultiselectField('TestTree', 'Test tree', File::class);

        $asdf = $this->objFromFixture(File::class, 'asdf');
        $subfolderfile1 = $this->objFromFixture(File::class, 'subfolderfile1');

        $schemaStateDefaults = $field->getSchemaStateDefaults();
        $this->assertArraySubset(['id' => 'TestTree', 'name' => 'TestTree', 'value' => 'unchanged'], $schemaStateDefaults, $strict = true);

        $html = $field->performReadonlyTransformation()->Field();

        $this->assertEquals(
            <<<"HTML"
<span id="TestTree_ReadonlyValue" class="readonly">
	<i>('none')</i>
</span><input type="hidden" name="TestTree" class="hidden" id="TestTree" />
HTML
            ,
            $html
        );
    }

    public function testReadonly()
    {
        $field = new TreeMultiselectField('TestTree', 'Test tree', File::class);

        $asdf = $this->objFromFixture(File::class, 'asdf');
        $subfolderfile1 = $this->objFromFixture(File::class, 'subfolderfile1');

        $field->setValue(implode(',', [$asdf->ID, $subfolderfile1->ID]));

        $schemaStateDefaults = $field->getSchemaStateDefaults();
        $this->assertArraySubset(['id' => 'TestTree', 'name' => 'TestTree', 'value' => [$asdf->ID, $subfolderfile1->ID]], $schemaStateDefaults, $strict = true);

        $html = (string)$field->performReadonlyTransformation()->Field();

        $this->assertEquals(
            <<<"HTML"
<span id="TestTree_ReadonlyValue" class="readonly">
	&lt;Special &amp; characters&gt;, TestFile1InSubfolder
</span><input type="hidden" name="TestTree" value="{$asdf->ID},{$subfolderfile1->ID}" class="hidden" id="TestTree" />
HTML
            ,
            $html
        );
    }
}
