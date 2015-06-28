<?php

namespace SilverStripe\Dev;

use SilverStripe\Control\Email\Email;
use SilverStripe\Control\Email\Mailer;

class TestMailer extends Mailer
{
    /**
     * @var string
     * @config
     */
    private static $swift_transport = 'Swift_NullTransport';

    protected $emailsSent = array();

    /**
     * @param \SilverStripe\Control\Email\Email $message
     * @return int
     */
    public function send($message)
    {
        $this->saveEmail($message);

        return true;
    }

    /**
     * Save a single email to the log
     * @param mixed $data A map of information about the email
     */
    protected function saveEmail($data)
    {
        $this->emailsSent[] = $data;
    }

    /**
     * Clear the log of emails sent
     */
    public function clearEmails()
    {
        $this->emailsSent = array();
        return $this;
    }

    /**
     * Search for an email that was sent.
     * All of the parameters can either be a string, or, if they start with "/", a PREG-compatible regular expression.
     *
     * @param string $to
     * @param string $from
     * @param string $subject
     * @param string $content
     * @return Email|false
     */
    public function findEmail($to, $from = null, $subject = null, $content = null)
    {
        $compare = [
            'To' => $to,
            'From' => $from,
            'Subject' => $subject,
            'Content' => $content,
        ];

        foreach ($this->emailsSent as $email) {
            foreach (array(
                'to' => 'getTo',
                'from' => 'getFrom',
                'subject' => 'getSubeject',
                'content' => 'getBody',
            ) as $field => $method) {
                if ($value = $$field) {
                    $actual = $email->$method();
                    $isRegex = $value[0] == '/';
                    if (!is_array($actual)) {
                        $actual = array($actual);
                    }
                    foreach ($actual as $actualAddress => $actualName) {
                        if ($isRegex) {
                            $matched = preg_match($value, array($actualAddress, $actualName));
                        } else {
                            $matched = ($actualAddress == $value || $actualName == $value);
                        }
                        if ($matched) {
                            return $email;
                        }
                    }
                }
            }
        }
    }
}
