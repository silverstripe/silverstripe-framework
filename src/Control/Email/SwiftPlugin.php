<?php

namespace SilverStripe\Control\Email;

class SwiftPlugin implements \Swift_Events_SendListener
{
    /**
     * Before sending a message make sure all our overrides are taken into account
     *
     * @param \Swift_Events_SendEvent $evt
     */
    public function beforeSendPerformed(\Swift_Events_SendEvent $evt)
    {

        /** @var \Swift_Message $message */
        $message = $evt->getMessage();
        $sendAllTo = Email::config()->send_all_emails_to;
        $ccAllTo = Email::config()->cc_all_emails_to;
        $bccAllTo = Email::config()->bcc_all_emails_to;
        $sendAllFrom = Email::config()->send_all_emails_from;

        if (!empty($sendAllTo)) {
            $this->setTo($message, $sendAllTo);
        }

        if (!empty($ccAllTo)) {
            if (!is_array($ccAllTo)) {
                $ccAllTo = array($ccAllTo => null);
            }
            foreach ($ccAllTo as $address => $name) {
                $message->addCc($address, $name);
            }
        }

        if (!empty($bccAllTo)) {
            if (!is_array($bccAllTo)) {
                $bccAllTo = array($bccAllTo => null);
            }
            foreach ($bccAllTo as $address => $name) {
                $message->addBcc($address, $name);
            }
        }

        if (!empty($sendAllFrom)) {
            $this->setFrom($message, $sendAllFrom);
        }
    }

    /**
     * @param \Swift_Mime_Message $message
     * @param string $to
     */
    protected function setTo($message, $to)
    {
        $headers = $message->getHeaders();
        $origTo = $message->getTo();
        $cc = $message->getCc();
        $bcc = $message->getBcc();

        // set default recipient and remove all other recipients
        $message->setTo($to);
        $headers->removeAll('Cc');
        $headers->removeAll('Bcc');

        // store the old data as X-Original-* Headers for debugging
        $headers->addMailboxHeader('X-Original-To', $origTo);
        $headers->addMailboxHeader('X-Original-Cc', $cc);
        $headers->addMailboxHeader('X-Original-Bcc', $bcc);
    }

    /**
     * @param \Swift_Mime_Message $message
     * @param string $from
     */
    protected function setFrom($message, $from)
    {
        $headers = $message->getHeaders();
        $origFrom = $message->getFrom();
        $headers->addMailboxHeader('X-Original-From', $origFrom);
        $message->setFrom($from);
    }

    public function sendPerformed(\Swift_Events_SendEvent $evt)
    {
        // noop
    }
}
