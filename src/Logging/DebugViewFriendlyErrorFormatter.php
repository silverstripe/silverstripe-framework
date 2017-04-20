<?php

namespace SilverStripe\Logging;

use SilverStripe\Dev\Debug;
use SilverStripe\Control\Director;
use SilverStripe\Control\Email\Email;
use Monolog\Formatter\FormatterInterface;

/**
 * Produce a friendly error message
 */
class DebugViewFriendlyErrorFormatter implements FormatterInterface
{

    /**
     * Default status code
     *
     * @var int
     */
    protected $statusCode = 500;

    /**
     * Default friendly error
     *
     * @var string
     */
    protected $friendlyErrorMessage = 'Error';

    /**
     * Default error body
     *
     * @var string
     */
    protected $friendlyErrorDetail;

    /**
     * Get default status code
     *
     * @return int
     */
    public function getStatusCode()
    {
        return $this->statusCode;
    }

    /**
     * Set default status code
     *
     * @param int $statusCode
     */
    public function setStatusCode($statusCode)
    {
        $this->statusCode = $statusCode;
    }

    /**
     * Get friendly title
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->friendlyErrorMessage;
    }

    /**
     * Set friendly title
     *
     * @param string $title
     */
    public function setTitle($title)
    {
        $this->friendlyErrorMessage = $title;
    }

    /**
     * Get default error body
     *
     * @return string
     */
    public function getBody()
    {
        return $this->friendlyErrorDetail;
    }

    /**
     * Set default error body
     *
     * @param string $body
     */
    public function setBody($body)
    {
        $this->friendlyErrorDetail = $body;
    }

    public function format(array $record)
    {
        // Get error code
        $code = empty($record['code']) ? $this->statusCode : $record['code'];
        return $this->output($code);
    }

    public function formatBatch(array $records)
    {
        $message = '';
        foreach ($records as $record) {
            $message .= $this->format($record);
        }
        return $message;
    }

    /**
     * Return the appropriate error content for the given status code
     *
     * @param int $statusCode
     * @return string Content in an appropriate format for the current request
     */
    public function output($statusCode)
    {
        // TODO: Refactor into a content-type option
        if (Director::is_ajax()) {
            return $this->getTitle();
        }

        $renderer = Debug::create_debug_view();
        $output = $renderer->renderHeader();
        $output .= $renderer->renderInfo("Website Error", $this->getTitle(), $this->getBody());

        if (Email::config()->admin_email) {
            $mailto = Email::obfuscate(Email::config()->admin_email);
            $output .= $renderer->renderParagraph('Contact an administrator: ' . $mailto . '');
        }

        $output .= $renderer->renderFooter();
        return $output;
    }
}
