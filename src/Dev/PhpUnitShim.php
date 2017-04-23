<?php
// Ensure this class can be autoloaded when installed without dev dependencies.
// It's included by default through composer's autoloading.
// class_exists() triggers PSR-4 autoloaders, which should discover if PHPUnit is installed.
// TODO Factor out SapphireTest references from non-dev core code (avoid autoloading in the first place)
namespace {

    if (!class_exists('PHPUnit_Framework_TestCase')) {
        class PHPUnit_Framework_TestCase
        {
        }
    }

}
