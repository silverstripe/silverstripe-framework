<?php

namespace SilverStripe\Core\Tests\Manifest;

use SebastianBergmann\Version;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Manifest\VersionProvider;
use SilverStripe\Dev\SapphireTest;

class VersionProviderTest extends SapphireTest
{

    /**
     * @var VersionProvider
     */
    protected $provider;

    public function getMockProvider($composerLockPath = '')
    {
        if ($composerLockPath == '') {
            // composer.lock file without silverstripe/recipe-core or silverstripe/recipe-cms
            $composerLockPath = __DIR__ . '/fixtures/VersionProviderTest/composer.no-recipe.testlock';
        }
        /** @var VersionProvider $provider */
        $provider = $this->getMockBuilder(VersionProvider::class)
            ->setMethods(['getComposerLockPath'])
            ->getMock();
        $provider->method('getComposerLockPath')->willReturn($composerLockPath);
        return $provider;
    }

    public function testGetModules()
    {
        Config::modify()->set(VersionProvider::class, 'modules', [
            'silverstripe/mypackage' => 'My Package',
            'silverstripe/somepackage' => 'Some Package',
            'silverstripe/another' => 'Another',
            'cwp/cwp-something' => 'CWP something',
        ]);
        $result = $this->getMockProvider()->getModules();
        $this->assertArrayHasKey('silverstripe/mypackage', $result);
        $this->assertArrayHasKey('silverstripe/somepackage', $result);
        $this->assertArrayHasKey('silverstripe/another', $result);
        $this->assertArrayHasKey('cwp/cwp-something', $result);
    }

    public function testGetModulesEmpty()
    {
        Config::modify()->set(VersionProvider::class, 'modules', []);
        $this->assertEquals(
            ['silverstripe/framework' => 'Framework'],
            $this->getMockProvider()->getModules()
        );
    }

    public function testGetModulesNone()
    {
        Config::modify()->remove(VersionProvider::class, 'modules');
        $this->assertEquals(
            ['silverstripe/framework' => 'Framework'],
            $this->getMockProvider()->getModules()
        );
    }

    public function testGetModuleVersionFromComposer()
    {
        Config::modify()->set(VersionProvider::class, 'modules', [
            'silverstripe/siteconfig' => 'SiteConfig',
            'silverstripe/framework' => 'Framework',
        ]);

        $result = $this->getMockProvider()->getModules(['silverstripe/framework']);
        $this->assertArrayHasKey('silverstripe/framework', $result);
        $this->assertNotEmpty($result['silverstripe/framework']);
    }

    public function testGetVersion()
    {
        Config::modify()->set(VersionProvider::class, 'modules', [
            'silverstripe/siteconfig' => 'SiteConfig',
            'silverstripe/framework' => 'Framework'
        ]);
        $result = $this->getMockProvider()->getVersion();
        $this->assertNotContains('SiteConfig: ', $result);
        $this->assertContains('Framework: ', $result);
        $this->assertNotContains(', ', $result);
    }

    public function testGetVersionNoRecipe()
    {
        // composer.lock file without silverstripe/recipe-core or silverstripe/recipe-cms
        $provider = $this->getMockProvider(__DIR__ . '/fixtures/VersionProviderTest/composer.no-recipe.testlock');

        Config::modify()->set(VersionProvider::class, 'modules', []);
        $result = $provider->getVersion();
        $this->assertContains('Framework: 1.2.3', $result);

        Config::modify()->set(VersionProvider::class, 'modules', [
            'silverstripe/framework' => 'Framework',
            'silverstripe/recipe-core' => 'Core Recipe',
            'silverstripe/cms' => 'CMS',
            'silverstripe/recipe-cms' => 'CMS Recipe',
        ]);
        $result = $provider->getVersion();
        $this->assertNotContains('Framework: 1.2.3', $result);
        $this->assertContains('CMS: 4.5.6', $result);
        $this->assertNotContains('Core Recipe: 7.7.7', $result);
        $this->assertNotContains('CMS Recipe: 8.8.8', $result);
    }

    public function testGetVersionRecipeCore()
    {
        // composer.lock file with silverstripe/recipe-core but not silverstripe/recipe-cms
        $provider = $this->getMockProvider(__DIR__ . '/fixtures/VersionProviderTest/composer.recipe-core.testlock');
        Config::modify()->set(VersionProvider::class, 'modules', [
            'silverstripe/framework' => 'Framework',
            'silverstripe/recipe-core' => 'Core Recipe',
            'silverstripe/cms' => 'CMS',
            'silverstripe/recipe-cms' => 'CMS Recipe',
        ]);
        $result = $provider->getVersion();
        $this->assertNotContains('Framework: 1.2.3', $result);
        $this->assertNotContains('Core Recipe: 7.7.7', $result);
        $this->assertContains('CMS: 4.5.6', $result);
        $this->assertNotContains('CMS Recipe: 8.8.8', $result);
    }

    public function testGetVersionRecipeCmsCore()
    {
        // composer.lock file with silverstripe/recipe-core and silverstripe/recipe-cms
        $path = __DIR__ . '/fixtures/VersionProviderTest/composer.recipe-cms-core-and-cwpcore.testlock';
        $provider = $this->getMockProvider($path);

        Config::modify()->set(VersionProvider::class, 'modules', [
            'silverstripe/framework' => 'Framework',
            'silverstripe/recipe-core' => 'Core Recipe',
            'silverstripe/cms' => 'CMS',
            'silverstripe/recipe-cms' => 'CMS Recipe',
        ]);
        $result = $provider->getVersion();

        $this->assertNotContains('Framework: 1.2.3', $result);
        $this->assertNotContains('CMS: 4.5.6', $result);
        $this->assertNotContains('Core Recipe: 7.7.7', $result);
        $this->assertContains('CMS Recipe: 8.8.8', $result);
        $this->assertNotContains('CWP: 9.9.9', $result);

        Config::modify()->set(VersionProvider::class, 'modules', [
            'silverstripe/framework' => 'Framework',
            'silverstripe/recipe-core' => 'Core Recipe',
            'silverstripe/cms' => 'CMS',
            'silverstripe/recipe-cms' => 'CMS Recipe',
            'cwp/cwp-core' => 'CWP',
        ]);
        $result = $provider->getVersion();
        $this->assertNotContains('Framework: 1.2.3', $result);
        $this->assertNotContains('CMS: 4.5.6', $result);
        $this->assertNotContains('Core Recipe: 7.7.7', $result);
        $this->assertContains('CMS Recipe:', $result);
        $this->assertContains('CWP: 9.9.9', $result);
    }

    public function testGetModulesFromComposerLock()
    {
        $mock = $this->getMockBuilder(VersionProvider::class)
            ->setMethods(['getComposerLock'])
            ->getMock();

        $mock->expects($this->exactly(1))
            ->method('getComposerLock')
            ->will($this->returnValue([
                'packages' => [
                    [
                        'name' => 'silverstripe/somepackage',
                        'version' => '1.2.3'
                    ],
                    [
                        'name' => 'silverstripe/another',
                        'version' => '2.3.4'
                    ]
                ]
            ]));

        Config::modify()->set(VersionProvider::class, 'modules', [
            'silverstripe/somepackage' => 'Some Package'
        ]);

        $result = $mock->getVersion();
        $this->assertContains('Some Package: 1.2.3', $result);
    }
}
