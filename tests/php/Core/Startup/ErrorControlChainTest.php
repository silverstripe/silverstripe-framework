<?php

namespace SilverStripe\Core\Tests\Startup;

use Exception;
use Foo;
use SilverStripe\Core\Startup\ErrorControlChain;
use SilverStripe\Dev\SapphireTest;

class ErrorControlChainTest extends SapphireTest
{

    protected function setUp()
    {

        // Check we can run PHP at all
        $null = is_writeable('/dev/null') ? '/dev/null' : 'NUL';
        exec("php -v 2> $null", $out, $rv);

        if ($rv != 0) {
            $this->markTestSkipped("Can't run PHP from the command line - is it in your path?");
        }

        parent::setUp();
    }

    public function testErrorSuppression()
    {

        // Errors disabled by default
        $chain = new ErrorControlChainTest\ErrorControlChainTest_Chain();
        $chain->setDisplayErrors('Off'); // mocks display_errors: Off
        $initialValue = null;
        $whenNotSuppressed = null;
        $whenSuppressed = null;
        $chain->then(function (ErrorControlChainTest\ErrorControlChainTest_Chain $chain) use (
            &$initialValue,
            &$whenNotSuppressed,
            &$whenSuppressed
        ) {
            $initialValue = $chain->getDisplayErrors();
            $chain->setSuppression(false);
            $whenNotSuppressed = $chain->getDisplayErrors();
            $chain->setSuppression(true);
            $whenSuppressed = $chain->getDisplayErrors();
        })->execute();

        // Disabled errors never un-disable
        $this->assertEquals(0, $initialValue); // Chain starts suppressed
        $this->assertEquals(0, $whenSuppressed); // false value used internally when suppressed
        $this->assertEquals('Off', $whenNotSuppressed); // false value set by php ini when suppression lifted
        $this->assertEquals('Off', $chain->getDisplayErrors()); // Correctly restored after run

        // Errors enabled by default
        $chain = new ErrorControlChainTest\ErrorControlChainTest_Chain();
        $chain->setDisplayErrors('Yes'); // non-falsey ini value
        $initialValue = null;
        $whenNotSuppressed = null;
        $whenSuppressed = null;
        $chain->then(function (ErrorControlChainTest\ErrorControlChainTest_Chain $chain) use (
            &$initialValue,
            &$whenNotSuppressed,
            &$whenSuppressed
        ) {
            $initialValue = $chain->getDisplayErrors();
            $chain->setSuppression(true);
            $whenSuppressed = $chain->getDisplayErrors();
            $chain->setSuppression(false);
            $whenNotSuppressed = $chain->getDisplayErrors();
        })->execute();

        // Errors can be suppressed an un-suppressed when initially enabled
        $this->assertEquals(0, $initialValue); // Chain starts suppressed
        $this->assertEquals(0, $whenSuppressed); // false value used internally when suppressed
        $this->assertEquals('Yes', $whenNotSuppressed); // false value set by php ini when suppression lifted
        $this->assertEquals('Yes', $chain->getDisplayErrors()); // Correctly restored after run

        // Fatal error
        $chain = new ErrorControlChainTest\ErrorControlChainTest_Chain();

        list($out, $code) = $chain
            ->then(function () {
                Foo::bar(); // Non-existant class causes fatal error
            })
            ->thenIfErrored(function () {
                echo "Done";
            })
            ->executeInSubprocess();

        $this->assertEquals('Done', $out);

        // User error

        $chain = new ErrorControlChainTest\ErrorControlChainTest_Chain();

        list($out, $code) = $chain
            ->then(function () {
                user_error('Error', E_USER_ERROR);
            })
            ->thenIfErrored(function () {
                echo "Done";
            })
            ->executeInSubprocess();

        $this->assertEquals('Done', $out);

        // Recoverable error

        $chain = new ErrorControlChainTest\ErrorControlChainTest_Chain();

        list($out, $code) = $chain
            ->then(function () {
                $x = function (ErrorControlChain $foo) {
                };
                $x(1); // Calling against type
            })
            ->thenIfErrored(function () {
                echo "Done";
            })
            ->executeInSubprocess();

        $this->assertEquals('Done', $out);

        // Memory exhaustion

        $chain = new ErrorControlChainTest\ErrorControlChainTest_Chain();

        list($out, $code) = $chain
            ->then(function () {
                ini_set('memory_limit', '10M');
                $a = array();
                while (1) {
                    $a[] = 1;
                }
            })
            ->thenIfErrored(function () {
                echo "Done";
            })
            ->executeInSubprocess();

        $this->assertEquals('Done', $out);

        // Exceptions

        $chain = new ErrorControlChainTest\ErrorControlChainTest_Chain();

        list($out, $code) = $chain
            ->then(function () {
                throw new Exception("bob");
            })
            ->thenIfErrored(function () {
                echo "Done";
            })
            ->executeInSubprocess();

        $this->assertEquals('Done', $out);
    }

