<?php

/**
 * Tests for FieldSet
 * 
 * @package sapphire
 * @subpackage tests
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
		
		/* A field gets added to the set */
		$fields->addFieldToTab('Root', new TextField('Country'));

		/* We have the same object as the one we pushed */
		$this->assertSame($fields->dataFieldByName('Country'), $tab->fieldByName('Country'));
		
		/* The field called Country is replaced by the field called Email */
		$fields->replaceField('Country', new EmailField('Email'));
		
		/* We have 1 field inside our tab */
		$this->assertEquals(1, $tab->Fields()->Count());		
	}

	function testReplaceAFieldInADifferentTab() {
		/* A FieldSet gets created with a TabSet and some field objects */
		$fieldSet = new FieldSet(
			new TabSet('Root', $main = new Tab('Main',
				new TextField('A'),
				new TextField('B')
			), $other = new Tab('Other',
				new TextField('C'),
				new TextField('D')
			))
		);		
		
		/* The field "A" gets added to the FieldSet we just created created */
		$fieldSet->addFieldToTab('Root.Other', $newA = new TextField('A', 'New Title'));
		
		/* The field named "A" has been removed from the Main tab to make way for our new field named "A" in Other tab. */
		$this->assertEquals(1, $main->Fields()->Count());
		$this->assertEquals(3, $other->Fields()->Count());
	}
	
	/**
	 * Test finding a field that's inside a tabset, within another tab.
	 */
	function testNestedTabsFindingFieldByName() {
		$fields = new FieldSet();
		
		/* 2 tabs get created within a TabSet inside our set */
		$tab = new TabSet('Root',
			new TabSet('Content',
				$mainTab = new Tab('Main'),
				$otherTab = new Tab('Others')
			)
		);
		$fields->push($tab);

		/* Some fields get added to the 2 tabs we just created */
		$fields->addFieldToTab('Root.Content.Main', new TextField('Country'));
		$fields->addFieldToTab('Root.Content.Others', new TextField('Email'));
		
		/* The fields we just added actually exists in the set */
		$this->assertNotNull($fields->dataFieldByName('Country'));
		$this->assertNotNull($fields->dataFieldByName('Email'));
		
		/* The fields we just added actually exist in the tabs */
		$this->assertNotNull($mainTab->fieldByName('Country'));
		$this->assertNotNull($otherTab->fieldByName('Email'));
		
		/* We have 1 field for each of the tabs */
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
		
		/* A field named Country is added to the set */
		$fields->push(new TextField('Country'));
		
		/* We only have 1 field in the set */
		$this->assertEquals(1, $fields->Count());
		
		/* Another field called Email is added to the set */
		$fields->push(new EmailField('Email'));
		
		/* There are now 2 fields in the set */
		$this->assertEquals(2, $fields->Count());
	}

	/**
	 * Test inserting a field before another in a set.
	 * 
	 * This tests {@link FieldSet->insertBefore()}.
	 */
	function testInsertBeforeFieldToSet() {
		$fields = new FieldSet();
		
		/* 3 fields are added to the set */
		$fields->push(new TextField('Country'));
		$fields->push(new TextField('Email'));
		$fields->push(new TextField('FirstName'));
		
		/* We now have 3 fields in the set */
		$this->assertEquals(3, $fields->Count());
		
		/* We insert another field called Title before the FirstName field */
		$fields->insertBefore(new TextField('Title'), 'FirstName');
		
		/* The field we just added actually exists in the set */
		$this->assertNotNull($fields->dataFieldByName('Title'));
		
		/* We now have 4 fields in the set */
		$this->assertEquals(4, $fields->Count());
		
		/* The position of the Title field is at number 3 */
		$this->assertEquals(3, $fields->fieldByName('Title')->Pos());
	}
	
	/**
	 * Test inserting a field after another in a set.
	 */
	function testInsertAfterFieldToSet() {
		$fields = new FieldSet();
		
		/* 3 fields are added to the set */
		$fields->push(new TextField('Country'));
		$fields->push(new TextField('Email'));
		$fields->push(new TextField('FirstName'));
		
		/* We now have 3 fields in the set */
		$this->assertEquals(3, $fields->Count());
		
		/* A field called Title is inserted after the Country field */
		$fields->insertAfter(new TextField('Title'), 'Country');
		
		/* The field we just added actually exists in the set */
		$this->assertNotNull($fields->dataFieldByName('Title'));
		
		/* We now have 4 fields in the FieldSet */
		$this->assertEquals(4, $fields->Count());
		
		/* The position of the Title field should be at number 2 */
		$this->assertEquals(2, $fields->fieldByName('Title')->Pos());
	}

	function testRootFieldSet() {
		/* Given a nested set of FormField, CompositeField, and FieldSet objects */
		$fieldSet = new FieldSet(
			$root = new TabSet("Root", 
				$main = new Tab("Main",
					$a = new TextField("A"),
					$b = new TextField("B")
				)
			)
		);
		
		/* rootFieldSet() should always evaluate to the same object: the topmost fieldset */		
		$this->assertSame($fieldSet, $fieldSet->rootFieldSet());
		$this->assertSame($fieldSet, $root->rootFieldSet());
		$this->assertSame($fieldSet, $main->rootFieldSet());
		$this->assertSame($fieldSet, $a->rootFieldSet());
		$this->assertSame($fieldSet, $b->rootFieldSet());
		
		/* If we push additional fields, they should also have the same rootFieldSet() */
		$root->push($other = new Tab("Other"));
		$other->push($c = new TextField("C"));
		$root->push($third = new Tab("Third", $d = new TextField("D")));

		$this->assertSame($fieldSet, $other->rootFieldSet());
		$this->assertSame($fieldSet, $third->rootFieldSet());
		$this->assertSame($fieldSet, $c->rootFieldSet());
		$this->assertSame($fieldSet, $d->rootFieldSet());
	}
	
	function testAddingDuplicateReplacesOldField() {
		/* Given a nested set of FormField, CompositeField, and FieldSet objects */
		$fieldSet = new FieldSet(
			$root = new TabSet("Root", 
				$main = new Tab("Main",
					$a = new TextField("A"),
					$b = new TextField("B")
				)
			)
		);
		
		/* Adding new fields of the same names should replace the original fields */
		$newA = new TextField("A", "New A");
		$newB = new TextField("B", "New B");
		
		$fieldSet->addFieldToTab("Root.Main", $newA);
		$fieldSet->addFieldToTab("Root.Other", $newB);

		$this->assertSame($newA, $fieldSet->dataFieldByName("A"));
		$this->assertSame($newB, $fieldSet->dataFieldByName("B"));
		$this->assertEquals(1, $main->Fields()->Count());
		
		/* Pushing fields on the end of the field set should remove them from the tab */
		$thirdA = new TextField("A", "Third A");
		$thirdB = new TextField("B", "Third B");
		$fieldSet->push($thirdA);
		$fieldSet->push($thirdB);

		$this->assertSame($thirdA, $fieldSet->fieldByName("A"));
		$this->assertSame($thirdB, $fieldSet->fieldByName("B"));
		
		$this->assertEquals(0, $main->Fields()->Count());
	}

	function testAddingFieldToNonExistentTabCreatesThatTab() {
		$fieldSet = new FieldSet(
			$root = new TabSet("Root", 
				$main = new Tab("Main",
					$a = new TextField("A")
				)
			)
		);
		
		$fieldSet->addFieldToTab("Root.Other", $b = new TextField("B"));
		$this->assertSame($b, $fieldSet->fieldByName('Root')->fieldByName('Other')->Fields()->First());
	}
	
	/**
	 * @TODO the same as above with insertBefore() and insertAfter()
	 */
	
}

?>