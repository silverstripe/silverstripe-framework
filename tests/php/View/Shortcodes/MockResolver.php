<?php

namespace SilverStripe\View\Tests\Shortcodes;

use Embed\Http\DispatcherInterface;
use Embed\Http\ImageResponse;
use Embed\Http\Response;
use Embed\Http\Url;
use InvalidArgumentException;

class MockResolver implements DispatcherInterface
{
    protected $url = null;

    protected $expectedContent = null;

    /**
     * Constructor. Sets the url.
     *
     * @param string $url The url value
     * @param array $config The resolver configuration
     */
    public function __construct($url, array $config)
    {
        $this->url = $url;
        if (empty($config['expectedContent'])) {
            throw new InvalidArgumentException("Mock resolvers need expectedContent");
        }
        $this->expectedContent = $config['expectedContent'];
    }

    /**
     * Dispatch an url.
     *
     * @param Url $url
     *
     * @return Response
     */
    public function dispatch(Url $url)
    {
        return new Response(
            $url,
            $url,
            200,
            'application/json',
            $this->expectedContent,
            [],
            []
        );
    }

    /**
     * Resolve multiple image urls at once.
     *
     * @param Url[] $urls
     *
     * @return ImageResponse[]
     */
    public function dispatchImages(array $urls)
    {
        return [];
    }
}
