<?php

/**
 * @package tests
 */

/**
 * Tests for FieldSet
 * @package tests
 * 
 * @TODO test for {@link FieldSet->insertBeforeRecursive()}.
 * 
 * @TODO test for {@link FieldSet->setValues()}. Need to check
 * that the values that were set are the correct ones given back.
 *
 * @TODO test for {@link FieldSet->transform()} and {@link FieldSet->makeReadonly()}.
 * Need to ensure that it correctly transforms the FieldSet object.
 *
 * @TODO test for {@link FieldSet->HiddenFields()}. Need to check
 * the fields returned are the correct HiddenField objects for a
 * given FieldSet instance.
 * 
 * @TODO test for {@link FieldSet->dataFields()}.
 * 
 * @TODO test for {@link FieldSet->findOrMakeTab()}.
 *
 */
class FieldSetTest extends SapphireTest {

	/**
	 * Test adding a field to a tab in a set.
	 */
	function testAddFieldToTab() {
		$fields = new FieldSet();
		$tab = new Tab('Root');
		$fields->push($tab);
		
		/* We add field objects to the FieldSet, using two different methods */
		$fields->addFieldToTab('Root', new TextField('Country'));
		$fields->addFieldsToTab('Root', array(
			new EmailField('Email'),
			new TextField('Name'),
		));
		
		/* Check that the field objects were created */
		$this->assertNotNull($fields->dataFieldByName('Country'));
		$this->assertNotNull($fields->dataFieldByName('Email'));
		$this->assertNotNull($fields->dataFieldByName('Name'));
		
		/* The field objects in the set should be the same as the ones we created */
		$this->assertSame($fields->dataFieldByName('Country'), $tab->fieldByName('Country'));
		$this->assertSame($fields->dataFieldByName('Email'), $tab->fieldByName('Email'));
		$this->assertSame($fields->dataFieldByName('Name'), $tab->fieldByName('Name'));
		
		/* We'll have 3 fields inside the tab */
		$this->assertEquals(3, $tab->Fields()->Count());
	}
		
	/**
	 * Test removing a single field from a tab in a set.
	 */
	function testRemoveSingleFieldFromTab() {
		$fields = new FieldSet();
		$tab = new Tab('Root');
		$fields->push($tab);

		/* We add a field to the "Root" tab */
		$fields->addFieldToTab('Root', new TextField('Country'));
		
		/* We have 1 field inside the tab, which is the field we just created */
		$this->assertEquals(1, $tab->Fields()->Count());
		
		/* We remove the field from the tab */
		$fields->removeFieldFromTab('Root', 'Country');
		
		/* We'll have no fields in the tab now */
		$this->assertEquals(0, $tab->Fields()->Count());
	}
	
	/**
	 * Test removing an array of fields from a tab in a set.
	 */
	function testRemoveMultipleFieldsFromTab() {
		$fields = new FieldSet();
		$tab = new Tab('Root');
		$fields->push($tab);
		
		/* We add an array of fields, using addFieldsToTab() */
		$fields->addFieldsToTab('Root', array(
			new TextField('Name', 'Your name'),
			new EmailField('Email', 'Email address'),
			new NumericField('Number', 'Insert a number')
		));
		
		/* We have 3 fields inside the tab, which we just created */
		$this->assertEquals(3, $tab->Fields()->Count());
		
		/* We remove the 3 fields from the tab */
		$fields->removeFieldsFromTab('Root', array(
			'Name',
			'Email',
			'Number'
		));
		
		/* We have no fields in the tab now */
		$this->assertEquals(0, $tab->Fields()->Count());
	}
	
	/**
	 * Test removing a field from a set by it's name.
	 */
	function testRemoveFieldByName() {
		$fields = new FieldSet();
		
		/* First of all, we add a field into our FieldSet object */
		$fields->push(new TextField('Name', 'Your name'));
		
		/* We have 1 field in our set now */
		$this->assertEquals(1, $fields->Count());
		
		/* Then, we call up removeByName() to take it out again */
		$fields->removeByName('Name');
		
		/* We have 0 fields in our set now, as we've just removed the one we added */
		$this->assertEquals(0, $fields->Count());
	}
	
