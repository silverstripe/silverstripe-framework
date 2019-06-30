<?php declare(strict_types = 1);

namespace SilverStripe\Forms\Tests;

use SilverStripe\Forms\Tests\CheckboxSetFieldTest\MultiEnumArticle;
use SilverStripe\ORM\DB;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Control\Controller;
use SilverStripe\Forms\CheckboxSetField;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\Form;
use SilverStripe\ORM\Connect\MySQLDatabase;

class CheckboxSetFieldMulitEnumTest extends SapphireTest
{

    protected $usesDatabase = true;

    public static function getExtraDataObjects()
    {
        // Don't add this for other database
        if (DB::get_conn() instanceof MySQLDatabase) {
            return [
                MultiEnumArticle::class,
            ];
        }
    }

    public function setUp()
    {
        if (!(DB::get_conn() instanceof MySQLDatabase)) {
            $this->markTestSkipped('DBMultiEnum only supported by MySQL');
            return;
        }
        parent::setUp();
    }

    public function tearDown()
    {
        if (!(DB::get_conn() instanceof MySQLDatabase)) {
            return;
        }
        parent::tearDown();
    }

    public function testLoadDataFromMultiEnum()
    {
        $article = new MultiEnumArticle();
        $article->Colours = 'Red,Green';

        $field = new CheckboxSetField(
            'Colours',
            'Colours',
            [
                'Red' => 'Red',
                'Blue' => 'Blue',
                'Green' => 'Green',
            ]
        );

        $form = new Form(
            Controller::curr(),
            'Form',
            new FieldList($field),
            new FieldList()
        );
        $form->loadDataFrom($article);
        $value = $field->Value();
        $this->assertEquals(['Red', 'Green'], $value);
    }

    public function testSavingIntoMultiEnum()
    {
        $field = new CheckboxSetField(
            'Colours',
            'Colours',
            [
                'Red' => 'Red',
                'Blue' => 'Blue',
                'Green' => 'Green',
            ]
        );
        $article = new MultiEnumArticle();
        $field->setValue(array('Red' => 'Red', 'Blue' => 'Blue'));
        $field->saveInto($article);
        $article->write();

        $dbValue = DB::query(
            sprintf(
                'SELECT "Colours" FROM "CheckboxSetFieldTest_MultiEnumArticle" WHERE "ID" = %s',
                $article->ID
            )
        )->value();

        // JSON encoded values
        $this->assertEquals('Red,Blue', $dbValue);
    }
}
