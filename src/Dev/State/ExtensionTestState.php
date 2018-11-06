<?php

namespace SilverStripe\Dev\State;

use LogicException;
use SilverStripe\Core\Extension;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\ORM\DataObject;

/**
 * Manages illegal and required extensions for sapphiretest
 */
class ExtensionTestState implements TestState
{
    /**
     * Called on setup
     *
     * @param SapphireTest $test
     */
    public function setUp(SapphireTest $test)
    {
        DataObject::flush_extra_methods_cache();
    }

    public function tearDown(SapphireTest $test)
    {
    }

    public function setUpOnce($class)
    {
        // May be altered by another class
        $isAltered = false;

        /** @var string|SapphireTest $class */
        /** @var string|DataObject $dataClass */
        // Remove any illegal extensions that are present
        foreach ($class::getIllegalExtensions() as $dataClass => $extensions) {
            if (!class_exists($dataClass)) {
                continue;
            }
            if ($extensions === '*') {
                $extensions = $dataClass::get_extensions();
            }
            foreach ($extensions as $extension) {
                if (!class_exists($extension) || !$dataClass::has_extension($extension)) {
                    continue;
                }
                $dataClass::remove_extension($extension);
                $isAltered = true;
            }
        }

        // Add any required extensions that aren't present
        foreach ($class::getRequiredExtensions() as $dataClass => $extensions) {
            if (!class_exists($dataClass)) {
                throw new LogicException("Test {$class} requires dataClass {$dataClass} which doesn't exist");
            }
            foreach ($extensions as $extension) {
                $extension = Extension::get_classname_without_arguments($extension);
                if (!class_exists($extension)) {
                    throw new LogicException("Test {$class} requires extension {$extension} which doesn't exist");
                }
                if (!$dataClass::has_extension($extension)) {
                    $dataClass::add_extension($extension);
                    $isAltered = true;
                }
            }
        }

        // clear singletons, they're caching old extension info
        // which is used in DatabaseAdmin->doBuild()
        Injector::inst()->unregisterObjects([
            DataObject::class,
            Extension::class
        ]);

        // If we have altered the schema, but SapphireTest::setUpBeforeClass() would not otherwise
        // reset the schema (if there were extra objects) then force a reset
        if ($isAltered && empty($class::getExtraDataObjects())) {
            DataObject::reset();
            $class::resetDBSchema(true, true);
        }
    }

    public function tearDownOnce($class)
    {
        DataObject::flush_extra_methods_cache();
    }
}
