<?php

namespace SilverStripe\View\Tests\Embed;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

class MockResponse implements ResponseInterface
{
    private EmbedUnitTest $unitTest;
    private string $firstReponse;
    private string $secondResponse;

    public function __construct(EmbedUnitTest $unitTest, string $firstResponse, string $secondResponse)
    {
        $this->unitTest = $unitTest;
        $this->firstResponse = $firstResponse;
        $this->secondResponse = $secondResponse;
    }

    public function getStatusCode()
    {
        return 200;
    }

    public function getBody()
    {
        // first request is to the video HTML to get to find the oembed link
        // second request is to the oembed endpoint to fetch JSON
        if ($this->unitTest->getFirstRequest()) {
            return $this->firstResponse;
        } else {
            return $this->secondResponse;
        }
    }

    public function getReasonPhrase()
    {
    }

    public function getProtocolVersion()
    {
    }

    public function getHeaders()
    {
    }

    public function getHeader($name)
    {
    }

    public function getHeaderLine($name)
    {
    }

    public function hasHeader($name)
    {
    }

    public function withHeader($name, $value)
    {
        return $this;
    }

    public function withAddedHeader($name, $value)
    {
        return $this;
    }

    public function withBody(StreamInterface $body)
    {
        return $this;
    }

    public function withoutHeader($name)
    {
        return $this;
    }

    public function withProtocolVersion($version)
    {
        return $this;
    }

    public function withStatus($code, $reasonPhrase = '')
    {
        return $this;
    }
}
