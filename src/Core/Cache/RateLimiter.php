<?php

namespace SilverStripe\Core\Cache;

use Psr\SimpleCache\CacheInterface;
use SilverStripe\Core\Injector\Injectable;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\ORM\FieldType\DBDatetime;

class RateLimiter
{
    use Injectable;

    /**
     * @var CacheInterface
     */
    private $cache;

    /**
     * @var string
     */
    private $identifier;

    /**
     * @var int Maximum number of attempts allowed
     */
    private $maxAttempts;

    /**
     * @var int How long the rate limit lasts for in minutes
     */
    private $decay;

    /**
     * RateLimiter constructor.
     * @param string $identifier
     * @param int $maxAttempts
     * @param int $decay
     */
    public function __construct(string $identifier, int $maxAttempts, int $decay): void
    {
        $this->setIdentifier($identifier);
        $this->setMaxAttempts($maxAttempts);
        $this->setDecay($decay);
    }

    /**
     * @return CacheInterface
     */
    public function getCache(): Symfony\Component\Cache\Simple\FilesystemCache
    {
        if (!$this->cache) {
            $this->setCache(Injector::inst()->create(CacheInterface::class . '.RateLimiter'));
        }
        return $this->cache;
    }

    /**
     * @param CacheInterface $cache
     *
     * @return $this
     */
    public function setCache(Symfony\Component\Cache\Simple\FilesystemCache $cache): SilverStripe\Core\Cache\RateLimiter
    {
        $this->cache = $cache;
        return $this;
    }

    /**
     * @return string
     */
    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    /**
     * @param string $identifier
     * @return $this
     */
    public function setIdentifier(string $identifier): SilverStripe\Core\Cache\RateLimiter
    {
        $this->identifier = $identifier;
        return $this;
    }

    /**
     * @return int
     */
    public function getMaxAttempts(): int
    {
        return $this->maxAttempts;
    }

    /**
     * @param int $maxAttempts
     * @return $this
     */
    public function setMaxAttempts(int $maxAttempts): SilverStripe\Core\Cache\RateLimiter
    {
        $this->maxAttempts = $maxAttempts;
        return $this;
    }

    /**
     * @return int
     */
    public function getDecay(): int
    {
        return $this->decay;
    }

    /**
     * @param int $decay
     * @return $this
     */
    public function setDecay(int $decay): SilverStripe\Core\Cache\RateLimiter
    {
        $this->decay = $decay;
        return $this;
    }

    /**
     * @return int
     */
    public function getNumAttempts(): int
    {
        return $this->getCache()->get($this->getIdentifier(), 0);
    }

    /**
     * @return int
     */
    public function getNumAttemptsRemaining(): int
    {
        return max(0, $this->getMaxAttempts() - $this->getNumAttempts());
    }

    /**
     * @return int
     */
    public function getTimeToReset(): int
    {
        if ($expiry = $this->getCache()->get($this->getIdentifier() . '-timer')) {
            return $expiry - DBDatetime::now()->getTimestamp();
        }
        return  0;
    }

    /**
     * @return $this
     */
    public function clearAttempts(): SilverStripe\Core\Cache\RateLimiter
    {
        $this->getCache()->delete($this->getIdentifier());
        return $this;
    }

    /**
     * Store a hit in the rate limit cache
     *
     * @return $this
     */
    public function hit(): SilverStripe\Core\Cache\RateLimiter
    {
        if (!$this->getCache()->has($this->getIdentifier())) {
            $ttl = $this->getDecay() * 60;
            $expiry = DBDatetime::now()->getTimestamp() + $ttl;
            $this->getCache()->set($this->getIdentifier() . '-timer', $expiry, $ttl);
        } else {
            $expiry = $this->getCache()->get($this->getIdentifier() . '-timer');
            $ttl = $expiry - DBDatetime::now()->getTimestamp();
        }
        $this->getCache()->set($this->getIdentifier(), $this->getNumAttempts() + 1, $ttl);
        return $this;
    }

    /**
     * @return bool
     */
    public function canAccess(): bool
    {
        if ($this->getNumAttempts() >= $this->getMaxAttempts()) {
            // if the timer cache item still exists then they are locked out
            if ($this->getCache()->has($this->getIdentifier() . '-timer')) {
                return false;
            }
            // the timer key has expired so we can clear their attempts and start again
            $this->clearAttempts();
        }
        return true;
    }
}
