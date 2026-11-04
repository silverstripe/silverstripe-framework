<?php

namespace SilverStripe\Forms\Tests\GridField;

use PHPUnit\Framework\Attributes\DataProvider;
use SilverStripe\Dev\CSSContentParser;
use SilverStripe\Dev\FunctionalTest;
use SilverStripe\Forms\Tests\GridField\GridFieldDetailFormCreatePermissionTest\CreateRestrictedController;
use SilverStripe\Forms\Tests\GridField\GridFieldDetailFormCreatePermissionTest\CreateRestrictedObject;

class GridFieldDetailFormCreatePermissionTest extends FunctionalTest
{
    protected static $extra_dataobjects = [
        CreateRestrictedObject::class,
    ];

    protected static $extra_controllers = [
        CreateRestrictedController::class,
    ];

    protected static $disable_themes = true;

    public static function provideDoSaveNewRecord(): array
    {
        return [
            'member without create permission' => [
                'permission' => 'CMS_ACCESS_LeftAndMain',
                'expectedStatusCode' => 403,
                'expectedNumCreated' => 0,
            ],
            'member with create permission' => [
                'permission' => CreateRestrictedObject::CREATE_PERMISSION,
                'expectedStatusCode' => 200,
                'expectedNumCreated' => 1,
            ],
        ];
    }

    /**
     * The row count is asserted against every row in the table rather than against rows matching the
     * posted title, because the form a member without create permission is served is readonly and so
     * posts no field values - a bypassed create writes a row with a blank title, which a title filter
     * would not see.
     */
    #[DataProvider('provideDoSaveNewRecord')]
    public function testDoSaveNewRecord(string $permission, int $expectedStatusCode, int $expectedNumCreated): void
    {
        $this->logInWithPermission($permission);
        $formUrl = $this->getItemEditFormUrl('new');
        $title = 'Created via a crafted doSave submission';
        $numRecordsBefore = CreateRestrictedObject::get()->count();

        $response = $this->post($formUrl, ['Title' => $title, 'action_doSave' => 1]);

        $numCreated = CreateRestrictedObject::get()->count() - $numRecordsBefore;
        $this->assertSame($expectedNumCreated, $numCreated);
        $this->assertSame($expectedStatusCode, $response->getStatusCode());
    }

    /**
     * Editing an existing record only requires canEdit(), so the create gate must not
     * interfere with it.
     */
    public function testDoSaveExistingRecord(): void
    {
        $this->logInWithPermission('CMS_ACCESS_LeftAndMain');
        $record = new CreateRestrictedObject(['Title' => 'Original title']);
        $record->write();
        $formUrl = $this->getItemEditFormUrl($record->ID . '/edit');

        $response = $this->post($formUrl, ['Title' => 'Edited title', 'action_doSave' => 1]);

        $this->assertFalse($response->isError());
        $this->assertSame('Edited title', CreateRestrictedObject::get()->byID($record->ID)->Title);
    }

    /**
     * Get the URL the ItemEditForm posts back to, as a browser would from the rendered form.
     */
    private function getItemEditFormUrl(string $item): string
    {
        $url = 'GridFieldDetailFormCreatePermissionTest_Controller/Form/field/testfield/item/' . $item;
        $response = $this->get($url);
        $this->assertFalse($response->isError());
        $parser = new CSSContentParser($response->getBody());
        $form = $parser->getBySelector('#Form_ItemEditForm');
        return (string) $form[0]['action'];
    }
}
