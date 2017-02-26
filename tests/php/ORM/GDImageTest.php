<?php

namespace SilverStripe\ORM\Tests;

require_once __DIR__  . "/ImageTest.php";

use SilverStripe\Core\Config\Config;
use Psr\SimpleCache\CacheInterface;
use SilverStripe\Core\Injector\Injector;

class GDImageTest extends ImageTest
{

    public function setUp()
    {
        parent::setUp();

        if (!extension_loaded("gd")) {
            $this->markTestSkipped("The GD extension is required");
            return;
        }

        /**
 * @skipUpgrade
*/
        Config::inst()->update(
            'SilverStripe\\Core\\Injector\\Injector',
            'Image_Backend',
            'SilverStripe\\Assets\\GDBackend'
        );
    }

    public function tearDown()
    {
        $cache = Injector::inst()->get(CacheInterface::class . '.GDBackend_Manipulations');
        $cache->clear();

        parent::tearDown();
    }
}
