<?php

namespace SilverStripe\Control\Email;

use DateTime;
use RuntimeException;
use Egulias\EmailValidator\EmailValidator;
use Egulias\EmailValidator\Validation\RFCValidation;
use SilverStripe\Control\Director;
use SilverStripe\Control\HTTP;
use SilverStripe\Core\Convert;
use SilverStripe\Core\Environment;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\ORM\FieldType\DBDatetime;
use SilverStripe\ORM\FieldType\DBField;
use SilverStripe\ORM\FieldType\DBHTMLText;
use SilverStripe\View\Requirements;
use SilverStripe\View\SSViewer;
use SilverStripe\View\ThemeResourceLoader;
use SilverStripe\View\ViewableData;
use Swift_Message;
use Swift_Mime_SimpleMessage;
use Swift_MimePart;

/**
 * Class to support sending emails.
 */
class Email extends ViewableData
{
    /**
     * @var array
     * @config
     */
    private static $send_all_emails_to = [];

    /**
     * @var array
     * @config
     */
    private static $cc_all_emails_to = [];

    /**
     * @var array
     * @config
     */
    private static $bcc_all_emails_to = [];

    /**
     * @var array
     * @config
     */
    private static $send_all_emails_from = [];

    /**
     * This will be set in the config on a site-by-site basis
     * @see https://docs.silverstripe.org/en/4/developer_guides/email/#administrator-emails
     *
     * @config
     * @var string|array The default administrator email address or array of [email => name]
     */
    private static $admin_email = null;

    /**
     * @var Swift_Message
     */
    private $swiftMessage;

    /**
     * @var string The name of the HTML template to render the email with (without *.ss extension)
     */
    private $HTMLTemplate = null;

    /**
     * @var string The name of the plain text template to render the plain part of the email with
     */
    private $plainTemplate = null;

    /**
     * @var Swift_MimePart
     */
    private $plainPart;

    /**
     * @var array|ViewableData Additional data available in a template.
     * Used in the same way than {@link ViewableData->customize()}.
     */
    private $data = [];

    /**
     * @var array
     */
    private $failedRecipients = [];

    /**
     * Checks for RFC822-valid email format.
     *
     * @param string $address
     * @return boolean
     *
     * @copyright Cal Henderson <cal@iamcal.com>
     *    This code is licensed under a Creative Commons Attribution-ShareAlike 2.5 License
     *    http://creativecommons.org/licenses/by-sa/2.5/
     */
    public static function is_valid_address(string|array $address): bool
    {
        $validator = new EmailValidator();
        return $validator->isValid($address, new RFCValidation());
    }

    /**
     * Get send_all_emails_to
     *
     * @return array Keys are addresses, values are names
     */
    public static function getSendAllEmailsTo(): array
    {
        return static::mergeConfiguredEmails('send_all_emails_to', 'SS_SEND_ALL_EMAILS_TO');
    }

    /**
     * Get cc_all_emails_to
     *
     * @return array
     */
    public static function getCCAllEmailsTo(): array
    {
        return static::mergeConfiguredEmails('cc_all_emails_to', 'SS_CC_ALL_EMAILS_TO');
    }

    /**
     * Get bcc_all_emails_to
     *
     * @return array
     */
    public static function getBCCAllEmailsTo(): array
    {
        return static::mergeConfiguredEmails('bcc_all_emails_to', 'SS_BCC_ALL_EMAILS_TO');
    }

    /**
     * Get send_all_emails_from
     *
     * @return array
     */
    public static function getSendAllEmailsFrom(): array
    {
        return static::mergeConfiguredEmails('send_all_emails_from', 'SS_SEND_ALL_EMAILS_FROM');
    }

