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
     */
    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function start()
    {
        if (!$this->logger) {
            throw new \InvalidArgumentException("No Logger property passed to MonologErrorHandler."
                . "Is your Injector config correct?");
        }

        MonologHandler::register($this->logger);
    }
}
