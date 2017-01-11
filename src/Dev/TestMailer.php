<?php

namespace SilverStripe\Dev;

use SilverStripe\Control\Email\Mailer;

class TestMailer extends Mailer
{
    protected $emailsSent = array();

    /**
     * Send a plain-text email.
     * TestMailer will merely record that the email was asked to be sent, without sending anything.
     *
     * @param string $to
     * @param string $from
     * @param string $subject
     * @param string $plainContent
     * @param bool $attachedFiles
     * @param bool $customHeaders
     * @return bool|mixed
     */
    public function sendPlain($to, $from, $subject, $plainContent, $attachedFiles = false, $customHeaders = false)
    {
        $this->saveEmail([
            'Type' => 'plain',
            'To' => $to,
            'From' => $from,
            'Subject' => $subject,

            'Content' => $plainContent,
            'PlainContent' => $plainContent,

            'AttachedFiles' => $attachedFiles,
            'CustomHeaders' => $customHeaders,
        ]);

        return true;
    }

    /**
     * Send a multi-part HTML email
     * TestMailer will merely record that the email was asked to be sent, without sending anything.
     *
     * @param string $to
     * @param string $from
     * @param string $subject
     * @param string $htmlContent
     * @param bool $attachedFiles
     * @param bool $customHeaders
     * @param bool $plainContent
     * @param bool $inlineImages
     * @return bool|mixed
     */
    public function sendHTML(
        $to,
        $from,
        $subject,
        $htmlContent,
        $attachedFiles = false,
        $customHeaders = false,
        $plainContent = false,
        $inlineImages = false
    ) {

        $this->saveEmail([
            'Type' => 'html',
            'To' => $to,
            'From' => $from,
            'Subject' => $subject,

            'Content' => $htmlContent,
            'PlainContent' => $plainContent,
            'HtmlContent' => $htmlContent,

            'AttachedFiles' => $attachedFiles,
            'CustomHeaders' => $customHeaders,
            'InlineImages' => $inlineImages,
        ]);

        return true;
    }

    /**
     * Save a single email to the log
     * @param $data A map of information about the email
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
    }

    /**
     * Search for an email that was sent.
     * All of the parameters can either be a string, or, if they start with "/", a PREG-compatible regular expression.
     *
     * @param string $to
     * @param string $from
     * @param string $subject
     * @param string $content
     * @return array Contains the keys: 'type', 'to', 'from', 'subject', 'content', 'plainContent', 'attachedFiles',
     *               'customHeaders', 'htmlContent', 'inlineImages'
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
            $matched = true;

            foreach (array('To','From','Subject','Content') as $field) {
                if ($value = $compare[$field]) {
                    if ($value[0] == '/') {
                        $matched = preg_match($value, $email[$field]);
                    } else {
                        $matched = ($value == $email[$field]);
                    }
                    if (!$matched) {
                        break;
                    }
                }
            }

            if ($matched) {
                return $email;
            }
        }
    }
}