    /**
     * Normalise email list from config merged with env vars
     *
     * @param string $config Config key
     * @param string $env Env variable key
     * @return array Array of email addresses
     */
    protected static function mergeConfiguredEmails(string $config, string $env): array
    {
        // Normalise config list
        $normalised = [];
        $source = (array)static::config()->get($config);
        foreach ($source as $address => $name) {
            if ($address && !is_numeric($address)) {
                $normalised[$address] = $name;
            } elseif ($name) {
                $normalised[$name] = null;
            }
        }
        $extra = Environment::getEnv($env);
        if ($extra) {
            $normalised[$extra] = null;
        }
        return $normalised;
    }

    /**
     * Encode an email-address to protect it from spambots.
     * At the moment only simple string substitutions,
     * which are not 100% safe from email harvesting.
     *
     * @param string $email Email-address
     * @param string $method Method for obfuscating/encoding the address
     *    - 'direction': Reverse the text and then use CSS to put the text direction back to normal
     *    - 'visible': Simple string substitution ('@' to '[at]', '.' to '[dot], '-' to [dash])
     *    - 'hex': Hexadecimal URL-Encoding - useful for mailto: links
     * @return string
     */
    public static function obfuscate(string $email, string $method = 'visible'): string
    {
        switch ($method) {
            case 'direction':
                Requirements::customCSS('span.codedirection { unicode-bidi: bidi-override; direction: rtl; }', 'codedirectionCSS');

                return '<span class="codedirection">' . strrev($email) . '</span>';
            case 'visible':
                $obfuscated = ['@' => ' [at] ', '.' => ' [dot] ', '-' => ' [dash] '];

                return strtr($email, $obfuscated);
            case 'hex':
                $encoded = '';
                $emailLength = strlen($email);
                for ($x = 0; $x < $emailLength; $x++) {
                    $encoded .= '&#x' . bin2hex($email[$x]) . ';';
                }

                return $encoded;
            default:
                user_error('Email::obfuscate(): Unknown obfuscation method', E_USER_NOTICE);

                return $email;
        }
    }

    /**
     * Email constructor.
     * @param string|array|null $from
     * @param string|array|null $to
     * @param string|null $subject
     * @param string|null $body
     * @param string|array|null $cc
     * @param string|array|null $bcc
     * @param string|null $returnPath
     */
    public function __construct(
        string|array $from = null,
        $to = null,
        $subject = null,
        $body = null,
        $cc = null,
        $bcc = null,
        $returnPath = null
    ): void {
        if ($from) {
            $this->setFrom($from);
        }
        if ($to) {
            $this->setTo($to);
        }
        if ($subject) {
            $this->setSubject($subject);
        }
        if ($body) {
            $this->setBody($body);
        }
        if ($cc) {
            $this->setCC($cc);
        }
        if ($bcc) {
            $this->setBCC($bcc);
        }
        if ($returnPath) {
            $this->setReturnPath($returnPath);
        }

        parent::__construct();
    }

    /**
     * @return Swift_Message
     */
    public function getSwiftMessage(): Swift_Message
    {
        if (!$this->swiftMessage) {
            $message = new Swift_Message(null, null, 'text/html', 'utf-8');
            // Set priority to fix PHP 8.1 SimpleMessage::getPriority() sscanf() null parameter
            $message->setPriority(Swift_Mime_SimpleMessage::PRIORITY_NORMAL);
            $this->setSwiftMessage($message);
        }

        return $this->swiftMessage;
    }

    /**
     * @param Swift_Message $swiftMessage
     *
     * @return $this
     */
    public function setSwiftMessage(Swift_Message $swiftMessage): SilverStripe\Control\Email\Email
    {
        $dateTime = new DateTime();
        $dateTime->setTimestamp(DBDatetime::now()->getTimestamp());
        $swiftMessage->setDate($dateTime);
        if (!$swiftMessage->getFrom()) {
            $swiftMessage->setFrom($this->getDefaultFrom());
        }
        $this->swiftMessage = $swiftMessage;

        return $this;
    }

