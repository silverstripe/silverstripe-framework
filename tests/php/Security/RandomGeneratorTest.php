<?php

namespace SilverStripe\Security\Tests;

use SilverStripe\Security\RandomGenerator;
use SilverStripe\Dev\SapphireTest;

/**
 * @author Ingo Schommer
 */
class RandomGeneratorTest extends SapphireTest
{

    public function testGenerateHash()
    {
        $r = new RandomGenerator();
        $this->assertNotNull($r->randomToken());
        $this->assertNotEquals($r->randomToken(), $r->randomToken());
    }

    public function testGenerateHashWithAlgorithm()
    {
        $r = new RandomGenerator();
        $this->assertNotNull($r->randomToken('md5'));
        $this->assertNotEquals($r->randomToken(), $r->randomToken('md5'));
    }
}
