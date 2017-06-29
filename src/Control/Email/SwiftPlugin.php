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

        $sendAllTo = Email::getSendAllEmailsTo();
        if (!empty($sendAllTo)) {
            $this->setTo($message, $sendAllTo);
        }

        $ccAllTo = Email::getCCAllEmailsTo();
        if (!empty($ccAllTo)) {
            foreach ($ccAllTo as $address => $name) {
                $message->addCc($address, $name);
            }
        }

        $bccAllTo = Email::getBCCAllEmailsTo();
        if (!empty($bccAllTo)) {
            foreach ($bccAllTo as $address => $name) {
                $message->addBcc($address, $name);
            }
        }

        $sendAllFrom = Email::getSendAllEmailsFrom();
        if (!empty($sendAllFrom)) {
            $this->setFrom($message, $sendAllFrom);
        }
    }

    /**
     * @param \Swift_Mime_Message $message
     * @param array|string $to
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
     * @param array|string $from
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
