<?php

namespace SilverStripe\Forms\Tests\HTMLEditor;

use Exception;
use PHPUnit_Framework_MockObject_MockObject;
use SilverStripe\Control\Director;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Convert;
use SilverStripe\Core\Manifest\ModuleLoader;
use SilverStripe\Core\Manifest\ModuleManifest;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Forms\HTMLEditor\HTMLEditorConfig;
use SilverStripe\Forms\HTMLEditor\HTMLEditorField;
use SilverStripe\Forms\HTMLEditor\TinyMCEConfig;

class HTMLEditorConfigTest extends SapphireTest
{

    protected function setUp()
    {
        parent::setUp();

        TinyMCEConfig::config()->set('base_dir', 'test/thirdparty/tinymce');
    }

    public function testEnablePluginsByString()
    {
        $c = new TinyMCEConfig();
        $c->enablePlugins('plugin1');
        $this->assertContains('plugin1', array_keys($c->getPlugins()));
    }

    public function testEnablePluginsByArray()
    {
        $c = new TinyMCEConfig();
        $c->enablePlugins(array('plugin1', 'plugin2'));
        $this->assertContains('plugin1', array_keys($c->getPlugins()));
        $this->assertContains('plugin2', array_keys($c->getPlugins()));
    }

    public function testEnablePluginsByMultipleStringParameters()
    {
        $c = new TinyMCEConfig();
        $c->enablePlugins('plugin1', 'plugin2');
        $this->assertContains('plugin1', array_keys($c->getPlugins()));
        $this->assertContains('plugin2', array_keys($c->getPlugins()));
    }

    public function testEnablePluginsByArrayWithPaths()
    {
        Config::modify()->set(Director::class, 'alternate_base_url', 'http://mysite.com/subdir');
        $c = new TinyMCEConfig();
        $c->setTheme('modern');
        $c->setOption('language', 'es');
        $c->disablePlugins('table', 'emoticons', 'paste', 'code', 'link', 'importcss');
        $c->enablePlugins(
            array(
                'plugin1' => 'mypath/plugin1.js',
                'plugin2' => '/anotherbase/mypath/plugin2.js',
                'plugin3' => 'https://www.google.com/plugin.js',
                'plugin4' => null,
                'plugin5' => null,
            )
        );
        $attributes = $c->getAttributes();
        $config = Convert::json2array($attributes['data-config']);
        $plugins = $config['external_plugins'];
        $this->assertNotEmpty($plugins);

        // Plugin specified via relative url
        $this->assertContains('plugin1', array_keys($plugins));
        $this->assertEquals(
            'http://mysite.com/subdir/mypath/plugin1.js',
            $plugins['plugin1']
        );

        // Plugin specified via root-relative url
        $this->assertContains('plugin2', array_keys($plugins));
        $this->assertEquals(
            'http://mysite.com/anotherbase/mypath/plugin2.js',
            $plugins['plugin2']
        );

        // Plugin specified with absolute url
        $this->assertContains('plugin3', array_keys($plugins));
        $this->assertEquals(
            'https://www.google.com/plugin.js',
            $plugins['plugin3']
        );

        // Plugin specified with standard location
        $this->assertContains('plugin4', array_keys($plugins));
        $this->assertEquals(
            'http://mysite.com/subdir/test/thirdparty/tinymce/plugins/plugin4/plugin.min.js',
            $plugins['plugin4']
        );

        // Check that internal plugins are extractable separately
        $this->assertEquals(['plugin4', 'plugin5'], $c->getInternalPlugins());
    }

