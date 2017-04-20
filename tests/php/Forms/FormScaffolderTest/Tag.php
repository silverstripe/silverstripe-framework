<?php

namespace SilverStripe\Forms\Tests\FormScaffolderTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataObject;

class Tag extends DataObject implements TestOnly
{
    private static $table_name = 'FormScaffolderTest_Tag';

    private static $db = array(
        'Title' => 'Varchar',
    );

    private static $belongs_many_many = array(
        'Articles' => Article::class
    );

    private static $has_many = array(
        'SubjectOfArticles' => 'SilverStripe\\Forms\\Tests\\FormScaffolderTest\\Article.Subject'
    );
}