    /**
     * @return string
     */
    private function getDefaultFrom(): string
    {
        // admin_email can have a string or an array config
        // https://docs.silverstripe.org/en/4/developer_guides/email/#administrator-emails
        $adminEmail = $this->config()->get('admin_email');
        if (is_array($adminEmail) && count($adminEmail ?? []) > 0) {
            $defaultFrom = array_keys($adminEmail)[0];
        } else {
            if (is_string($adminEmail)) {
                $defaultFrom = $adminEmail;
            } else {
                $defaultFrom = '';
            }
        }
        if (empty($defaultFrom)) {
            $host = Director::host();
            if (empty($host)) {
                throw new RuntimeException('Host not defined');
            }
            $defaultFrom = sprintf('no-reply@%s', $host);
        }
        $this->extend('updateDefaultFrom', $defaultFrom);
        return $defaultFrom;
    }

    /**
     * @return string[]
     */
    public function getFrom(): array
    {
        return $this->getSwiftMessage()->getFrom();
    }

    /**
     * @param string|array $address
     * @return string|array
     */
    private function sanitiseAddress(string|array $address): string|array
    {
        if (is_array($address)) {
            return array_map('trim', $address ?? []);
        }
        return trim($address ?? '');
    }

    /**
     * @param string|array $address
     * @param string|null $name
     * @return $this
     */
    public function setFrom(string|array $address, $name = null): SilverStripe\Control\Email\Email
    {
        $address = $this->sanitiseAddress($address);
        $this->getSwiftMessage()->setFrom($address, $name);

        return $this;
    }

    /**
     * @param string|array $address
     * @param string|null $name
     * @return $this
     */
    public function addFrom(string $address, $name = null): SilverStripe\Control\Email\Email
    {
        $address = $this->sanitiseAddress($address);
        $this->getSwiftMessage()->addFrom($address, $name);

        return $this;
    }

    /**
     * @return string
     */
    public function getSender(): null|array
    {
        return $this->getSwiftMessage()->getSender();
    }

    /**
     * @param string $address
     * @param string|null $name
     * @return $this
     */
    public function setSender(string $address, string $name = null): SilverStripe\Control\Email\Email
    {
        $address = $this->sanitiseAddress($address);
        $this->getSwiftMessage()->setSender($address, $name);

        return $this;
    }

    /**
     * @return string
     */
    public function getReturnPath(): string|null
    {
        return $this->getSwiftMessage()->getReturnPath();
    }

    /**
     * The bounce handler address
     *
     * @param string $address Email address where bounce notifications should be sent
     * @return $this
     */
    public function setReturnPath(string $address): SilverStripe\Control\Email\Email
    {
        $address = $this->sanitiseAddress($address);
        $this->getSwiftMessage()->setReturnPath($address);
        return $this;
    }

    /**
     * @return array
     */
    public function getTo(): array|null
    {
        return $this->getSwiftMessage()->getTo();
    }

    /**
     * Set recipient(s) of the email
     *
     * To send to many, pass an array:
     * ['me@example.com' => 'My Name', 'other@example.com'];
     *
     * @param string|array $address The message recipient(s) - if sending to multiple, use an array of address => name
     * @param string|null $name The name of the recipient (if one)
     * @return $this
     */
    public function setTo(string|array $address, string $name = null): SilverStripe\Control\Email\Email
    {
        $address = $this->sanitiseAddress($address);
        $this->getSwiftMessage()->setTo($address, $name);

        return $this;
    }

    /**
     * @param string|array $address
     * @param string|null $name
     * @return $this
     */
    public function addTo(string $address, $name = null): SilverStripe\Control\Email\Email
    {
        $address = $this->sanitiseAddress($address);
        $this->getSwiftMessage()->addTo($address, $name);

        return $this;
    }

    /**
     * @return array
     */
    public function getCC(): array
    {
        return $this->getSwiftMessage()->getCc();
    }

    /**
     * @param string|array $address
     * @param string|null $name
     * @return $this
     */
    public function setCC(string $address, string $name = null): SilverStripe\Control\Email\Email
    {
        $address = $this->sanitiseAddress($address);
        $this->getSwiftMessage()->setCc($address, $name);

        return $this;
    }

