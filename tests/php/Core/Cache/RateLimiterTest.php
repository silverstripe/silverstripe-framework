<?php

namespace SilverStripe\Core\Tests\Cache;

use Psr\SimpleCache\CacheInterface;
use SilverStripe\Core\Cache\RateLimiter;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\ORM\FieldType\DBDatetime;
use Symfony\Component\Cache\Simple\ArrayCache;

class RateLimiterTest extends SapphireTest
{

    protected function setUp()
    {
        parent::setUp();
        DBDatetime::set_mock_now('2017-09-27 00:00:00');
    }

    public function testConstruct()
    {
        $cache = new ArrayCache();
        $rateLimiter = new RateLimiter(
            'test',
            5,
            1
        );
        $rateLimiter->setCache($cache);
        $this->assertEquals('test', $rateLimiter->getIdentifier());
        $this->assertEquals(5, $rateLimiter->getMaxAttempts());
        $this->assertEquals(1, $rateLimiter->getDecay());
    }

    public function testGetNumberOfAttempts()
    {
        $cache = new ArrayCache();
        $rateLimiter = new RateLimiter(
            'test',
            5,
            1
        );
        $rateLimiter->setCache($cache);
        for ($i = 0; $i < 7; ++$i) {
            $this->assertEquals($i, $rateLimiter->getNumAttempts());
            $rateLimiter->hit();
        }
    }

    public function testGetNumAttemptsRemaining()
    {
        $cache = new ArrayCache();
        $rateLimiter = new RateLimiter(
            'test',
            1,
            1
        );
        $rateLimiter->setCache($cache);
        $this->assertEquals(1, $rateLimiter->getNumAttemptsRemaining());
        $rateLimiter->hit();
        $this->assertEquals(0, $rateLimiter->getNumAttemptsRemaining());
        $rateLimiter->hit();
        $this->assertEquals(0, $rateLimiter->getNumAttemptsRemaining());
    }

    public function testGetTimeToReset()
    {
        $cache = new ArrayCache();
        $rateLimiter = new RateLimiter(
            'test',
            1,
            1
        );
        $rateLimiter->setCache($cache);
        $this->assertEquals(0, $rateLimiter->getTimeToReset());
        $rateLimiter->hit();
        $this->assertEquals(60, $rateLimiter->getTimeToReset());
        DBDatetime::set_mock_now(DBDatetime::now()->getTimestamp() + 30);
        $this->assertEquals(30, $rateLimiter->getTimeToReset());
    }

    public function testClearAttempts()
    {
        $cache = new ArrayCache();
        $rateLimiter = new RateLimiter(
            'test',
            1,
            1
        );
        $rateLimiter->setCache($cache);
        for ($i = 0; $i < 5; ++$i) {
            $rateLimiter->hit();
        }
        $this->assertEquals(5, $rateLimiter->getNumAttempts());
        $rateLimiter->clearAttempts();
        $this->assertEquals(0, $rateLimiter->getNumAttempts());
    }

    public function testHit()
    {
        $cache = new ArrayCache();
        $rateLimiter = new RateLimiter(
            'test',
            1,
            1
        );
        $rateLimiter->setCache($cache);
        $this->assertFalse($cache->has('test'));
        $this->assertFalse($cache->has('test-timer'));
        $rateLimiter->hit();
        $this->assertTrue($cache->has('test'));
        $this->assertTrue($cache->has('test-timer'));
    }

    public function testCanAccess()
    {
        $cache = new ArrayCache();
        $rateLimiter = new RateLimiter(
            'test',
            1,
            1
        );
        $rateLimiter->setCache($cache);
        $this->assertTrue($rateLimiter->canAccess());
        $rateLimiter->hit();
        $this->assertFalse($rateLimiter->canAccess());
    }
}
