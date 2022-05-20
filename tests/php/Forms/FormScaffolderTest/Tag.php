<?php

namespace SilverStripe\Forms\Tests\FormScaffolderTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataObject;

class Tag extends DataObject implements TestOnly
{
    private static $table_name = 'FormScaffolderTest_Tag';

    private static $db = [
        'Title' => 'Varchar',
    ];

    private static $belongs_many_many = [
        'Articles' => Article::class
    ];

    private static $has_many = [
        'SubjectOfArticles' => 'SilverStripe\\Forms\\Tests\\FormScaffolderTest\\Article.Subject'
    ];
}
