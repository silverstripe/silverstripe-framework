<?php

namespace SilverStripe\Security\SudoMode;

use SilverStripe\Control\Session;
use SilverStripe\Core\Config\Configurable;
use SilverStripe\ORM\FieldType\DBDatetime;

class SudoModeService implements SudoModeServiceInterface
{
    use Configurable;

    /**
     * The lifetime that sudo mode authorization lasts for, in minutes.
     *
     * Note that if the PHP session times out before this lifetime is reached, it will automatically be reset.
     * @see \SilverStripe\Control\Session::$timeout
     */
    private static int $lifetime_minutes = 45;

    /**
     * The session key that is used to store the timestamp for when sudo mode was last activated
     *
     * @var string
     */
    private const SUDO_MODE_SESSION_KEY = 'sudo-mode-last-activated';

    public function check(Session $session): bool
    {
        $lastActivated = $session->get(SudoModeService::SUDO_MODE_SESSION_KEY);
        // Not activated at all
        if (!$lastActivated) {
            return false;
        }

        // Activated within the last "lifetime" window
        $nowTimestamp = DBDatetime::now()->getTimestamp();

        return $lastActivated > ($nowTimestamp - $this->getLifetime() * 60);
    }

    public function activate(Session $session): bool
    {
        $session->set(SudoModeService::SUDO_MODE_SESSION_KEY, DBDatetime::now()->getTimestamp());
        return true;
    }

    public function getLifetime(): int
    {
        return (int) static::config()->get('lifetime_minutes');
    }
}
