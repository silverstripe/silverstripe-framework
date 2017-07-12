<?php

namespace SilverStripe\Core\Tests\Manifest;

use SilverStripe\Core\Manifest\PrioritySorter;
use SilverStripe\Dev\SapphireTest;

class PrioritySorterTest extends SapphireTest
{
    /**
     * @var PrioritySorter
     */
    protected $sorter;

    public function setUp()
    {
        parent::setUp();
        $modules = [
            'module/one' => 'I am module one',
            'module/two' => 'I am module two',
            'module/three' => 'I am module three',
            'module/four' => 'I am module four',
            'module/five' => 'I am module five',
        ];
        $this->sorter = new PrioritySorter($modules);
    }

    public function testModuleSortingWithNoVarsAndNoRest()
    {
        $this->sorter->setPriorities([
            'module/three',
            'module/one',
            'module/two',
        ]);

        $result = $this->sorter->getSortedList();
        $keys = array_keys($result);
        $this->assertEquals('module/three', $keys[0]);
        $this->assertEquals('module/one', $keys[1]);
        $this->assertEquals('module/two', $keys[2]);
        $this->assertEquals('module/four', $keys[3]);
        $this->assertEquals('module/five', $keys[4]);
    }

    public function testModuleSortingWithVarsAndNoRest()
    {
        $this->sorter->setPriorities([
            'module/three',
            '$project',
        ])
            ->setVariable('$project', 'module/one');

        $result = $this->sorter->getSortedList();
        $keys = array_keys($result);
        $this->assertEquals('module/three', $keys[0]);
        $this->assertEquals('module/one', $keys[1]);
        $this->assertEquals('module/two', $keys[2]);
        $this->assertEquals('module/four', $keys[3]);
        $this->assertEquals('module/five', $keys[4]);
    }

    public function testModuleSortingWithNoVarsAndWithRest()
    {
        $this->sorter->setPriorities([
            'module/two',
            '$other_modules',
            'module/four',
        ])
            ->setRestKey('$other_modules');
        $result = $this->sorter->getSortedList();
        $keys = array_keys($result);
        $this->assertEquals('module/two', $keys[0]);
        $this->assertEquals('module/one', $keys[1]);
        $this->assertEquals('module/three', $keys[2]);
        $this->assertEquals('module/five', $keys[3]);
        $this->assertEquals('module/four', $keys[4]);
    }

    public function testModuleSortingWithVarsAndWithRest()
    {
        $this->sorter->setPriorities([
            'module/two',
            'other_modules',
            '$project',
        ])
            ->setVariable('$project', 'module/four')
            ->setRestKey('other_modules');

        $result = $this->sorter->getSortedList();
        $keys = array_keys($result);
        $this->assertEquals('module/two', $keys[0]);
        $this->assertEquals('module/one', $keys[1]);
        $this->assertEquals('module/three', $keys[2]);
        $this->assertEquals('module/five', $keys[3]);
        $this->assertEquals('module/four', $keys[4]);
    }
}
