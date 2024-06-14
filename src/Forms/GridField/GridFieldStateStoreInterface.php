<?php

namespace SilverStripe\Forms\GridField;

interface GridFieldStateStoreInterface
{
    public function storeState(GridField $gridField, $value = null);

    public function getStateRequestVar(): string;
}
