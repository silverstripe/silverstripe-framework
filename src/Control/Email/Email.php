<?php

namespace SilverStripe\Control\Email;

use Exception;
use RuntimeException;
use Egulias\EmailValidator\EmailValidator;
use Egulias\EmailValidator\Validation\RFCValidation;
use SilverStripe\Control\Director;
use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Environment;
use SilverStripe\Core\Extensible;
use SilverStripe\Core\Injector\Injectable;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\ORM\FieldType\DBField;
use SilverStripe\View\ArrayData;
use SilverStripe\View\Requirements;
use SilverStripe\View\SSViewer;
use SilverStripe\View\ThemeResourceLoader;
use SilverStripe\View\ViewableData;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email as SymfonyEmail;
use Symfony\Component\Mime\Part\AbstractPart;

class Email extends SymfonyEmail
{
    use Configurable;
    use Extensible;
    use Injectable;

    private static string|array $send_all_emails_to = [];

    private static string|array $cc_all_emails_to = [];

    private static string|array $bcc_all_emails_to = [];

    private static string|array $send_all_emails_from = [];

    /**
     * The default "from" email address or array of [email => name], or the email address as a string
     * This will be set in the config on a site-by-site basis
     * @see https://docs.silverstripe.org/en/4/developer_guides/email/#administrator-emails
     */
    private static string|array $admin_email = '';

    /**
     * The name of the HTML template to render the email with (without *.ss extension)
     */
    private string $HTMLTemplate = '';

    /**
     * The name of the plain text template to render the plain part of the email with
     */
    private string $plainTemplate = '';

    /**
     * Additional data available in a template.
     * Used in the same way than {@link ViewableData->customize()}.
     */
    private ViewableData $data;

    private bool $dataHasBeenSet = false;

    /**
     * Checks for RFC822-valid email format.
     *
     * @copyright Cal Henderson <cal@iamcal.com>
     *    This code is licensed under a Creative Commons Attribution-ShareAlike 2.5 License
     *    http://creativecommons.org/licenses/by-sa/2.5/
     */
    public static function is_valid_address(string $address): bool
    {
        $validator = new EmailValidator();
        return $validator->isValid($address, new RFCValidation());
    }

    public static function getSendAllEmailsTo(): array
    {
        return static::mergeConfiguredAddresses('send_all_emails_to', 'SS_SEND_ALL_EMAILS_TO');
    }

    public static function getCCAllEmailsTo(): array
    {
        return static::mergeConfiguredAddresses('cc_all_emails_to', 'SS_CC_ALL_EMAILS_TO');
    }

    public static function getBCCAllEmailsTo(): array
    {
        return static::mergeConfiguredAddresses('bcc_all_emails_to', 'SS_BCC_ALL_EMAILS_TO');
    }

    public static function getSendAllEmailsFrom(): array
    {
        return static::mergeConfiguredAddresses('send_all_emails_from', 'SS_SEND_ALL_EMAILS_FROM');
    }

    /**
     * Normalise email list from config merged with env vars
     *
     * @return Address[]
     */
    private static function mergeConfiguredAddresses(string $configKey, string $envKey): array
    {
        $addresses = [];
        $config = (array) static::config()->get($configKey);
        $addresses = Email::convertConfigToAddreses($config);
        $env = Environment::getEnv($envKey);
        if ($env) {
            $addresses = array_merge($addresses, Email::convertConfigToAddreses($env));
        }
        return $addresses;
    }

    private static function convertConfigToAddreses(array|string $config): array
    {
        $addresses = [];
        if (is_array($config)) {
            foreach ($config as $key => $val) {
                if (filter_var($key, FILTER_VALIDATE_EMAIL)) {
                    $addresses[] = new Address($key, $val);
                } else {
                    $addresses[] = new Address($val);
                }
            }
        } else {
            $addresses[] = new Address($config);
        }
        return $addresses;
    }

