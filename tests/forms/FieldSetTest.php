<?php

/**
 * Tests for FieldSet
 * 
 * @package sapphire
 * @subpackage tests
 * 
 * @todo test for {@link FieldSet->setValues()}. Need to check
 * 	that the values that were set are the correct ones given back.
 * @todo test for {@link FieldSet->transform()} and {@link FieldSet->makeReadonly()}.
 *  Need to ensure that it correctly transforms the FieldSet object.
 * @todo test for {@link FieldSet->HiddenFields()}. Need to check
 * 	the fields returned are the correct HiddenField objects for a
 * 	given FieldSet instance.
 * @todo test for {@link FieldSet->dataFields()}.
 * @todo test for {@link FieldSet->findOrMakeTab()}.
 * @todo the same as above with insertBefore() and insertAfter()
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
	
	function testRemoveTab() {
		$fields = new FieldSet(new TabSet(
			'Root',
			$tab1 = new Tab('Tab1'),
			$tab2 = new Tab('Tab2'),
			$tab3 = new Tab('Tab3')
		));
		
		$fields->removeByName('Tab2');
		$this->assertNull($fields->fieldByName('Root')->fieldByName('Tab2'));
		
		$this->assertEquals($tab1, $fields->fieldByName('Root')->fieldByName('Tab1'));
	}
	
	function testHasTabSet() {
		$untabbedFields = new FieldSet(
			new TextField('Field1')
		);
		$this->assertFalse($untabbedFields->hasTabSet());
		
		$tabbedFields = new FieldSet(
			new TabSet('Root',
				new Tab('Tab1')
			)
		);
		$this->assertTrue($tabbedFields->hasTabSet());
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
	
	function testRenameField() {
		$fields = new FieldSet();
		$nameField = new TextField('Name', 'Before title');
		$fields->push($nameField);
		
		/* The title of the field object is the same as what we put in */
		$this->assertSame('Before title', $nameField->Title());
		
		/* The field gets renamed to a different title */
		$fields->renameField('Name', 'After title');
		
		/* The title of the field object is the title we renamed to, this
			includes the original object we created ($nameField), and getting
			the field back out of the set */
		$this->assertSame('After title', $nameField->Title());
		$this->assertSame('After title', $fields->dataFieldByName('Name')->Title());
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
		
		$this->assertNotNull($fields->fieldByName('Root.Content'));
		$this->assertNotNull($fields->fieldByName('Root.Content.Main'));
	}
	
	function testTabTitles() {
		$set = new FieldSet(
			$rootTabSet = new TabSet('Root',
				$tabSetWithoutTitle = new TabSet('TabSetWithoutTitle'),
				$tabSetWithTitle = new TabSet('TabSetWithTitle', 'My TabSet Title',
					new Tab('ExistingChildTab')
				)
			)
		);
		
		$this->assertEquals(
			$tabSetWithTitle->Title(),
			'My TabSet Title',
			'Automatic conversion of tab identifiers through findOrMakeTab() with FormField::name_to_label()'
		);
		
		$tabWithoutTitle = $set->findOrMakeTab('Root.TabWithoutTitle');
		$this->assertEquals(
			$tabWithoutTitle->Title(),
			'Tab Without Title',
			'Automatic conversion of tab identifiers through findOrMakeTab() with FormField::name_to_label()'
		);
		
		$tabWithTitle = $set->findOrMakeTab('Root.TabWithTitle', 'My Tab with Title');
		$this->assertEquals(
			$tabWithTitle->Title(),
			'My Tab with Title',
			'Setting of simple tab titles through findOrMakeTab()'
		);
		
		$childTabWithTitle = $set->findOrMakeTab('Root.TabSetWithoutTitle.NewChildTab', 'My Child Tab Title');
		$this->assertEquals(
			$childTabWithTitle->Title(),
			'My Child Tab Title',
			'Setting of nested tab titles through findOrMakeTab() works on last child tab'
		);
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
		
		// Test that pushing a composite field without a name onto the set works
		// See ticket #2932
		$fields->push(new CompositeField(
			new TextField('Test1'),
			new TextField('Test2')
		));
		$this->assertEquals(3, $fields->Count());
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

		/* Add a field to a non-existent tab, and it will be created */
		$fieldSet->addFieldToTab("Root.Other", $b = new TextField("B"));
		$this->assertNotNull($fieldSet->fieldByName('Root')->fieldByName('Other'));
		$this->assertSame($b, $fieldSet->fieldByName('Root')->fieldByName('Other')->Fields()->First());
	}

	function testAddingFieldToATabWithTheSameNameAsTheField() {
		$fieldSet = new FieldSet(
			$root = new TabSet("Root", 
				$main = new Tab("Main",
					$a = new TextField("A")
				)
			)
		);

		/* If you have a tab with the same name as the field, then technically it's a duplicate. However, it's allowed because
		tab isn't a data field.  Only duplicate data fields are problematic */
		$fieldSet->addFieldToTab("Root.MyName", $myName = new TextField("MyName"));
		$this->assertNotNull($fieldSet->fieldByName('Root')->fieldByName('MyName'));
		$this->assertSame($myName, $fieldSet->fieldByName('Root')->fieldByName('MyName')->Fields()->First());
	}
	
	function testInsertBeforeWithNestedCompositeFields() {
		$fieldSet = new FieldSet(
			new TextField('A_pre'),
			new TextField('A'),
			new TextField('A_post'),
			$compositeA = new CompositeField(
				new TextField('B_pre'),
				new TextField('B'),
				new TextField('B_post'),
				$compositeB = new CompositeField(
					new TextField('C_pre'),
					new TextField('C'),
					new TextField('C_post')
				)
			)
		);
		
		$fieldSet->insertBefore(
			$A_insertbefore = new TextField('A_insertbefore'),
			'A'
		);
		$this->assertSame(
			$A_insertbefore,
			$fieldSet->dataFieldByName('A_insertbefore'),
			'Field on toplevel fieldset can be inserted'
		);
		
		$fieldSet->insertBefore(
			$B_insertbefore = new TextField('B_insertbefore'),
			'B'
		);
		$this->assertSame(
			$fieldSet->dataFieldByName('B_insertbefore'),
			$B_insertbefore,
			'Field on one nesting level fieldset can be inserted'
		);
		
		$fieldSet->insertBefore(
			$C_insertbefore = new TextField('C_insertbefore'),
			'C'
		);
		$this->assertSame(
			$fieldSet->dataFieldByName('C_insertbefore'),
			$C_insertbefore,
			'Field on two nesting levels fieldset can be inserted'
		);
	}
	
	/**
	 * @todo check actual placement of fields
	 */
	function testInsertBeforeWithNestedTabsets() {
		$fieldSetA = new FieldSet(
			$tabSetA = new TabSet('TabSet_A',
				$tabA1 = new Tab('Tab_A1',
					new TextField('A_pre'),
					new TextField('A'),
					new TextField('A_post')
				),
				$tabB1 = new Tab('Tab_B1',
					new TextField('B')
				)
			)
		);
		$tabSetA->insertBefore(
			$A_insertbefore = new TextField('A_insertbefore'),
			'A'
		);
		$this->assertEquals(
			$fieldSetA->dataFieldByName('A_insertbefore'),
			$A_insertbefore,
			'Field on toplevel tab can be inserted'
		);
		
		$this->assertEquals(0, $tabA1->fieldPosition('A_pre'));
		$this->assertEquals(1, $tabA1->fieldPosition('A_insertbefore'));
		$this->assertEquals(2, $tabA1->fieldPosition('A'));
		$this->assertEquals(3, $tabA1->fieldPosition('A_post'));

		$fieldSetB = new FieldSet(
			new TabSet('TabSet_A',
				$tabsetB = new TabSet('TabSet_B',
					$tabB1 = new Tab('Tab_B1',
						new TextField('C')
					),
					$tabB2 = new Tab('Tab_B2',
						new TextField('B_pre'),
						new TextField('B'),
						new TextField('B_post')
					)
				)
			)
		);
		$fieldSetB->insertBefore(
			$B_insertbefore = new TextField('B_insertbefore'),
			'B'
		);
		$this->assertSame(
			$fieldSetB->dataFieldByName('B_insertbefore'),
			$B_insertbefore,
			'Field on nested tab can be inserted'
		);
		$this->assertEquals(0, $tabB2->fieldPosition('B_pre'));
		$this->assertEquals(1, $tabB2->fieldPosition('B_insertbefore'));
		$this->assertEquals(2, $tabB2->fieldPosition('B'));
		$this->assertEquals(3, $tabB2->fieldPosition('B_post'));
	}
	
	function testInsertAfterWithNestedCompositeFields() {
		$fieldSet = new FieldSet(
			new TextField('A_pre'),
			new TextField('A'),
			new TextField('A_post'),
			$compositeA = new CompositeField(
				new TextField('B_pre'),
				new TextField('B'),
				new TextField('B_post'),
				$compositeB = new CompositeField(
					new TextField('C_pre'),
					new TextField('C'),
					new TextField('C_post')
				)
			)
		);
		
		$fieldSet->insertAfter(
			$A_insertafter = new TextField('A_insertafter'),
			'A'
		);
		$this->assertSame(
			$A_insertafter,
			$fieldSet->dataFieldByName('A_insertafter'),
			'Field on toplevel fieldset can be inserted after'
		);
		
		$fieldSet->insertAfter(
			$B_insertafter = new TextField('B_insertafter'),
			'B'
		);
		$this->assertSame(
			$fieldSet->dataFieldByName('B_insertafter'),
			$B_insertafter,
			'Field on one nesting level fieldset can be inserted after'
		);
		
		$fieldSet->insertAfter(
			$C_insertafter = new TextField('C_insertafter'),
			'C'
		);
		$this->assertSame(
			$fieldSet->dataFieldByName('C_insertafter'),
			$C_insertafter,
			'Field on two nesting levels fieldset can be inserted after'
		);
	}
	
	/**
	 * @todo check actual placement of fields
	 */
	function testInsertAfterWithNestedTabsets() {
		$fieldSetA = new FieldSet(
			$tabSetA = new TabSet('TabSet_A',
				$tabA1 = new Tab('Tab_A1',
					new TextField('A_pre'),
					new TextField('A'),
					new TextField('A_post')
				),
				$tabB1 = new Tab('Tab_B1',
					new TextField('B')
				)
			)
		);
		$tabSetA->insertAfter(
			$A_insertafter = new TextField('A_insertafter'),
			'A'
		);
		$this->assertEquals(
			$fieldSetA->dataFieldByName('A_insertafter'),
			$A_insertafter,
			'Field on toplevel tab can be inserted after'
		);
		$this->assertEquals(0, $tabA1->fieldPosition('A_pre'));
		$this->assertEquals(1, $tabA1->fieldPosition('A'));
		$this->assertEquals(2, $tabA1->fieldPosition('A_insertafter'));
		$this->assertEquals(3, $tabA1->fieldPosition('A_post'));

		$fieldSetB = new FieldSet(
			new TabSet('TabSet_A',
				$tabsetB = new TabSet('TabSet_B',
					$tabB1 = new Tab('Tab_B1',
						new TextField('C')
					),
					$tabB2 = new Tab('Tab_B2',
						new TextField('B_pre'),
						new TextField('B'),
						new TextField('B_post')
					)
				)
			)
		);
		$fieldSetB->insertAfter(
			$B_insertafter = new TextField('B_insertafter'),
			'B'
		);
		$this->assertSame(
			$fieldSetB->dataFieldByName('B_insertafter'),
			$B_insertafter,
			'Field on nested tab can be inserted after'
		);
		$this->assertEquals(0, $tabB2->fieldPosition('B_pre'));
		$this->assertEquals(1, $tabB2->fieldPosition('B'));
		$this->assertEquals(2, $tabB2->fieldPosition('B_insertafter'));
		$this->assertEquals(3, $tabB2->fieldPosition('B_post'));
	}
	
	function testFieldPosition() {
		$set = new FieldSet(
			new TextField('A'),
			new TextField('B'),
			new TextField('C')
		);
		
		$this->assertEquals(0, $set->fieldPosition('A'));
		$this->assertEquals(1, $set->fieldPosition('B'));
		$this->assertEquals(2, $set->fieldPosition('C'));
		
		$set->insertBefore(new TextField('AB'), 'B');
		$this->assertEquals(0, $set->fieldPosition('A'));
		$this->assertEquals(1, $set->fieldPosition('AB'));
		$this->assertEquals(2, $set->fieldPosition('B'));
		$this->assertEquals(3, $set->fieldPosition('C'));
		
		unset($set);
	}
	
	function testMakeFieldReadonly() {
		$fieldSet = new FieldSet(
			new TabSet('Root', new Tab('Main',
				new TextField('A'),
				new TextField('B')
			)
		));
		
		$fieldSet->makeFieldReadonly('A');
		$this->assertTrue(
			$fieldSet->dataFieldByName('A')->isReadonly(),
			'Field nested inside a TabSet and FieldSet can be marked readonly by FieldSet->makeFieldReadonly()'
		);
	}
}
?>