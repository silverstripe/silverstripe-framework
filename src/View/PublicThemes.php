<?php

namespace SilverStripe\View;

class PublicThemes implements ThemeList
{
    public function getThemes()
    {
        return ['/' . PUBLIC_DIR];
    }
}