    /**
     * @param string|array $address
     * @param string|null $name
     * @return $this
     */
    public function addCC(string $address, string $name = null): SilverStripe\Control\Email\Email
    {
        $address = $this->sanitiseAddress($address);
        $this->getSwiftMessage()->addCc($address, $name);

        return $this;
    }

    /**
     * @return array
     */
    public function getBCC(): array
    {
        return $this->getSwiftMessage()->getBcc();
    }

    /**
     * @param string|array $address
     * @param string|null $name
     * @return $this
     */
    public function setBCC(string $address, string $name = null): SilverStripe\Control\Email\Email
    {
        $address = $this->sanitiseAddress($address);
        $this->getSwiftMessage()->setBcc($address, $name);

        return $this;
    }

    /**
     * @param string|array $address
     * @param string|null $name
     * @return $this
     */
    public function addBCC(string $address, string $name = null): SilverStripe\Control\Email\Email
    {
        $address = $this->sanitiseAddress($address);
        $this->getSwiftMessage()->addBcc($address, $name);

        return $this;
    }

    /**
     * @return mixed
     */
    public function getReplyTo(): null|array
    {
        return $this->getSwiftMessage()->getReplyTo();
    }

    /**
     * @param string|array $address
     * @param string|null $name
     * @return $this
     */
    public function setReplyTo(string $address, string $name = null): SilverStripe\Control\Email\Email
    {
        $address = $this->sanitiseAddress($address);
        $this->getSwiftMessage()->setReplyTo($address, $name);

        return $this;
    }

    /**
     * @param string|array $address
     * @param string|null $name
     * @return $this
     */
    public function addReplyTo(string $address, $name = null): SilverStripe\Control\Email\Email
    {
        $address = $this->sanitiseAddress($address);
        $this->getSwiftMessage()->addReplyTo($address, $name);

        return $this;
    }

    /**
     * @return string
     */
    public function getSubject(): string|null
    {
        return $this->getSwiftMessage()->getSubject();
    }

    /**
     * @param string $subject The Subject line for the email
     * @return $this
     */
    public function setSubject(string $subject): SilverStripe\Control\Email\Email
    {
        $this->getSwiftMessage()->setSubject($subject);

        return $this;
    }

    /**
     * @return int
     */
    public function getPriority(): string
    {
        return $this->getSwiftMessage()->getPriority();
    }

    /**
     * @param int $priority
     * @return $this
     */
    public function setPriority(int $priority): SilverStripe\Control\Email\Email
    {
        $this->getSwiftMessage()->setPriority($priority);

        return $this;
    }

    /**
     * @param string $path Path to file
     * @param string $alias An override for the name of the file
     * @param string $mime The mime type for the attachment
     * @return $this
     */
    public function addAttachment(string $path, $alias = null, string $mime = null): SilverStripe\Control\Email\Email
    {
        $attachment = \Swift_Attachment::fromPath($path);
        if ($alias) {
            $attachment->setFilename($alias);
        }
        if ($mime) {
            $attachment->setContentType($mime);
        }
        $this->getSwiftMessage()->attach($attachment);

        return $this;
    }

    /**
     * @param string $data
     * @param string $name
     * @param string $mime
     * @return $this
     */
    public function addAttachmentFromData(string $data, string $name, string $mime = null): SilverStripe\Control\Email\Email
    {
        $attachment = new \Swift_Attachment($data, $name);
        if ($mime) {
            $attachment->setContentType($mime);
        }
        $this->getSwiftMessage()->attach($attachment);

        return $this;
    }

    /**
     * @return array|ViewableData The template data
     */
    public function getData(): SilverStripe\Security\Member|array
    {
        return $this->data;
    }

    /**
     * @param array|ViewableData $data The template data to set
     * @return $this
     */
    public function setData(SilverStripe\Security\Member|array $data): SilverStripe\Control\Email\Email
    {
        $this->data = $data;
        $this->invalidateBody();

        return $this;
    }

