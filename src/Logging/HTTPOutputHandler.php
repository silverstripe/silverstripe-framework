<?php

namespace SilverStripe\Logging;

use Monolog\Formatter\FormatterInterface;
use Monolog\Handler\AbstractProcessingHandler;
use Monolog\LogRecord;
use SilverStripe\Control\Controller;
use SilverStripe\Control\Director;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\Dev\Deprecation;

/**
 * Output the error to the browser, with the given HTTP status code.
 * We recommend that you use a formatter that generates HTML with this.
 */
class HTTPOutputHandler extends AbstractProcessingHandler
{

    /**
     * @var string
     */
    private $contentType = 'text/html';

    /**
     * @var int
     */
    private $statusCode = 500;

    /**
     * @var FormatterInterface
     */
    private $cliFormatter = null;

    /**
     * Get the mime type to use when displaying this error.
     *
     * @return string
     */
    public function getContentType()
    {
        return $this->contentType;
    }

    /**
     * Set the mime type to use when displaying this error.
     * Default text/html
     *
     * @param string $contentType
     * @return HTTPOutputHandler Return $this to allow chainable calls
     */
    public function setContentType($contentType)
    {
        $this->contentType = $contentType;
        return $this;
    }

    /**
     * Get the HTTP status code to use when displaying this error.
     *
     * @return int
     */
    public function getStatusCode()
    {
        return $this->statusCode;
    }

    /**
     * Set the HTTP status code to use when displaying this error.
     * Default 500
     *
     * @param int $statusCode
     * @return $this
     */
    public function setStatusCode($statusCode)
    {
        $this->statusCode = $statusCode;
        return $this;
    }

    /**
     * Set a formatter to use if Director::is_cli() is true
     *
     * @param FormatterInterface $cliFormatter
     * @return HTTPOutputHandler Return $this to allow chainable calls
     */
    public function setCLIFormatter(FormatterInterface $cliFormatter)
    {
        $this->cliFormatter = $cliFormatter;

        return $this;
    }

    /**
     * Return the formatter use if Director::is_cli() is true
     * If none has been set, null is returned, and the getFormatter() result will be used instead
     *
     * @return FormatterInterface
     */
    public function getCLIFormatter()
    {
        return $this->cliFormatter;
    }

    /**
     * Return the formatter to use in this case.
     * May be the getCliFormatter() value if one is provided and Director::is_cli() is true.
     *
     * @return FormatterInterface
     */
    public function getFormatter(): FormatterInterface
    {
        if (Director::is_cli() && ($cliFormatter = $this->getCLIFormatter())) {
            return $cliFormatter;
        }

        return $this->getDefaultFormatter();
    }

    /**
     * Check default formatter to use
     *
     * @return FormatterInterface
     */
    public function getDefaultFormatter(): FormatterInterface
    {
        return parent::getFormatter();
    }

    /**
     * Set default formatter
     *
     * @param FormatterInterface $formatter
     * @return $this
     */
    public function setDefaultFormatter(FormatterInterface $formatter)
    {
        parent::setFormatter($formatter);
        return $this;
    }

    protected function shouldShowError(int $errorCode): bool
    {
        // show all non-E_USER_DEPRECATED errors
        // or E_USER_DEPRECATED errors when not triggering from the Deprecation class
        // or our deprecations when the relevant shouldShow method returns true
        return $errorCode !== E_USER_DEPRECATED
            || !Deprecation::isTriggeringError()
            || ($this->isCli() ? Deprecation::shouldShowForCli() : Deprecation::shouldShowForHttp());
    }

    /**
     * @param array $record
     * @return bool
     */
    protected function write(LogRecord $record): void
    {
        ini_set('display_errors', 0);

        // Suppress errors that should be suppressed
        if (isset($record['context']['code'])) {
            $errorCode = $record['context']['code'];
            if (!$this->shouldShowError($errorCode)) {
                return;
            }
        }

        if (Controller::has_curr()) {
            $response = Controller::curr()->getResponse();
        } else {
            $response = new HTTPResponse();
        }

        // If headers have been sent then these won't be used, and may throw errors that we won't want to see.
        if (!headers_sent()) {
            $response->setStatusCode($this->getStatusCode());
            $response->addHeader('Content-Type', $this->getContentType());
        } else {
            // To suppress errors about errors
            $response->setStatusCode(200);
        }

        $response->setBody($record['formatted']);
        $response->output();
    }

    /**
     * This method is required and must be protected for unit testing, since we can't mock static or private methods
     */
    protected function isCli(): bool
    {
        return Director::is_cli();
    }
}
