<?php

namespace SilverStripe\Forms;

/**
 * Declares that a form field has the ability to accept and render Tips.
 */
interface TippableFieldInterface
{
    public function getTip(): ?Tip;

    public function setTip(Tip $tip);
}
