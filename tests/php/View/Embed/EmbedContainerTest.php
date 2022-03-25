<?php

namespace SilverStripe\View\Tests\Embed;

use Embed\Extractor;
use Embed\Http\Crawler;
use SilverStripe\View\Embed\EmbedContainer;
use SilverStripe\AssetAdmin\Controller\AssetAdmin;

class EmbedContainerTest extends EmbedUnitTest
{
    public function testGetDimensions()
    {
        $container = $this->getEmbedContainer();
        $this->assertSame(480, $container->getWidth());
        $this->assertSame(270, $container->getHeight());
        $container = $this->getFallbackEmbedContainer();
        $this->assertSame(100, $container->getWidth());
        $this->assertSame(100, $container->getHeight());
    }

    public function testGetPreviewURL()
    {
        $container = $this->getEmbedContainer();
        $this->assertSame('https://www.youtube.com/watch?v=iRXJXaLV0n4', $container->getPreviewURL());
        $container = $this->getFallbackEmbedContainer();
        if (class_exists(AssetAdmin::class)) {
            $this->assertStringContainsString('client/dist/images/icon_file.png', $container->getPreviewURL());
        }
    }

    public function testGetName()
    {
        $container = $this->getEmbedContainer();
        $this->assertSame('Try to stay SERIOUS -The most popular CAT videos', $container->getName());
    }

    public function testGetType()
    {
        $container = $this->getEmbedContainer();
        $this->assertSame('rich', $container->getType());
        $container = $this->getEmbedContainer(
            <<<EOT
            <video width="320" height="240" controls>
                <source src="movie.ogg" type="video/ogg">
                Your browser does not support the video tag.
            </video> 
            EOT
        );
        $this->assertSame('video', $container->getType());
        $container = $this->getEmbedContainer(
            <<<EOT
            <audio controls>
                <source src="horse.ogg" type="audio/ogg">
                Your browser does not support the audio element.
            </audio> 
            EOT
        );
        $this->assertSame('audio', $container->getType());
        $container = $this->getEmbedContainer(
            <<<EOT
            <a data-flickr-embed="true" href="https://www.flickr.com/photos/philocycler/32119532132/"><img src="https://live.staticflickr.com/759/32119532132_50c3f7933f_b.jpg" width="1024" height="742" alt="bird"></a>
            EOT
        );
        $this->assertSame('photo', $container->getType());
        $container = $this->getEmbedContainer('<p>Lorem ipsum</p>');
        $this->assertSame('link', $container->getType());
    }

    public function testValidate()
    {
        $container = $this->getEmbedContainer();
        $this->assertTrue($container->validate());
        $container = $this->getFallbackEmbedContainer();
        $this->assertFalse($container->validate());
    }

    public function testOptions()
    {
        $options = ['foo' => 'bar'];
        $container = $this->getEmbedContainer();
        $this->assertSame([], $container->getOptions());
        $container->setOptions($options);
        $this->assertSame($options, $container->getOptions());
    }

    public function testGetExtractor()
    {
        $container = $this->getEmbedContainer();
        $extractor = $container->getExtractor();
        $this->assertTrue($extractor instanceof Extractor);
        $this->assertSame('Try to stay SERIOUS -The most popular CAT videos', $extractor->title);
    }

    private function getFallbackEmbedContainer()
    {
        return $this->createEmbedContainer('', '', '', '');
    }

    private function getEmbedContainer(string $htmlOverride = '')
    {
        $html = $htmlOverride ?: implode('', [
            '<iframe width="480" height="270" src="https://www.youtube.com/embed/iRXJXaLV0n4?feature=oembed" ',
            'frameborder="0" allow="autoplay; encrypted-media" allowfullscreen></iframe>'
        ]);
        $url = 'https://www.youtube.com/watch?v=iRXJXaLV0n4';
        return $this->createEmbedContainer(
            $url,
            $url,
            implode('', [
                '<html><link rel="alternate" type="application/json+oembed" ',
                'href="https://www.youtube.com/oembed?format=json&amp;',
                'url=https%3A%2F%2Fwww.youtube.com%2Fwatch%3Fv%3DiRXJXaLV0n4" ',
                'title="Try to stay SERIOUS -The most popular CAT videos"></html>'
            ]),
            json_encode([
                'author_url' => 'https://www.youtube.com/channel/UCR2KG2dK1tAkwZZjm7rAiSg',
                'thumbnail_width' => 480,
                'title' => 'Try to stay SERIOUS -The most popular CAT videos',
                'width' => 480,
                'provider_name' => 'YouTube',
                'author_name' => 'Tiger Funnies',
                'height' => 270,
                'version' => '1.0',
                'type' => 'video',
                // phpcs:ignore
                'html' => $html,
                'provider_url' => 'https://www.youtube.com/',
                'thumbnail_height' => 360,
                'thumbnail_url' => 'https://i.ytimg.com/vi/iRXJXaLV0n4/hqdefault.jpg',
            ])
        );
    }
}
