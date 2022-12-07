<?php

namespace SilverStripe\Logging;

use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Monolog\ErrorHandler as MonologHandler;

class MonologErrorHandler implements ErrorHandler
{
    /**
     * @var LoggerInterface[]
     */
    private $loggers = [];

    /**
     * Adds a PSR-3 logger to send messages to, to the end of the stack
     *
     * @param LoggerInterface $logger
     * @return $this
     */
    public function pushLogger(LoggerInterface $logger)
    {
        $this->loggers[] = $logger;
        return $this;
    }

    /**
     * Returns the stack of PSR-3 loggers
     *
     * @return LoggerInterface[]
     */
    public function getLoggers()
    {
        return $this->loggers;
    }

    /**
     * Set the PSR-3 loggers (overwrites any previously configured values)
     *
     * @param LoggerInterface[] $loggers
     * @return $this
     */
    public function setLoggers(array $loggers)
    {
        $this->loggers = $loggers;
        return $this;
    }

    /**
     * {@inheritDoc}
     *
     * @throws InvalidArgumentException
     */
    public function start()
    {
        $loggers = $this->getLoggers();
        if (empty($loggers)) {
            throw new InvalidArgumentException(
                "No Logger properties passed to MonologErrorHandler. Is your Injector config correct?"
            );
        }

        foreach ($loggers as $logger) {
            MonologHandler::register($logger);
        }
    }
}
