<?php

namespace SilverStripe\View;

use IteratorAggregate;

/**
 * A special iterator type used by the template engine. The template engine
 * needs to know the total number of items before iterating (to save rewinding
 * database queries), which is impossible with a natural Generator, so this
 * interface allows implementors to pre-compute the total number of items
 */
interface TemplateIterator extends IteratorAggregate
{
    /**
     * @return Iterator|Generator
     */
    public function getTemplateIterator();

    /**
     * @return int
     */
    public function getTemplateIteratorCount();
}
