<?php

namespace SilverStripe\View\Tests\Embed;

use Psr\Http\Message\UriInterface;
use Stringable;

class MockUri implements UriInterface, Stringable
{
    private string $scheme;
    private string $host;
    private string $path;
    private string $query;

    public function __construct(string $url)
    {
        $p = parse_url($url ?? '');
        $this->scheme = $p['scheme'] ?? '';
        $this->host = $p['host'] ?? '';
        $this->path = $p['path'] ?? '';
        $this->query = $p['query'] ?? '';
    }

    public function getScheme()
    {
        return $this->scheme;
    }

    public function getHost()
    {
        return $this->host;
    }

    public function getPath()
    {
        return $this->path;
    }

    public function getQuery()
    {
        return $this->query;
    }

    public function getPort()
    {
    }

    public function getAuthority()
    {
    }

    public function getUserInfo()
    {
    }

    public function getFragment()
    {
    }

    public function withPath($path)
    {
        return $this;
    }

    public function withScheme($scheme)
    {
        return $this;
    }

    public function withUserInfo($user, $password = null)
    {
        return $this;
    }

    public function withHost($host)
    {
        return $this;
    }

    public function withPort($port)
    {
        return $this;
    }

    public function withQuery($query)
    {
        return $this;
    }

    public function withFragment($fragment)
    {
        return $this;
    }

    public function __toString(): string
    {
        $query = $this->getQuery();
        return sprintf(
            '%s://%s%s%s',
            $this->getScheme(),
            $this->getHost(),
            '/' . ltrim($this->getPath() ?? '', '/'),
            $query ? "?$query" : ''
        );
    }
}
