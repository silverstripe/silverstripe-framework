<?php

namespace SilverStripe\View;

class PublicThemes implements ThemeList
{
    public function getThemes(): array
    {
        return PUBLIC_DIR ? ['/' . PUBLIC_DIR] : [];
    }
}
