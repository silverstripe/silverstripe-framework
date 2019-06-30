<?php declare(strict_types = 1);

namespace SilverStripe\View\Tests\Embed;

use Embed\Adapters\Adapter;
use Embed\Http\DispatcherInterface;
use Embed\Http\Response;
use Embed\Http\Url;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\View\Embed\EmbedResource;

class EmbedResourceTest extends SapphireTest
{
    public function testGetEmbed()
    {
        $dispatcherMock = $this->createMock(DispatcherInterface::class);
        $dispatcherMock->expects($this->atLeastOnce())->method('dispatch')->willReturn($this->mockResponse());

        /** @var EmbedResource $embed */
        $embed = Injector::inst()->create(EmbedResource::class, 'https://www.youtube.com/watch?v=iRXJXaLV0n4');
        $this->assertEmpty($embed->getOptions());
        $this->assertEmpty($embed->getDispatcher());

        $embed->setOptions(['foo' => 'bar']);
        $embed->setDispatcher($dispatcherMock);

        $adapter = $embed->getEmbed();
        $this->assertInstanceOf(Adapter::class, $adapter);
        $this->assertSame('Try to stay SERIOUS -The most popular CAT videos', $adapter->getTitle());
    }

    /**
     * Generate a mock Response object suitable for Embed
     *
     * @return Response
     */
    private function mockResponse()
    {
        $url = Url::create('https://www.youtube.com/watch?v=iRXJXaLV0n4');
        return new Response(
            $url,
            $url,
            200,
            'application/json',
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
                'html' => '<iframe width="480" height="270" src="https://www.youtube.com/embed/iRXJXaLV0n4?feature=oembed" frameborder="0" allow="autoplay; encrypted-media" allowfullscreen></iframe>',
                'provider_url' => 'https://www.youtube.com/',
                'thumbnail_height' => 360,
                'thumbnail_url' => 'https://i.ytimg.com/vi/iRXJXaLV0n4/hqdefault.jpg',
            ]),
            []
        );
    }
}
