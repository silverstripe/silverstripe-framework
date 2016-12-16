<?php

namespace SilverStripe\View\Tests\SSViewerTest;

use SilverStripe\ORM\ArrayList;
use SilverStripe\View\ViewableData;

/**
 * A test fixture that will echo back the template item
 */
class TestFixture extends ViewableData
{
    protected $name;

    public function __construct($name = null)
    {
        $this->name = $name;
        parent::__construct();
    }


    private function argedName($fieldName, $arguments)
    {
        $childName = $this->name ? "$this->name.$fieldName" : $fieldName;
        if ($arguments) {
            return $childName . '(' . implode(',', $arguments) . ')';
        } else {
            return $childName;
        }
    }

    public function obj($fieldName, $arguments = null, $cache = false, $cacheName = null)
    {
        $childName = $this->argedName($fieldName, $arguments);

        // Special field name Loop### to create a list
        if (preg_match('/^Loop([0-9]+)$/', $fieldName, $matches)) {
            $output = new ArrayList();
            for ($i = 0; $i < $matches[1]; $i++) {
                $output->push(new TestFixture($childName));
            }
            return $output;
        } else {
            if (preg_match('/NotSet/i', $fieldName)) {
                return new ViewableData();
            } else {
                return new TestFixture($childName);
            }
        }
    }


    public function XML_val($fieldName, $arguments = null, $cache = false)
    {
        if (preg_match('/NotSet/i', $fieldName)) {
            return '';
        } else {
            if (preg_match('/Raw/i', $fieldName)) {
                return $fieldName;
            } else {
                return '[out:' . $this->argedName($fieldName, $arguments) . ']';
            }
        }
    }

    public function hasValue($fieldName, $arguments = null, $cache = true)
    {
        return (bool)$this->XML_val($fieldName, $arguments);
    }
}
