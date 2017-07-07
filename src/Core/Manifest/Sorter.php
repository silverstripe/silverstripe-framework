<?php

namespace SilverStripe\Core\Manifest;

interface Sorter
{
    public function __construct(array $items, array $priorities);

    public function getSortedList();
}