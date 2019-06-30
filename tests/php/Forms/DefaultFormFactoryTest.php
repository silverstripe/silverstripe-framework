<?php declare(strict_types = 1);

namespace SilverStripe\Forms\Tests;

use InvalidArgumentException;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Forms\DefaultFormFactory;
use SilverStripe\ORM\DataObject;

class DefaultFormFactoryTest extends SapphireTest
{
    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessageRegExp /Missing required context/
     */
    public function testGetFormThrowsExceptionOnMissingContext()
    {
        $factory = new DefaultFormFactory();
        $factory->getForm();
    }

    public function testGetForm()
    {
        $record = new DataObject();
        $record->Title = 'Test';

        $factory = new DefaultFormFactory();
        $form = $factory->getForm(null, null, ['Record' => $record]);

        $this->assertSame($record, $form->getRecord());
    }

    public function testGetRequiredContext()
    {
        $factory = new DefaultFormFactory();
        $this->assertContains('Record', $factory->getRequiredContext());
    }
}
