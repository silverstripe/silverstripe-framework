<?php declare(strict_types = 1);

namespace SilverStripe\View;

class PublicThemes implements ThemeList
{
    public function getThemes()
    {
        return PUBLIC_DIR ? ['/' . PUBLIC_DIR] : [];
    }
}
