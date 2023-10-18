<?php

namespace SilverStripe\Logging;

use SilverStripe\Core\Convert;
use SilverStripe\Dev\Debug;
use SilverStripe\Control\Director;
use SilverStripe\Control\Email\Email;
use Monolog\Formatter\FormatterInterface;
use Monolog\LogRecord;

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
     * @return $this
     */
    public function setStatusCode($statusCode)
    {
        $this->statusCode = $statusCode;
        return $this;
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
     * @return $this
     */
    public function setTitle($title)
    {
        $this->friendlyErrorMessage = $title;
        return $this;
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
     * @return $this
     */
    public function setBody($body)
    {
        $this->friendlyErrorDetail = $body;
        return $this;
    }

    public function format(array|LogRecord $record)
    {
        // Get error code
        $code = empty($record['code']) ? $this->getStatusCode() : $record['code'];
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
        if (Director::is_ajax()) {
            return $this->getTitle();
        }

        $renderer = Debug::create_debug_view();
        $output = $renderer->renderHeader();
        $output .= $renderer->renderInfo("Website Error", $this->getTitle(), $this->getBody());

        if (!is_null($contactInfo = $this->addContactAdministratorInfo())) {
            $output .= $renderer->renderParagraph($contactInfo);
        }

        $output .= $renderer->renderFooter();
        return $output;
    }

    /**
     * Generate the line with admin contact info
     *
     * @return string|null
     */
    private function addContactAdministratorInfo()
    {
        if (!$adminEmail = Email::config()->admin_email) {
            return null;
        }

        if (is_string($adminEmail)) {
            return 'Contact an administrator: ' . Email::obfuscate($adminEmail);
        }

        if (!is_array($adminEmail) || !count($adminEmail ?? [])) {
            return null;
        }

        $email = array_keys($adminEmail)[0];
        $name = array_values($adminEmail)[0];

        return sprintf('Contact %s: %s', Convert::raw2xml($name), Email::obfuscate($email));
    }
}