    public function testPluginCompression()
    {
        $module = ModuleLoader::inst()->getManifest()->getModule('silverstripe/admin');
        if (!$module) {
            $this->markTestSkipped('No silverstripe/admin module loaded');
        }
        TinyMCEConfig::config()->remove('base_dir');
        Config::modify()->set(Director::class, 'alternate_base_url', 'http://mysite.com/subdir');
        $c = new TinyMCEConfig();
        $c->setTheme('modern');
        $c->setOption('language', 'es');
        $c->disablePlugins('table', 'emoticons', 'paste', 'code', 'link', 'importcss');
        $c->enablePlugins(
            array(
                'plugin1' => 'mypath/plugin1.js',
                'plugin2' => '/anotherbase/mypath/plugin2.js',
                'plugin3' => 'https://www.google.com/plugin.js',
                'plugin4' => null,
                'plugin5' => null,
            )
        );
        $attributes = $c->getAttributes();
        $config = Convert::json2array($attributes['data-config']);
        $plugins = $config['external_plugins'];
        $this->assertNotEmpty($plugins);

        // Test plugins included via gzip compresser
        HTMLEditorField::config()->update('use_gzip', true);
        $this->assertEquals(
            'silverstripe-admin/thirdparty/tinymce/tiny_mce_gzip.php?js=1&plugins=plugin4,plugin5&themes=modern&languages=es&diskcache=true&src=true',
            $c->getScriptURL()
        );

        // If gzip is disabled only the core plugin is loaded
        HTMLEditorField::config()->remove('use_gzip');
        $this->assertEquals(
            'silverstripe-admin/thirdparty/tinymce/tinymce.min.js',
            $c->getScriptURL()
        );
    }

    public function testDisablePluginsByString()
    {
        $c = new TinyMCEConfig();
        $c->enablePlugins('plugin1');
        $c->disablePlugins('plugin1');
        $this->assertNotContains('plugin1', array_keys($c->getPlugins()));
    }

    public function testDisablePluginsByArray()
    {
        $c = new TinyMCEConfig();
        $c->enablePlugins(array('plugin1', 'plugin2'));
        $c->disablePlugins(array('plugin1', 'plugin2'));
        $this->assertNotContains('plugin1', array_keys($c->getPlugins()));
        $this->assertNotContains('plugin2', array_keys($c->getPlugins()));
    }

    public function testDisablePluginsByMultipleStringParameters()
    {
        $c = new TinyMCEConfig();
        $c->enablePlugins('plugin1', 'plugin2');
        $c->disablePlugins('plugin1', 'plugin2');
        $this->assertNotContains('plugin1', array_keys($c->getPlugins()));
        $this->assertNotContains('plugin2', array_keys($c->getPlugins()));
    }

    public function testDisablePluginsByArrayWithPaths()
    {
        $c = new TinyMCEConfig();
        $c->enablePlugins(array('plugin1' => '/mypath/plugin1', 'plugin2' => '/mypath/plugin2'));
        $c->disablePlugins(array('plugin1', 'plugin2'));
        $plugins = $c->getPlugins();
        $this->assertNotContains('plugin1', array_keys($plugins));
        $this->assertNotContains('plugin2', array_keys($plugins));
    }

    public function testRequireJSIncludesAllConfigs()
    {
        $a = HTMLEditorConfig::get('configA');
        $c = HTMLEditorConfig::get('configB');

        $aAttributes = $a->getAttributes();
        $cAttributes = $c->getAttributes();

        $this->assertNotEmpty($aAttributes['data-config']);
        $this->assertNotEmpty($cAttributes['data-config']);
    }

    public function testExceptionThrownWhenTinyMCEPathCannotBeComputed()
    {
        TinyMCEConfig::config()->remove('base_dir');
        ModuleLoader::inst()->pushManifest(new ModuleManifest(__DIR__));

        try {
            $config = new TinyMCEConfig();
            $this->expectException(Exception::class);
            $this->expectExceptionMessageRegExp('/module is not installed/');
            $config->getScriptURL();
        } finally {
            ModuleLoader::inst()->popManifest();
        }
    }

    public function testExceptionThrownWhenTinyMCEGZipPathDoesntExist()
    {
        HTMLEditorField::config()->set('use_gzip', true);
        /** @var TinyMCEConfig|PHPUnit_Framework_MockObject_MockObject $stub */
        $stub = $this->getMockBuilder(TinyMCEConfig::class)
            ->setMethods(['getTinyMCEPath'])
            ->getMock();
        $stub->method('getTinyMCEPath')
            ->willReturn('fail');

        $this->expectException(Exception::class);
        $this->expectExceptionMessageRegExp('/does not exist/');
        $stub->getScriptURL();
    }
}