    /**
     * @param string|array $name The data name to add or array to names => value
     * @param string|null $value The value of the data to add
     * @return $this
     */
    public function addData(string|array $name, string|bool|SilverStripe\Security\Member $value = null): SilverStripe\Control\Email\Email
    {
        if (is_array($name)) {
            $this->data = array_merge($this->data, $name);
        } elseif (is_array($this->data)) {
            $this->data[$name] = $value;
        } else {
            $this->data->$name = $value;
        }

        $this->invalidateBody();

        return $this;
    }

    /**
     * Remove a datum from the message
     *
     * @param string $name
     * @return $this
     */
    public function removeData(string $name): SilverStripe\Control\Email\Email
    {
        if (is_array($this->data)) {
            unset($this->data[$name]);
        } else {
            $this->data->$name = null;
        }

        $this->invalidateBody();

        return $this;
    }

    /**
     * @return string
     */
    public function getBody(): string|null
    {
        return $this->getSwiftMessage()->getBody();
    }

    /**
     * @param string $body The email body
     * @return $this
     */
    public function setBody(SilverStripe\ORM\FieldType\DBHTMLText|string $body): SilverStripe\Control\Email\Email
    {
        $plainPart = $this->findPlainPart();
        if ($plainPart) {
            $this->getSwiftMessage()->detach($plainPart);
        }
        unset($plainPart);

        $body = HTTP::absoluteURLs($body);
        $this->getSwiftMessage()->setBody($body);

        return $this;
    }

    /**
     * @return $this
     */
    public function invalidateBody(): SilverStripe\Control\Email\Email
    {
        $this->setBody(null);

        return $this;
    }

    /**
     * @return string The base URL for the email
     */
    public function BaseURL()
    {
        return Director::absoluteBaseURL();
    }

    /**
     * Debugging help
     *
     * @return string Debug info
     */
    public function debug()
    {
        $this->render();

        $class = static::class;
        return "<h2>Email template {$class}:</h2>\n" . '<pre>' . $this->getSwiftMessage()->toString() . '</pre>';
    }

    /**
     * @return string
     */
    public function getHTMLTemplate(): string
    {
        if ($this->HTMLTemplate) {
            return $this->HTMLTemplate;
        }

        return ThemeResourceLoader::inst()->findTemplate(
            SSViewer::get_templates_by_class(static::class, '', self::class),
            SSViewer::get_themes()
        );
    }

    /**
     * Set the template to render the email with
     *
     * @param string $template
     * @return $this
     */
    public function setHTMLTemplate(string $template): SilverStripe\Control\Email\Email
    {
        if (substr($template ?? '', -3) == '.ss') {
            $template = substr($template ?? '', 0, -3);
        }
        $this->HTMLTemplate = $template;

        return $this;
    }

    /**
     * Get the template to render the plain part with
     *
     * @return string
     */
    public function getPlainTemplate(): null|string
    {
        return $this->plainTemplate;
    }

    /**
     * Set the template to render the plain part with
     *
     * @param string $template
     * @return $this
     */
    public function setPlainTemplate(string $template): SilverStripe\Control\Email\Email
    {
        if (substr($template ?? '', -3) == '.ss') {
            $template = substr($template ?? '', 0, -3);
        }
        $this->plainTemplate = $template;

        return $this;
    }

    /**
     * @param array $recipients
     * @return $this
     */
    public function setFailedRecipients(array $recipients): SilverStripe\Control\Email\Email
    {
        $this->failedRecipients = $recipients;

        return $this;
    }

    /**
     * @return array
     */
    public function getFailedRecipients(): array
    {
        return $this->failedRecipients;
    }

    /**
     * Used by {@link SSViewer} templates to detect if we're rendering an email template rather than a page template
     *
     * @return bool
     */
    public function IsEmail(): bool
    {
        return true;
    }

