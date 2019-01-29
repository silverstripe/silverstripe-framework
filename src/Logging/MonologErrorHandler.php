<?php

namespace SilverStripe\Logging;

use Psr\Log\LoggerInterface;
use Monolog\ErrorHandler as MonologHandler;

class MonologErrorHandler implements ErrorHandler
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * Set the PSR-3 logger to send errors & exceptions to
     *
     * @param LoggerInterface $logger
     * @return $this
     */
    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
        return $this;
    }

    /**
     * Get the PSR-3 logger to send errors & exceptions to
     *
     * @return LoggerInterface
     */
    public function getLogger()
    {
        return $this->logger;
    }

    public function start()
    {
        if (!$this->getLogger()) {
            throw new \InvalidArgumentException("No Logger property passed to MonologErrorHandler."
                . "Is your Injector config correct?");
        }

        MonologHandler::register($this->getLogger());
    }
}
