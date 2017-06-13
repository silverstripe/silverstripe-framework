<?php

namespace SilverStripe\Dev;

use LogicException;
use SilverStripe\Core\Extension;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\ORM\DataObject;

/**
 * Manages illegal and required extensions for sapphiretest
 */
class ExtensionTestState implements TestState
{
    /**
     * @var array
     */
    protected $extensionsToReapply = [];

    /**
     * @var array
     */
    protected $extensionsToRemove = [];

    /**
     * Called on setup
     *
     * @param SapphireTest $test
     */
    public function setUp(SapphireTest $test)
    {
    }

    public function tearDown(SapphireTest $test)
    {
    }

    public function setUpOnce($class)
    {
        // May be altered by another class
        $isAltered = $this->extensionsToReapply || $this->extensionsToRemove;

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
                if (!isset($this->extensionsToReapply[$dataClass])) {
                    $this->extensionsToReapply[$dataClass] = array();
                }
                $this->extensionsToReapply[$dataClass][] = $extension;
                $dataClass::remove_extension($extension);
                $isAltered = true;
            }
        }

        // Add any required extensions that aren't present
        foreach ($class::getRequiredExtensions() as $dataClass => $extensions) {
            if (!class_exists($dataClass)) {
                throw new LogicException("Test {$class} requires dataClass {$dataClass} which doesn't exist");
            }
            $this->extensionsToRemove[$dataClass] = array();
            foreach ($extensions as $extension) {
                $dataClass = Extension::get_classname_without_arguments($extension);
                if (!class_exists($dataClass)) {
                    $self = static::class;
                    throw new LogicException("Test {$self} requires extension {$extension} which doesn't exist");
                }
                if (!$dataClass::has_extension($extension)) {
                    if (!isset($this->extensionsToRemove[$dataClass])) {
                        $this->extensionsToReapply[$dataClass] = array();
                    }
                    $this->extensionsToRemove[$dataClass][] = $extension;
                    $dataClass::add_extension($extension);
                    $isAltered = true;
                }
            }
        }

        // clear singletons, they're caching old extension info
        // which is used in DatabaseAdmin->doBuild()
        Injector::inst()->unregisterObjects(DataObject::class);

        // If we have altered the schema, but SapphireTest::setUpBeforeClass() would not otherwise
        // reset the schema (if there were extra objects) then force a reset
        if ($isAltered && empty($class::getExtraDataObjects())) {
            DataObject::reset();
            if (!SapphireTest::using_temp_db()) {
                SapphireTest::create_temp_db();
            }
            $class::resetDBSchema(true);
        }
    }

    public function tearDownOnce($class)
    {
        // @todo: This isn't strictly necessary to restore extensions, but only to ensure that
        // Object::$extra_methods is properly flushed. This should be replaced with a simple
        // flush mechanism for each $class.
        /** @var string|DataObject $dataClass */

        // Remove extensions added for testing
        foreach ($this->extensionsToRemove as $dataClass => $extensions) {
            foreach ($extensions as $extension) {
                $dataClass::remove_extension($extension);
            }
        }

        // Reapply ones removed
        foreach ($this->extensionsToReapply as $dataClass => $extensions) {
            foreach ($extensions as $extension) {
                $dataClass::add_extension($extension);
            }
        }
    }
}