    /**
     * Send the message to the recipients
     *
     * @return bool true if successful or array of failed recipients
     */
    public function send(): bool
    {
        if (!$this->getBody()) {
            $this->render();
        }
        if (!$this->hasPlainPart()) {
            $this->generatePlainPartFromBody();
        }
        return Injector::inst()->get(Mailer::class)->send($this);
    }

    /**
     * @return array|bool
     */
    public function sendPlain(): bool
    {
        if (!$this->hasPlainPart()) {
            $this->render(true);
        }
        return Injector::inst()->get(Mailer::class)->send($this);
    }

    /**
     * Render the email
     * @param bool $plainOnly Only render the message as plain text
     * @return $this
     */
    public function render(bool $plainOnly = false): SilverStripe\Control\Email\Email
    {
        if ($existingPlainPart = $this->findPlainPart()) {
            $this->getSwiftMessage()->detach($existingPlainPart);
        }
        unset($existingPlainPart);

        // Respect explicitly set body
        $htmlPart = $plainOnly ? null : $this->getBody();
        $plainPart = $plainOnly ? $this->getBody() : null;

        // Ensure we can at least render something
        $htmlTemplate = $this->getHTMLTemplate();
        $plainTemplate = $this->getPlainTemplate();
        if (!$htmlTemplate && !$plainTemplate && !$plainPart && !$htmlPart) {
            return $this;
        }

        // Do not interfere with emails styles
        Requirements::clear();

        // Render plain part
        if ($plainTemplate && !$plainPart) {
            $plainPart = $this->renderWith($plainTemplate, $this->getData())->Plain();
        }

        // Render HTML part, either if sending html email, or a plain part is lacking
        if (!$htmlPart && $htmlTemplate && (!$plainOnly || empty($plainPart))) {
            $htmlPart = $this->renderWith($htmlTemplate, $this->getData());
        }

        // Plain part fails over to generated from html
        if (!$plainPart && $htmlPart) {
            /** @var DBHTMLText $htmlPartObject */
            $htmlPartObject = DBField::create_field('HTMLFragment', $htmlPart);
            $plainPart = $htmlPartObject->Plain();
        }

        // Rendering is finished
        Requirements::restore();

        // Fail if no email to send
        if (!$plainPart && !$htmlPart) {
            return $this;
        }

        // Build HTML / Plain components
        if ($htmlPart && !$plainOnly) {
            $this->setBody($htmlPart);
            $this->getSwiftMessage()->setContentType('text/html');
            $this->getSwiftMessage()->setCharset('utf-8');
            if ($plainPart) {
                $this->getSwiftMessage()->addPart($plainPart, 'text/plain', 'utf-8');
            }
        } else {
            if ($plainPart) {
                $this->setBody($plainPart);
            }
            $this->getSwiftMessage()->setContentType('text/plain');
            $this->getSwiftMessage()->setCharset('utf-8');
        }

        return $this;
    }

    /**
     * @return Swift_MimePart|false
     */
    public function findPlainPart(): bool|Swift_MimePart
    {
        foreach ($this->getSwiftMessage()->getChildren() as $child) {
            if ($child instanceof Swift_MimePart && $child->getContentType() == 'text/plain') {
                return $child;
            }
        }
        return false;
    }

    /**
     * @return bool
     */
    public function hasPlainPart(): bool
    {
        if ($this->getSwiftMessage()->getContentType() === 'text/plain') {
            return true;
        }
        return (bool) $this->findPlainPart();
    }

    /**
     * Automatically adds a plain part to the email generated from the current Body
     *
     * @return $this
     */
    public function generatePlainPartFromBody(): SilverStripe\Control\Email\Email
    {
        $plainPart = $this->findPlainPart();
        if ($plainPart) {
            $this->getSwiftMessage()->detach($plainPart);
        }
        unset($plainPart);

        $this->getSwiftMessage()->addPart(
            Convert::xml2raw($this->getBody()),
            'text/plain',
            'utf-8'
        );

        return $this;
    }
}
