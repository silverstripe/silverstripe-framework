<?php

namespace SilverStripe\Dev;

use SilverStripe\Control\Email\Mailer;
use Swift_Attachment;
use SilverStripe\Dev\Deprecation;

class TestMailer implements Mailer
{
    /**
     * @var array
     * @deprecated 4.13.0 Will be removed without equivalent functionality to replace it
     */
    protected $emailsSent = [];

    public function send($email)
    {
        // Detect body type
        $htmlContent = null;
        $plainContent = null;
        if ($email->getSwiftMessage()->getContentType() === 'text/plain') {
            $type = 'plain';
            $plainContent = $email->getBody();
        } else {
            $type = 'html';
            $htmlContent = $email->getBody();
            $plainPart = $email->findPlainPart();
            if ($plainPart) {
                $plainContent = $plainPart->getBody();
            }
        }

        // Get attachments
        $attachedFiles = [];
        foreach ($email->getSwiftMessage()->getChildren() as $child) {
            if ($child instanceof Swift_Attachment) {
                $attachedFiles[] = [
                    'contents' => $child->getBody(),
                    'filename' => $child->getFilename(),
                    'mimetype' => $child->getContentType(),
                ];
            }
        }

        // Serialise email
        $serialised = [
            'Type' => $type,
            'To' => implode(';', array_keys($email->getTo() ?: [])),
            'From' => implode(';', array_keys($email->getFrom() ?: [])),
            'Subject' => $email->getSubject(),
            'Content' => $email->getBody(),
            'AttachedFiles' => $attachedFiles,
            'Headers' => $email->getSwiftMessage()->getHeaders(),
        ];
        if ($plainContent) {
            $serialised['PlainContent'] = $plainContent;
        }
        if ($htmlContent) {
            $serialised['HtmlContent'] = $htmlContent;
        }

        Deprecation::withNoReplacement(function () use ($serialised) {
            $this->saveEmail($serialised);
        });

        return true;
    }

    /**
     * Save a single email to the log
     *
     * @param array $data A map of information about the email
     * @deprecated 4.13.0 Will be removed without equivalent functionality to replace it
     */
    protected function saveEmail($data)
    {
        Deprecation::notice('4.13.0', 'Will be removed without equivalent functionality to replace it');
        $this->emailsSent[] = $data;
    }

    /**
     * Clear the log of emails sent
     */
    public function clearEmails()
    {
        $this->emailsSent = [];
    }

    /**
     * Search for an email that was sent.
     * All of the parameters can either be a string, or, if they start with "/", a PREG-compatible regular expression.
     *
     * @param string $to
     * @param string $from
     * @param string $subject
     * @param string $content
     * @return array|null Contains keys: 'Type', 'To', 'From', 'Subject', 'Content', 'PlainContent', 'AttachedFiles',
     *               'HtmlContent'
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

            // Loop all our Email fields
            foreach ($compare as $field => $value) {
                $emailValue = $email[$field];
                if ($value) {
                    if (in_array($field, ['To', 'From'])) {
                        $emailValue = $this->normaliseSpaces($emailValue);
                        $value = $this->normaliseSpaces($value);
                    }
                    if ($value[0] === '/') {
                        $matched = preg_match($value ?? '', $emailValue ?? '');
                    } else {
                        $matched = ($value === $emailValue);
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
        return null;
    }

    /**
     * @param string $value
     */
    private function normaliseSpaces(string $value)
    {
        return str_replace([', ', '; '], [',', ';'], $value ?? '');
    }
}
