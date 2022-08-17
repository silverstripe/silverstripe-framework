<?php

namespace SilverStripe\Security\Confirmation;

/**
 * Confirmation item is a simple data object
 * incapsulating a single confirmation unit,
 * its unique identifier (token), its human
 * friendly name, description and the status
 * whether it has already been confirmed.
 */
class Item
{
    /**
     * A confirmation token which is
     * unique for every confirmation item
     *
     * @var string
     */
    private $token;

    /**
     * Human readable item name
     *
     * @var string
     */
    private $name;

    /**
     * Human readable description of the item
     *
     * @var string
     */
    private $description;

    /**
     * Whether the item has been confirmed or not
     *
     * @var bool
     */
    private $confirmed;

    /**
     * @param string $token unique token of this confirmation item
     * @param string $name Human readable name of the item
     * @param string $description Human readable description of the item
     */
    public function __construct(string $token, string $name, string $description): void
    {
        $this->token = $token;
        $this->name = $name;
        $this->description = $description;
        $this->confirmed = false;
    }

    /**
     * Returns the token of the item
     *
     * @return string
     */
    public function getToken(): string
    {
        return $this->token;
    }

    /**
     * Returns the item name (human readable)
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Returns the human readable description of the item
     *
     * @return string
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * Returns whether the item has been confirmed
     *
     * @return bool
     */
    public function isConfirmed(): bool
    {
        return $this->confirmed;
    }

    /**
     * Mark the item as confirmed
     */
    public function confirm(): void
    {
        $this->confirmed = true;
    }
}
