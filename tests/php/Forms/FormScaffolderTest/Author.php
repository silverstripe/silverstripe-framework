<?php

namespace SilverStripe\Forms\Tests\FormScaffolderTest;

use SilverStripe\Assets\Image;
use SilverStripe\Dev\TestOnly;
use SilverStripe\Security\Member;

class Author extends Member implements TestOnly
{
    private static $table_name = 'FormScaffolderTest_Author';

    private static $has_one = array(
        'ProfileImage' => Image::class
    );

    private static $has_many = array(
        'Articles' => 'SilverStripe\\Forms\\Tests\\FormScaffolderTest\\Article.Author',
        'SubjectOfArticles' => 'SilverStripe\\Forms\\Tests\\FormScaffolderTest\\Article.Subject'
    );
}
