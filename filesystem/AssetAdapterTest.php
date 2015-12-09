<?php

use SilverStripe\Filesystem\Flysystem\ProtectedAssetAdapter;
use SilverStripe\Filesystem\Flysystem\PublicAssetAdapter;

class AssetAdapterTest extends SapphireTest {

    protected $rootDir = null;

    protected $originalServer = null;

    public function setUp() {
        parent::setUp();

        $this->rootDir = ASSETS_PATH . '/AssetAdapterTest';
        Filesystem::makeFolder($this->rootDir);
        $this->originalServer = $_SERVER;
    }

    public function tearDown() {
        if($this->rootDir) {
            Filesystem::removeFolder($this->rootDir);
            $this->rootDir = null;
        }
        if($this->originalServer) {
            $_SERVER = $this->originalServer;
            $this->originalServer = null;
        }
        parent::tearDown();
    }

    public function testPublicAdapter() {
        $_SERVER['SERVER_SOFTWARE'] = 'Apache/2.2.22 (Win64) PHP/5.3.13';
        $adapter = new PublicAssetAdapter($this->rootDir);
        $this->assertFileExists($this->rootDir . '/.htaccess');
        $this->assertFileNotExists($this->rootDir . '/web.config');

        $htaccess = $adapter->read('.htaccess');
        $content = $htaccess['contents'];
        // Allowed extensions set
        $this->assertContains('RewriteCond %{REQUEST_URI} !.(?i:', $content);
        foreach(File::config()->allowed_extensions as $extension) {
            $this->assertRegExp('/\b'.preg_quote($extension).'\b/', $content);
        }

        // Rewrite rules
        $this->assertContains('RewriteRule .* ../framework/main.php?url=%1 [QSA]', $content);
        $this->assertContains('RewriteRule error[^\\/]*.html$ - [L]', $content);

        // Test flush restores invalid content
        \file_put_contents($this->rootDir . '/.htaccess', '# broken content');
        $adapter->flush();
        $htaccess2 = $adapter->read('.htaccess');
        $this->assertEquals($content, $htaccess2['contents']);

        // Test URL
        $this->assertEquals('/assets/AssetAdapterTest/file.jpg', $adapter->getPublicUrl('file.jpg'));
    }

    public function testProtectedAdapter() {
        $_SERVER['SERVER_SOFTWARE'] = 'Apache/2.2.22 (Win64) PHP/5.3.13';
        $adapter = new ProtectedAssetAdapter($this->rootDir . '/.protected');
        $this->assertFileExists($this->rootDir . '/.protected/.htaccess');
        $this->assertFileNotExists($this->rootDir . '/.protected/web.config');

        // Test url
        $this->assertEquals('/assets/file.jpg', $adapter->getProtectedUrl('file.jpg'));
    }
}