	/**
	 * Test replacing a field with another one.
	 */
	function testReplaceField() {
		$fields = new FieldSet();
		$tab = new Tab('Root');
		$fields->push($tab);
		
		/* We add a field object to the FieldSet */
		$fields->addFieldToTab('Root', new TextField('Country'));

		/* We have the same object we added to the tab, as the one we get back from it */
		$this->assertSame($fields->dataFieldByName('Country'), $tab->fieldByName('Country'));
		
		/* We replace the "Country" field object with an "Email" field object */
		$fields->replaceField('Country', new EmailField('Email'));
		
		/* We'll be left with one (1) field inside the tab */
		$this->assertEquals(1, $tab->Fields()->Count());		
	}
	
	/**
	 * Test finding a field that's inside a tabset, within another tab.
	 */
	function testNestedTabsFindingFieldByName() {
		$fields = new FieldSet();
		
		// Create 2 nested TabSet objects, and two Tab objects inside the nested TabSet
		$tab = new TabSet('Root',
			new TabSet('Content',
				$mainTab = new Tab('Main'),
				$otherTab = new Tab('Others')
			)
		);
		$fields->push($tab);

		// Create a field inside each of the Tab objects
		$fields->addFieldToTab('Root.Content.Main', new TextField('Country'));
		$fields->addFieldToTab('Root.Content.Others', new TextField('Email'));
		
		// Ensure the field object in the FieldSet is not null
		$this->assertNotNull($fields->dataFieldByName('Country'));
		$this->assertNotNull($fields->dataFieldByName('Email'));
		
		// Ensure the field objects inside the Tab objects are not null
		$this->assertNotNull($mainTab->fieldByName('Country'));
		$this->assertNotNull($otherTab->fieldByName('Email'));
		
		// Ensure that there is only 1 field in each tab
		$this->assertEquals(1, $mainTab->Fields()->Count());
		$this->assertEquals(1, $otherTab->Fields()->Count());
	}
	
	/**
	 * Test pushing a field to a set.
	 * 
	 * This tests {@link FieldSet->push()}.
	 */
	function testPushFieldToSet() {
		$fields = new FieldSet();
		
		// Ensure that there are no fields in the set at this time
		$this->assertEquals(0, $fields->Count());
		
		// Push a field into this set
		$fields->push(new TextField('Country'));
		
		// Ensure that there is only 1 field in the set
		$this->assertEquals(1, $fields->Count());
		
		$fields->push(new EmailField('Email'));
		
		// Ensure that there are 2 fields in the set
		$this->assertEquals(2, $fields->Count());
	}

	/**
	 * Test inserting a field before another in a set.
	 * 
	 * This tests {@link FieldSet->insertBefore()}.
	 */
	function testInsertBeforeFieldToSet() {
		$fields = new FieldSet();
		
		// Push some field objects into the FieldSet object
		$fields->push(new TextField('Country'));
		$fields->push(new TextField('Email'));
		$fields->push(new TextField('FirstName'));
		
		// Ensure that there are 3 fields in the FieldSet
		$this->assertEquals(3, $fields->Count());
		
		// Insert a new field object before the "FirstName" field in the set
		$fields->insertBefore(new TextField('Title'), 'FirstName');
		
		// Check the field was actually inserted into the FieldSet
		$this->assertNotNull($fields->dataFieldByName('Title'));
		
		// Ensure that there are now 4 fields in the FieldSet
		$this->assertEquals(4, $fields->Count());
		
		// Check the position of the "Title" field that we inserted
		$this->assertEquals(3, $fields->fieldByName('Title')->Pos());
	}
	
	/**
	 * Test inserting a field after another in a set.
	 */
	function testInsertAfterFieldToSet() {
		$fields = new FieldSet();
		
		// Push some field objects into the FieldSet object
		$fields->push(new TextField('Country'));
		$fields->push(new TextField('Email'));
		$fields->push(new TextField('FirstName'));
		
		// Ensure that there are 3 fields in the FieldSet
		$this->assertEquals(3, $fields->Count());
		
		// Insert a new field object before the "FirstName" field in the set
		$fields->insertAfter(new TextField('Title'), 'Country');
		
		// Check the field was actually inserted into the FieldSet
		$this->assertNotNull($fields->dataFieldByName('Title'));
		
		// Ensure that there are now 4 fields in the FieldSet
		$this->assertEquals(4, $fields->Count());
		
		// Check the position of the "Title" field that we inserted
		$this->assertEquals(2, $fields->fieldByName('Title')->Pos());
	}
	
	/**
	 * @TODO test pushing a field replacing an existing one. (duplicate)
	 * @TODO the same as above with insertBefore() and insertAfter()
	 */
	
}

?>