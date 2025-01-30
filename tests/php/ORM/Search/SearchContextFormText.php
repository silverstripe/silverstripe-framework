<?php

namespace SilverStripe\ORM\Tests\Search;

use PHPUnit\Framework\Attributes\DataProvider;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Forms\Form;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridField_FormAction;
use SilverStripe\Forms\GridField\GridFieldConfig_Base;
use SilverStripe\Forms\GridField\GridFieldFilterHeader;
use SilverStripe\ORM\Search\SearchContext;
use SilverStripe\ORM\Search\SearchContextForm;
use SilverStripe\ORM\Tests\Search\SearchContextTest;
use SilverStripe\ORM\Tests\Search\SearchContextFormTest\TestController;

class SearchContextFormText extends SapphireTest
{
    protected static $extra_dataobjects = [
        SearchContextTest\Action::class,
    ];

    public static function provideGetSchemaData(): array
    {
        return [
            'standard controller' => [
                'controllerClass' => TestController::class,
                'modelClass' => SearchContextTest\Action::class,
                // Note placeholder can only be customised with gridfields
                'placeholder' => null,
                'expected' => [
                    'formSchemaUrl' => 'my-path/SearchForm/schema',
                    'name' => 'q',
                    'filters' => new \stdClass,
                    'placeholder' => 'Search "Actions"',
                ],
            ],
            'standard controller - no general search' => [
                'controllerClass' => TestController::class,
                'modelClass' => SearchContextTest\Book::class,
                'placeholder' => null,
                'expected' => [
                    'formSchemaUrl' => 'my-path/SearchForm/schema',
                    'name' => 'Title',
                    'filters' => new \stdClass,
                    'placeholder' => 'Search "Books"',
                ],
            ],
            'gridfield as controller' => [
                'controllerClass' => GridField::class,
                'modelClass' => SearchContextTest\Action::class,
                'placeholder' => null,
                'expected' => [
                    'formSchemaUrl' => 'field/my-field/SearchForm/schema',
                    'name' => 'q',
                    'filters' => new \stdClass,
                    'placeholder' => 'Search "Actions"',
                    'gridfield' => 'my-field',
                    // Action values are dynamic and will be set in the test below.
                    'searchAction' => '',
                    'clearAction' => '',
                ],
            ],
            'gridfield as controller - no general search with custom placeholder' => [
                'controllerClass' => GridField::class,
                'modelClass' => SearchContextTest\Book::class,
                'placeholder' => 'My custom placeholder',
                'expected' => [
                    'formSchemaUrl' => 'field/my-field/SearchForm/schema',
                    'name' => 'Title',
                    'filters' => new \stdClass,
                    'placeholder' => 'My custom placeholder',
                    'gridfield' => 'my-field',
                    // Action values are dynamic and will be set in the test below.
                    'searchAction' => '',
                    'clearAction' => '',
                ],
            ],
        ];
    }

    #[DataProvider('provideGetSchemaData')]
    public function testGetSchemaData(string $controllerClass, string $modelClass, ?string $placeholder, array $expected): void
    {
        if ($controllerClass === GridField::class) {
            $controller = new GridField('my-field', config: new GridFieldConfig_Base());
            $form = new Form();
            $controller->setForm($form);
            $searchAction = new GridField_FormAction($controller, 'filter', false, 'filter', null);
            $clearAction = new GridField_FormAction($controller, 'reset', false, 'reset', null);
            $expected['searchAction'] = $searchAction->getAttribute('name');
            $expected['clearAction'] = $clearAction->getAttribute('name');
            if ($placeholder) {
                $controller->getConfig()->getComponentByType(GridFieldFilterHeader::class)->setPlaceHolderText($placeholder);
            }
        } else {
            $controller = new $controllerClass();
        }
        $form = new SearchContextForm($controller, new SearchContext($modelClass));
        $searchSchema = $form->getSchemaData();

        $this->assertEquals($expected, $searchSchema);
    }

    public function testGetSchemaDataIncludesFilters(): void
    {
        $context = new SearchContext(SearchContextTest\Action::class);
        $context->setSearchParams(['q' => 'test', 'Title' => 'some title']);
        $form = new SearchContextForm(new TestController(), $context);
        $searchSchema = $form->getSchemaData();

        $this->assertEquals(
            [
                'formSchemaUrl' => 'my-path/SearchForm/schema',
                'name' => 'q',
                'filters' => ['Search__q' => 'test', 'Search__Title' => 'some title'],
                'placeholder' => 'Search "Actions"',
            ],
            $searchSchema,
        );
    }
}
