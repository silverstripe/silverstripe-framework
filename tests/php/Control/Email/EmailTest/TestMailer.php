<?php

namespace SilverStripe\Control\Tests\Email\EmailTest;

use SilverStripe\Control\Email\Mailer;

class TestMailer extends Mailer
{

    public function sendHTML(
        $to,
        $from,
        $subject,
        $htmlContent,
        $attachedFiles = false,
        $customheaders = false,
        $plainContent = false
    ) {
        return array(
            'to' => $to,
            'from' => $from,
            'subject' => $subject,
            'content' => $htmlContent,
            'files' => $attachedFiles,
            'customheaders' => $customheaders,
            'plaincontent' => $plainContent
        );
    }

    public function sendPlain($to, $from, $subject, $plainContent, $attachedFiles = false, $customheaders = false)
    {
        return array(
            'to' => $to,
            'from' => $from,
            'subject' => $subject,
            'content' => $plainContent,
            'files' => $attachedFiles,
            'customheaders' => $customheaders
        );
    }
}