    /**
     * Encode an email-address to protect it from spambots.
     * At the moment only simple string substitutions,
     * which are not 100% safe from email harvesting.
     *
     * $method defines the method for obfuscating/encoding the address
     * - 'direction': Reverse the text and then use CSS to put the text direction back to normal
     * - 'visible': Simple string substitution ('@' to '[at]', '.' to '[dot], '-' to [dash])
     * - 'hex': Hexadecimal URL-Encoding - useful for mailto: links
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

    public function __construct(
        string|array $from = '',
        string|array $to = '',
        string $subject = '',
        string $body = '',
        string|array $cc = '',
        string|array $bcc = '',
        string $returnPath = ''
    ) {
        parent::__construct();
        if ($from) {
            $this->setFrom($from);
        } else {
            $this->setFrom($this->getDefaultFrom());
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
        $this->data = ViewableData::create();
    }

    private function getDefaultFrom(): string|array
    {
        // admin_email can have a string or an array config
        // https://docs.silverstripe.org/en/4/developer_guides/email/#administrator-emails
        $adminEmail = $this->config()->get('admin_email');
        if (is_array($adminEmail) && count($adminEmail ?? []) > 0) {
            $email = array_keys($adminEmail)[0];
            $defaultFrom = [$email => $adminEmail[$email]];
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
     * Passing a string of HTML for $body will have no affect if you also call either setData() or addData()
     */
    public function setBody(AbstractPart|string $body = null): static
    {
        if ($body instanceof AbstractPart) {
            // pass to Symfony\Component\Mime\Message::setBody()
            return parent::setBody($body);
        }
        // Set HTML content directly.
        return $this->html($body);
    }

    /**
     * The following arguments combinations are valid
     * a) $address = 'my@email.com', $name = 'My name'
     * b) $address = ['my@email.com' => 'My name']
     * c) $address = ['my@email.com' => 'My name', 'other@email.com' => 'My other name']
     * d) $address = ['my@email.com' => 'My name', 'other@email.com']
     */
    private function createAddressArray(string|array $address, $name = ''): array
    {
        if (is_array($address)) {
            $ret = [];
            foreach ($address as $key => $val) {
                $addr = is_numeric($key) ? $val : $key;
                $name2 = is_numeric($key) ? '' : $val;
                $ret[] = new Address($addr, $name2);
            }
            return $ret;
        }
        return [new Address($address, $name)];
    }

    /**
     * @see createAddressArray()
     */
    public function setFrom(string|array $address, string $name = ''): static
    {
        return $this->from(...$this->createAddressArray($address, $name));
    }

    /**
     * @see createAddressArray()
     */
    public function setTo(string|array $address, string $name = ''): static
    {
        return $this->to(...$this->createAddressArray($address, $name));
    }

    /**
     * @see createAddressArray()
     */
    public function setCC(string|array $address, string $name = ''): static
    {
        return $this->cc(...$this->createAddressArray($address, $name));
    }

    /**
     * @see createAddressArray()
     */
    public function setBCC(string|array $address, string $name = ''): static
    {
        return $this->bcc(...$this->createAddressArray($address, $name));
    }

    public function setSender(string $address, string $name = ''): static
    {
        return $this->sender(new Address($address, $name));
    }

    public function setReplyTo(string $address, string $name = ''): static
    {
        return $this->replyTo(new Address($address, $name));
    }

    public function setSubject(string $subject): static
    {
        return $this->subject($subject);
    }

    public function setReturnPath(string $address): static
    {
        return $this->returnPath($address);
    }

    public function setPriority(int $priority): static
    {
        return $this->priority($priority);
    }

    /**
     * @param string $path Path to file
     * @param string $alias An override for the name of the file
     * @param string $mime The mime type for the attachment
     */
    public function addAttachment(string $path, ?string $alias = null, ?string $mime = null): static
    {
        return $this->attachFromPath($path, $alias, $mime);
    }

    public function addAttachmentFromData(string $data, string $name, string $mime = null): static
    {
        return $this->attach($data, $name, $mime);
    }

    /**
     * Get data which is exposed to the template
     *
     * The following data is exposed via this method by default:
     * IsEmail: used to detect if rendering an email template rather than a page template
     * BaseUrl: used to get the base URL for the email
     */
    public function getData(): ViewableData
    {
        $extraData = [
            'IsEmail' => true,
            'BaseURL' => Director::absoluteBaseURL(),
        ];
        $data = clone $this->data;
        foreach ($extraData as $key => $value) {
            if (is_null($data->{$key})) {
                $data->{$key} = $value;
            }
        }
        $this->extend('updateGetData', $data);
        return $data;
    }

    /**
     * Set template data
     *
     * Calling setData() once means that any content set via text()/html()/setBody() will have no effect
     */
    public function setData(array|ViewableData $data)
    {
        if (is_array($data)) {
            $data = ArrayData::create($data);
        }
        $this->data = $data;
        $this->dataHasBeenSet = true;
        return $this;
    }

