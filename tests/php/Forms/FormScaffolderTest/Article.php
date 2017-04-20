<?php

namespace SilverStripe\Forms\Tests\FormScaffolderTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataObject;

class Article extends DataObject implements TestOnly
{
    private static $table_name = 'FormScaffolderTest_Article';

    private static $db = array(
        'Title' => 'Varchar',
        'Content' => 'HTMLText'
    );

    private static $has_one = array(
        'Author' => Author::class,
        'Subject' => DataObject::class
    );

    private static $many_many = array(
        'Tags' => Tag::class,
    );

    private static $has_many = array(
        'SubjectOfArticles' => 'SilverStripe\\Forms\\Tests\\FormScaffolderTest\\Article.Subject'
    );
}
