<?php

namespace SilverStripe\View\Tests\Embed;

use SilverStripe\Core\Injector\Injector;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\View\Embed\EmbedContainer;
use SilverStripe\View\Embed\Embeddable;
use Embed\Http\Crawler;
use Embed\Embed;

/**
 * Special unit test class to faciliate mock embed/embed requests
 */
class EmbedUnitTest extends SapphireTest
{
    private bool $firstRequest = true;

    public function getFirstRequest(): bool
    {
        return $this->firstRequest;
    }

    public function setFirstRequest(bool $b): void
    {
        $this->firstRequest = $b;
    }

    protected function createEmbedContainer(
        string $urlA,
        string $urlB,
        string $firstResponse,
        string $secondResponse
    ): EmbedContainer {
        $this->registerCrawlerService($urlA, $urlB, $firstResponse, $secondResponse);
        $embedContainer = EmbedContainer::create($urlA);
        return $embedContainer;
    }

    private function registerCrawlerService(
        string $urlA,
        string $urlB,
        string $firstResponse,
        string $secondResponse
    ): void {
        $mockUriA = new MockUri($urlA);
        $mockUriB = new MockUri($urlB);
        $crawlerMock = $this->createMock(Crawler::class);
        $crawlerMock->method('getResponseUri')->willReturn($mockUriA);
        $crawlerMock->method('createUri')->willReturn($mockUriB);
        $crawlerMock->method('sendRequest')->willReturn(new MockResponse($this, $firstResponse, $secondResponse));
        $crawlerMock->method('createRequest')->willReturn(new MockRequest($this, $mockUriA));
        Injector::inst()->registerService($crawlerMock, Crawler::class);
        // replace the existing registered Embed singleton with a new singleton that is
        // created using $crawlerMock as the the __constructor argument - see oembed.yml
        $embed = Injector::inst()->create(Embed::class, $crawlerMock);
        Injector::inst()->registerService($embed, Embed::class);
    }

    /**
     * This is to prevent the following warning:
     * No tests found in class "SilverStripe\View\Tests\Embed\EmbedUnitTest".
     */
    public function testPass()
    {
        $this->assertTrue(true);
    }
}