    /**
     * Add data to be used in the template
     *
     * Calling addData() once means that any content set via text()/html()/setBody() will have no effect
     *
     * @param string|array $nameOrData can be either the name to add, or an array of [name => value]
     */
    public function addData(string|array $nameOrData, mixed $value = null): static
    {
        if (is_array($nameOrData)) {
            foreach ($nameOrData as $key => $val) {
                $this->data->{$key} = $val;
            }
        } else {
            $this->data->{$nameOrData} = $value;
        }
        $this->dataHasBeenSet = true;
        return $this;
    }

    /**
     * Remove a single piece of template data
     */
    public function removeData(string $name)
    {
        $this->data->{$name} = null;
        return $this;
    }

    public function getHTMLTemplate(): string
    {
        if ($this->HTMLTemplate) {
            return $this->HTMLTemplate;
        }

        return ThemeResourceLoader::inst()->findTemplate(
            SSViewer::get_templates_by_class(static::class, '', Email::class),
            SSViewer::get_themes()
        );
    }

    /**
     * Set the template to render the email with
     */
    public function setHTMLTemplate(string $template): static
    {
        if (substr($template ?? '', -3) == '.ss') {
            $template = substr($template ?? '', 0, -3);
        }
        $this->HTMLTemplate = $template;
        return $this;
    }

    /**
     * Get the template to render the plain part with
     */
    public function getPlainTemplate(): string
    {
        return $this->plainTemplate;
    }

    /**
     * Set the template to render the plain part with
     */
    public function setPlainTemplate(string $template): static
    {
        if (substr($template ?? '', -3) == '.ss') {
            $template = substr($template ?? '', 0, -3);
        }
        $this->plainTemplate = $template;
        return $this;
    }

    /**
     * Send the message to the recipients
     */
    public function send(): void
    {
        $this->updateHtmlAndTextWithRenderedTemplates();
        Injector::inst()->get(MailerInterface::class)->send($this);
    }

    /**
     * Send the message to the recipients as plain-only
     */
    public function sendPlain(): void
    {
        $html = $this->getHtmlBody();
        $this->updateHtmlAndTextWithRenderedTemplates(true);
        $this->html(null);
        Injector::inst()->get(MailerInterface::class)->send($this);
        $this->html($html);
    }

    /**
     * Call html() and/or text() after rendering email templates
     * If either body html or text were previously explicitly set, those values will not be overwritten
     *
     * @param bool $plainOnly - if true then do not call html()
     */
    private function updateHtmlAndTextWithRenderedTemplates(bool $plainOnly = false): void
    {
        $htmlBody = $this->getHtmlBody();
        $plainBody = $this->getTextBody();

        // Ensure we can at least render something
        $htmlTemplate = $this->getHTMLTemplate();
        $plainTemplate = $this->getPlainTemplate();
        if (!$htmlTemplate && !$plainTemplate && !$plainBody && !$htmlBody) {
            return;
        }

        $htmlRender = null;
        $plainRender = null;

        if ($htmlBody && !$this->dataHasBeenSet) {
            $htmlRender = $htmlBody;
        }

        if ($plainBody && !$this->dataHasBeenSet) {
            $plainRender = $plainBody;
        }

        // Do not interfere with emails styles
        Requirements::clear();

        // Render plain
        if (!$plainRender && $plainTemplate) {
            $plainRender = $this->getData()->renderWith($plainTemplate)->Plain();
        }

        // Render HTML
        if (!$htmlRender && $htmlTemplate) {
            $htmlRender = $this->getData()->renderWith($htmlTemplate)->RAW();
        }

        // Rendering is finished
        Requirements::restore();

        // Plain render fallbacks to using the html render with html tags removed
        if (!$plainRender && $htmlRender) {
            // call html_entity_decode() to ensure any encoded HTML is also stripped inside ->Plain()
            $dbField = DBField::create_field('HTMLFragment', html_entity_decode($htmlRender));
            $plainRender = $dbField->Plain();
        }

        // Handle edge case where no template was found
        if (!$htmlRender && $htmlBody) {
            $htmlRender = $htmlBody;
        }

        if (!$plainRender && $plainBody) {
            $plainRender = $plainBody;
        }

        if ($plainRender) {
            $this->text($plainRender);
        }
        if ($htmlRender && !$plainOnly) {
            $this->html($htmlRender);
        }
    }
}
