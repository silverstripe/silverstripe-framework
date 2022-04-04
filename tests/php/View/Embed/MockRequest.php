<?php

namespace SilverStripe\View\Tests\Embed;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\UriInterface;
use Psr\Http\Message\StreamInterface;

class MockRequest implements RequestInterface
{
    private EmbedUnitTest $unitTest;
    private MockUri $mockUri;

    public function __construct(EmbedUnitTest $unitTest, MockUri $mockUri)
    {
        $this->unitTest = $unitTest;
        $this->mockUri = $mockUri;
    }

    public function getRequestTarget()
    {
    }

    public function getMethod()
    {
    }

    public function getUri()
    {
        $this->unitTest->setFirstRequest(false);
        return $this->mockUri;
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

    public function getBody()
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

    public function withoutHeader($name)
    {
        return $this;
    }

    public function withBody(StreamInterface $body)
    {
        return $this;
    }

    public function withProtocolVersion($version)
    {
        return $this;
    }

    public function withRequestTarget($requestTarget)
    {
        return $this;
    }

    public function withMethod($method)
    {
        return $this;
    }

    public function withUri(UriInterface $uri, $preserveHost = false)
    {
        return $this;
    }
}