    public function testExceptionSuppression()
    {
        $chain = new ErrorControlChainTest\ErrorControlChainTest_Chain();

        list($out, $code) = $chain
            ->then(function () {
                throw new Exception('This exception should be suppressed');
            })
            ->thenIfErrored(function () {
                echo "Done";
            })
            ->executeInSubprocess();

        $this->assertEquals('Done', $out);
    }

    public function testErrorControl()
    {
        $chain = new ErrorControlChainTest\ErrorControlChainTest_Chain();

        list($out, $code) = $chain
            ->then(function () {
                echo 'preThen,';
            })
            ->thenIfErrored(function () {
                echo 'preThenIfErrored,';
            })
            ->thenAlways(function () {
                echo 'preThenAlways,';
            })
            ->then(function () {
                user_error('An error', E_USER_ERROR);
            })
            ->then(function () {
                echo 'postThen,';
            })
            ->thenIfErrored(function () {
                echo 'postThenIfErrored,';
            })
            ->thenAlways(function () {
                echo 'postThenAlways,';
            })
            ->executeInSubprocess();

        $this->assertEquals(
            "preThen,preThenAlways,postThenIfErrored,postThenAlways,",
            $out
        );
    }

    public function testSuppressionControl()
    {
        // Turning off suppression before execution

        $chain = new ErrorControlChainTest\ErrorControlChainTest_Chain();
        $chain->setSuppression(false);

        list($out, $code) = $chain
            ->then(function ($chain) {
                Foo::bar(); // Non-existant class causes fatal error
            })
            ->executeInSubprocess(true);

        $this->assertContains('Fatal error', $out);
        $this->assertContains('Foo', $out);

        // Turning off suppression during execution

        $chain = new ErrorControlChainTest\ErrorControlChainTest_Chain();

        list($out, $code) = $chain
            ->then(function ($chain) {
                $chain->setSuppression(false);
                Foo::bar(); // Non-existent class causes fatal error
            })
            ->executeInSubprocess(true);

        $this->assertContains('Fatal error', $out);
        $this->assertContains('Foo', $out);
    }

    public function testDoesntAffectNonFatalErrors()
    {
        $chain = new ErrorControlChainTest\ErrorControlChainTest_Chain();

        list($out, $code) = $chain
            ->then(function () {
                $array = null;
                if (@$array['key'] !== null) {
                    user_error('Error', E_USER_ERROR);
                }
            })
            ->then(function () {
                echo "Good";
            })
            ->thenIfErrored(function () {
                echo "Bad";
            })
            ->executeInSubprocess();

        $this->assertContains("Good", $out);
    }

    public function testDoesntAffectCaughtExceptions()
    {
        $chain = new ErrorControlChainTest\ErrorControlChainTest_Chain();

        list($out, $code) = $chain
            ->then(function () {
                try {
                    throw new Exception('Error');
                } catch (Exception $e) {
                    echo "Good";
                }
            })
            ->thenIfErrored(function () {
                echo "Bad";
            })
            ->executeInSubprocess();

        $this->assertContains("Good", $out);
    }

    public function testDoesntAffectHandledErrors()
    {
        $chain = new ErrorControlChainTest\ErrorControlChainTest_Chain();

        list($out, $code) = $chain
            ->then(function () {
                set_error_handler(
                    function () {
                        /* NOP */
                    }
                );
                user_error('Error', E_USER_ERROR);
            })
            ->then(function () {
                echo "Good";
            })
            ->thenIfErrored(function () {
                echo "Bad";
            })
            ->executeInSubprocess();

        $this->assertContains("Good", $out);
    }

    public function testMemoryConversion()
    {
        $chain = new ErrorControlChainTest\ErrorControlChainTest_Chain();

        $this->assertEquals(200, $chain->translateMemstring('200'));
        $this->assertEquals(300, $chain->translateMemstring('300'));

        $this->assertEquals(2 * 1024, $chain->translateMemstring('2k'));
        $this->assertEquals(3 * 1024, $chain->translateMemstring('3K'));

        $this->assertEquals(2 * 1024 * 1024, $chain->translateMemstring('2m'));
        $this->assertEquals(3 * 1024 * 1024, $chain->translateMemstring('3M'));

        $this->assertEquals(2 * 1024 * 1024 * 1024, $chain->translateMemstring('2g'));
        $this->assertEquals(3 * 1024 * 1024 * 1024, $chain->translateMemstring('3G'));

        $this->assertEquals(200, $chain->translateMemstring('200foo'));
        $this->assertEquals(300, $chain->translateMemstring('300foo'));
    }
}
