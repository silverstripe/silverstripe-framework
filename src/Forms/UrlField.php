<?php

namespace SilverStripe\Forms;

use SilverStripe\Core\Validation\ConstraintValidator;
use Symfony\Component\Validator\Constraints\Url;

/**
 * Text input field with validation for a url
 * Url must include a protocol (aka scheme) such as https:// or http://
 */
class UrlField extends TextField
{
    /**
     * The default set of protocols allowed for valid URLs
     */
    private static array $default_protocols = ['https', 'http'];

    /**
     * The default value for whether a relative protocol (// on its own) is allowed
     */
    private static bool $default_allow_relative_protocol = false;

    private array $protocols = [];

    private ?bool $allowRelativeProtocol = null;

    public function Type()
    {
        return 'text url';
    }

    public function validate($validator)
    {
        $allowedProtocols = $this->getAllowedProtocols();
        $message = _t(
            __CLASS__ . '.INVALID_WITH_PROTOCOL',
            'Please enter a valid URL including a protocol, e.g {protocol}://example.com',
            ['protocol' => $allowedProtocols[0]]
        );
        $result = ConstraintValidator::validate(
            $this->value,
            new Url(
                message: $message,
                protocols: $allowedProtocols,
                relativeProtocol: $this->getAllowRelativeProtocol()
            ),
            $this->getName()
        );
        $validator->getResult()->combineAnd($result);
        $isValid = $result->isValid();
        return $this->extendValidationResult($isValid, $validator);
    }

    /**
     * Set which protocols valid URLs are allowed to have.
     * Passing an empty array will result in using configured defaults.
     */
    public function setAllowedProtocols(array $protocols): static
    {
        $this->protocols = $protocols;
        return $this;
    }

    /**
     * Get which protocols valid URLs are allowed to have
     */
    public function getAllowedProtocols(): array
    {
        $protocols = $this->protocols;
        if (empty($protocols)) {
            $protocols = static::config()->get('default_protocols');
        }
        // Ensure the array isn't associative so we can use 0 index in validate().
        return array_values($protocols);
    }

    /**
     * Set whether a relative protocol (// on its own) is allowed
     */
    public function setAllowRelativeProtocol(?bool $allow): static
    {
        $this->allowRelativeProtocol = $allow;
        return $this;
    }

    /**
     * Get whether a relative protocol (// on its own) is allowed
     */
    public function getAllowRelativeProtocol(): bool
    {
        if ($this->allowRelativeProtocol === null) {
            return static::config()->get('default_allow_relative_protocol');
        }
        return $this->allowRelativeProtocol;
    }
}
