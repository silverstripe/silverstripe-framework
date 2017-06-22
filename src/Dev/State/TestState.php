<?php

namespace SilverStripe\Dev\State;

use SilverStripe\Dev\SapphireTest;
use SilverStripe\Dev\TestOnly;

/**
 * Helper for resetting, booting, or cleaning up test state.
 *
 * SapphireTest will detect all implementors of this interface during test execution
 */
interface TestState extends TestOnly
{
    /**
     * Called on setup
     *
     * @param SapphireTest $test
     */
    public function setUp(SapphireTest $test);

    /**
     * Called on tear down
     *
     * @param SapphireTest $test
     */
    public function tearDown(SapphireTest $test);

    /**
     * Called once on setup
     *
     * @param string $class Class being setup
     */
    public function setUpOnce($class);

    /**
     * Called once on tear down
     *
     * @param string $class Class being torn down
     */
    public function tearDownOnce($class);
}
