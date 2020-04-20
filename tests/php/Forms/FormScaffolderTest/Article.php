<?php

namespace SilverStripe\Forms\Tests\FormScaffolderTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataObject;

class Article extends DataObject implements TestOnly
{
    private static $table_name = 'FormScaffolderTest_Article';

    private static $db = [
        'Title' => 'Varchar',
        'Content' => 'HTMLText'
    ];

    private static $has_one = [
        'Author' => Author::class,
        'Subject' => DataObject::class
    ];

    private static $many_many = [
        'Tags' => Tag::class,
    ];

    private static $has_many = [
        'SubjectOfArticles' => 'SilverStripe\\Forms\\Tests\\FormScaffolderTest\\Article.Subject'
    ];
}
