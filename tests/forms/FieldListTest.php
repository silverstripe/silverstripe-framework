<?php

/**
 * Tests for FieldList
 *
 * @package framework
 * @subpackage tests
 *
 * @todo test for {@link FieldList->setValues()}. Need to check
 * 	that the values that were set are the correct ones given back.
 * @todo test for {@link FieldList->transform()} and {@link FieldList->makeReadonly()}.
 *  Need to ensure that it correctly transforms the FieldList object.
 * @todo test for {@link FieldList->HiddenFields()}. Need to check
 * 	the fields returned are the correct HiddenField objects for a
 * 	given FieldList instance.
 * @todo test for {@link FieldList->dataFields()}.
 * @todo test for {@link FieldList->findOrMakeTab()}.
 * @todo the same as above with insertBefore() and insertAfter()
 *
 */
class FieldListTest extends SapphireTest {

	/**
	 * Test adding a field to a tab in a set.
	 */
	public function testAddFieldToTab() {
		$fields = new FieldList();
		$tab = new Tab('Root');
		$fields->push($tab);

		/* We add field objects to the FieldList, using two different methods */
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
	 * Test that groups can be added to a fieldlist
	 */
	public function testFieldgroup() {
		$fields = new FieldList();
		$tab = new Tab('Root');
		$fields->push($tab);

		$fields->addFieldsToTab('Root', array(
			$group1 = new FieldGroup(
				new TextField('Name'),
				new EmailField('Email')
			),
			$group2 = new FieldGroup(
				new TextField('Company'),
				new TextareaField('Address')
			)
		));

		/* Check that the field objects were created */
		$this->assertNotNull($fields->dataFieldByName('Name'));
		$this->assertNotNull($fields->dataFieldByName('Email'));
		$this->assertNotNull($fields->dataFieldByName('Company'));
		$this->assertNotNull($fields->dataFieldByName('Address'));

		/* The field objects in the set should be the same as the ones we created */
		$this->assertSame($fields->dataFieldByName('Name'), $group1->fieldByName('Name'));
		$this->assertSame($fields->dataFieldByName('Email'), $group1->fieldByName('Email'));
		$this->assertSame($fields->dataFieldByName('Company'), $group2->fieldByName('Company'));
		$this->assertSame($fields->dataFieldByName('Address'), $group2->fieldByName('Address'));

		/* We'll have 2 fields directly inside the tab */
		$this->assertEquals(2, $tab->Fields()->Count());


	}

	/**
	 * Test removing a single field from a tab in a set.
	 */
	public function testRemoveSingleFieldFromTab() {
		$fields = new FieldList();
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

	public function testRemoveTab() {
		$fields = new FieldList(new TabSet(
			'Root',
			$tab1 = new Tab('Tab1'),
			$tab2 = new Tab('Tab2'),
			$tab3 = new Tab('Tab3')
		));

		$fields->removeByName('Tab2');
		$this->assertNull($fields->fieldByName('Root')->fieldByName('Tab2'));

		$this->assertEquals($tab1, $fields->fieldByName('Root')->fieldByName('Tab1'));
	}

	public function testHasTabSet() {
		$untabbedFields = new FieldList(
			new TextField('Field1')
		);
		$this->assertFalse($untabbedFields->hasTabSet());

		$tabbedFields = new FieldList(
			new TabSet('Root',
				new Tab('Tab1')
			)
		);
		$this->assertTrue($tabbedFields->hasTabSet());
	}

	/**
	 * Test removing an array of fields from a tab in a set.
	 */
	public function testRemoveMultipleFieldsFromTab() {
		$fields = new FieldList();
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

	public function testRemoveFieldByName() {
		$fields = new FieldList();
		$fields->push(new TextField('Name', 'Your name'));

		$this->assertEquals(1, $fields->Count());
		$fields->removeByName('Name');
		$this->assertEquals(0, $fields->Count());

		$fields->push(new TextField('Name[Field]', 'Your name'));
		$this->assertEquals(1, $fields->Count());
		$fields->removeByName('Name[Field]');
		$this->assertEquals(0, $fields->Count());
	}

	public function testDataFieldByName() {
		$fields = new FieldList();
		$fields->push($basic = new TextField('Name', 'Your name'));
		$fields->push($brack = new TextField('Name[Field]', 'Your name'));

		$this->assertEquals($basic, $fields->dataFieldByName('Name'));
		$this->assertEquals($brack, $fields->dataFieldByName('Name[Field]'));
	}

	/**
	 * Test removing multiple fields from a set by their names in an array.
	 */
	public function testRemoveFieldsByName() {
		$fields = new FieldList();

		/* First of all, we add some fields into our FieldList object */
		$fields->push(new TextField('Name', 'Your name'));
		$fields->push(new TextField('Email', 'Your email'));

		/* We have 2 fields in our set now */
		$this->assertEquals(2, $fields->Count());

		/* Then, we call up removeByName() to take it out again */
		$fields->removeByName(array('Name', 'Email'));

		/* We have 0 fields in our set now, as we've just removed the one we added */
		$this->assertEquals(0, $fields->Count());
	}

	/**
	 * Test replacing a field with another one.
	 */
	public function testReplaceField() {
		$fields = new FieldList();
		$tab = new Tab('Root');
		$fields->push($tab);

		/* A field gets added to the set */
		$fields->addFieldToTab('Root', new TextField('Country'));

		$this->assertSame($fields->dataFieldByName('Country'), $tab->fieldByName('Country'));

		$fields->replaceField('Country', new EmailField('Email'));
		$this->assertEquals(1, $tab->Fields()->Count());

		$fields = new FieldList();
		$fields->push(new TextField('Name', 'Your name'));
		$brack = new TextField('Name[Field]', 'Your name');

		$fields->replaceField('Name', $brack);
		$this->assertEquals(1, $fields->Count());

		$this->assertEquals('Name[Field]', $fields->first()->getName());
	}

	public function testRenameField() {
		$fields = new FieldList();
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

	public function testReplaceAFieldInADifferentTab() {
		/* A FieldList gets created with a TabSet and some field objects */
		$FieldList = new FieldList(
			new TabSet('Root', $main = new Tab('Main',
				new TextField('A'),
				new TextField('B')
			), $other = new Tab('Other',
				new TextField('C'),
				new TextField('D')
			))
		);

		/* The field "A" gets added to the FieldList we just created created */
		$FieldList->addFieldToTab('Root.Other', $newA = new TextField('A', 'New Title'));

		/* The field named "A" has been removed from the Main tab to make way for our new field named "A" in
		 * Other tab. */
		$this->assertEquals(1, $main->Fields()->Count());
		$this->assertEquals(3, $other->Fields()->Count());
	}

	/**
	 * Test finding a field that's inside a tabset, within another tab.
	 */
	public function testNestedTabsFindingFieldByName() {
		$fields = new FieldList();

		/* 2 tabs get created within a TabSet inside our set */
		$tab = new TabSet('Root',
			new TabSet('MyContent',
				$mainTab = new Tab('Main'),
				$otherTab = new Tab('Others')
			)
		);
		$fields->push($tab);

		/* Some fields get added to the 2 tabs we just created */
		$fields->addFieldToTab('Root.MyContent.Main', new TextField('Country'));
		$fields->addFieldToTab('Root.MyContent.Others', new TextField('Email'));

		/* The fields we just added actually exists in the set */
		$this->assertNotNull($fields->dataFieldByName('Country'));
		$this->assertNotNull($fields->dataFieldByName('Email'));

		/* The fields we just added actually exist in the tabs */
		$this->assertNotNull($mainTab->fieldByName('Country'));
		$this->assertNotNull($otherTab->fieldByName('Email'));

		/* We have 1 field for each of the tabs */
		$this->assertEquals(1, $mainTab->Fields()->Count());
		$this->assertEquals(1, $otherTab->Fields()->Count());

		$this->assertNotNull($fields->fieldByName('Root.MyContent'));
		$this->assertNotNull($fields->fieldByName('Root.MyContent'));
	}

	public function testTabTitles() {
		$set = new FieldList(
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
	 * This tests {@link FieldList->push()}.
	 */
	public function testPushFieldToSet() {
		$fields = new FieldList();

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
	 * This tests {@link FieldList->insertBefore()}.
	 */
	public function testInsertBeforeFieldToSet() {
		$fields = new FieldList();

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
		$this->assertEquals('Title', $fields[2]->getName());

		/* Test arguments are accepted in either order */
		$fields->insertBefore('FirstName', new TextField('Surname'));

		/* The field we just added actually exists in the set */
		$this->assertNotNull($fields->dataFieldByName('Surname'));

		/* We now have 5 fields in the set */
		$this->assertEquals(5, $fields->Count());

		/* The position of the Surname field is at number 4 */
		$this->assertEquals('Surname', $fields[3]->getName());
	}

	public function testInsertBeforeMultipleFields() {
		$fields = new FieldList(
			$root = new TabSet("Root",
				$main = new Tab("Main",
					$a = new TextField("A"),
					$b = new TextField("B")
				)
			)
		);

		$fields->addFieldsToTab('Root.Main', array(
			new TextField('NewField1'),
			new TextField('NewField2')
		), 'B');

		$this->assertEquals(array_keys($fields->dataFields()), array(
			'A',
			'NewField1',
			'NewField2',
			'B'
		));
	}

	/**
	 * Test inserting a field after another in a set.
	 */
	public function testInsertAfterFieldToSet() {
		$fields = new FieldList();

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

		/* We now have 4 fields in the FieldList */
		$this->assertEquals(4, $fields->Count());

		/* The position of the Title field should be at number 2 */
		$this->assertEquals('Title', $fields[1]->getName());

		/* Test arguments are accepted in either order */
		$fields->insertAfter('FirstName', new TextField('Surname'));

		/* The field we just added actually exists in the set */
		$this->assertNotNull($fields->dataFieldByName('Surname'));

		/* We now have 5 fields in the set */
		$this->assertEquals(5, $fields->Count());

		/* The position of the Surname field is at number 5 */
		$this->assertEquals('Surname', $fields[4]->getName());
	}

	public function testrootFieldList() {
		/* Given a nested set of FormField, CompositeField, and FieldList objects */
		$FieldList = new FieldList(
			$root = new TabSet("Root",
				$main = new Tab("Main",
					$a = new TextField("A"),
					$b = new TextField("B")
				)
			)
		);

		/* rootFieldList() should always evaluate to the same object: the topmost FieldList */
		$this->assertSame($FieldList, $FieldList->rootFieldList());
		$this->assertSame($FieldList, $root->rootFieldList());
		$this->assertSame($FieldList, $main->rootFieldList());
		$this->assertSame($FieldList, $a->rootFieldList());
		$this->assertSame($FieldList, $b->rootFieldList());

		/* If we push additional fields, they should also have the same rootFieldList() */
		$root->push($other = new Tab("Other"));
		$other->push($c = new TextField("C"));
		$root->push($third = new Tab("Third", $d = new TextField("D")));

		$this->assertSame($FieldList, $other->rootFieldList());
		$this->assertSame($FieldList, $third->rootFieldList());
		$this->assertSame($FieldList, $c->rootFieldList());
		$this->assertSame($FieldList, $d->rootFieldList());
	}

	public function testAddingDuplicateReplacesOldField() {
		/* Given a nested set of FormField, CompositeField, and FieldList objects */
		$FieldList = new FieldList(
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

		$FieldList->addFieldToTab("Root.Main", $newA);
		$FieldList->addFieldToTab("Root.Other", $newB);

		$this->assertSame($newA, $FieldList->dataFieldByName("A"));
		$this->assertSame($newB, $FieldList->dataFieldByName("B"));
		$this->assertEquals(1, $main->Fields()->Count());

		/* Pushing fields on the end of the field set should remove them from the tab */
		$thirdA = new TextField("A", "Third A");
		$thirdB = new TextField("B", "Third B");
		$FieldList->push($thirdA);
		$FieldList->push($thirdB);

		$this->assertSame($thirdA, $FieldList->fieldByName("A"));
		$this->assertSame($thirdB, $FieldList->fieldByName("B"));

		$this->assertEquals(0, $main->Fields()->Count());
	}

	public function testAddingFieldToNonExistentTabCreatesThatTab() {
		$FieldList = new FieldList(
			$root = new TabSet("Root",
				$main = new Tab("Main",
					$a = new TextField("A")
				)
			)
		);

		/* Add a field to a non-existent tab, and it will be created */
		$FieldList->addFieldToTab("Root.Other", $b = new TextField("B"));
		$this->assertNotNull($FieldList->fieldByName('Root')->fieldByName('Other'));
		$this->assertSame($b, $FieldList->fieldByName('Root')->fieldByName('Other')->Fields()->First());
	}

	public function testAddingFieldToATabWithTheSameNameAsTheField() {
		$FieldList = new FieldList(
			$root = new TabSet("Root",
				$main = new Tab("Main",
					$a = new TextField("A")
				)
			)
		);

		/* If you have a tab with the same name as the field, then technically it's a duplicate. However, it's
		 * allowed because tab isn't a data field.  Only duplicate data fields are problematic */
		$FieldList->addFieldToTab("Root.MyName", $myName = new TextField("MyName"));
		$this->assertNotNull($FieldList->fieldByName('Root')->fieldByName('MyName'));
		$this->assertSame($myName, $FieldList->fieldByName('Root')->fieldByName('MyName')->Fields()->First());
	}

	public function testInsertBeforeWithNestedCompositeFields() {
		$FieldList = new FieldList(
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

		$FieldList->insertBefore(
			$A_insertbefore = new TextField('A_insertbefore'),
			'A'
		);
		$this->assertSame(
			$A_insertbefore,
			$FieldList->dataFieldByName('A_insertbefore'),
			'Field on toplevel FieldList can be inserted'
		);

		$FieldList->insertBefore(
			$B_insertbefore = new TextField('B_insertbefore'),
			'B'
		);
		$this->assertSame(
			$FieldList->dataFieldByName('B_insertbefore'),
			$B_insertbefore,
			'Field on one nesting level FieldList can be inserted'
		);

		$FieldList->insertBefore(
			$C_insertbefore = new TextField('C_insertbefore'),
			'C'
		);
		$this->assertSame(
			$FieldList->dataFieldByName('C_insertbefore'),
			$C_insertbefore,
			'Field on two nesting levels FieldList can be inserted'
		);
	}

	/**
	 * @todo check actual placement of fields
	 */
	public function testInsertBeforeWithNestedTabsets() {
		$FieldListA = new FieldList(
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
			$FieldListA->dataFieldByName('A_insertbefore'),
			$A_insertbefore,
			'Field on toplevel tab can be inserted'
		);

		$this->assertEquals(0, $tabA1->fieldPosition('A_pre'));
		$this->assertEquals(1, $tabA1->fieldPosition('A_insertbefore'));
		$this->assertEquals(2, $tabA1->fieldPosition('A'));
		$this->assertEquals(3, $tabA1->fieldPosition('A_post'));

		$FieldListB = new FieldList(
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
		$FieldListB->insertBefore(
			$B_insertbefore = new TextField('B_insertbefore'),
			'B'
		);
		$this->assertSame(
			$FieldListB->dataFieldByName('B_insertbefore'),
			$B_insertbefore,
			'Field on nested tab can be inserted'
		);
		$this->assertEquals(0, $tabB2->fieldPosition('B_pre'));
		$this->assertEquals(1, $tabB2->fieldPosition('B_insertbefore'));
		$this->assertEquals(2, $tabB2->fieldPosition('B'));
		$this->assertEquals(3, $tabB2->fieldPosition('B_post'));
	}

	public function testInsertAfterWithNestedCompositeFields() {
		$FieldList = new FieldList(
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

		$FieldList->insertAfter(
			$A_insertafter = new TextField('A_insertafter'),
			'A'
		);
		$this->assertSame(
			$A_insertafter,
			$FieldList->dataFieldByName('A_insertafter'),
			'Field on toplevel FieldList can be inserted after'
		);

		$FieldList->insertAfter(
			$B_insertafter = new TextField('B_insertafter'),
			'B'
		);
		$this->assertSame(
			$FieldList->dataFieldByName('B_insertafter'),
			$B_insertafter,
			'Field on one nesting level FieldList can be inserted after'
		);

		$FieldList->insertAfter(
			$C_insertafter = new TextField('C_insertafter'),
			'C'
		);
		$this->assertSame(
			$FieldList->dataFieldByName('C_insertafter'),
			$C_insertafter,
			'Field on two nesting levels FieldList can be inserted after'
		);
	}

	/**
	 * @todo check actual placement of fields
	 */
	public function testInsertAfterWithNestedTabsets() {
		$FieldListA = new FieldList(
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
			$FieldListA->dataFieldByName('A_insertafter'),
			$A_insertafter,
			'Field on toplevel tab can be inserted after'
		);
		$this->assertEquals(0, $tabA1->fieldPosition('A_pre'));
		$this->assertEquals(1, $tabA1->fieldPosition('A'));
		$this->assertEquals(2, $tabA1->fieldPosition('A_insertafter'));
		$this->assertEquals(3, $tabA1->fieldPosition('A_post'));

		$FieldListB = new FieldList(
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
		$FieldListB->insertAfter(
			$B_insertafter = new TextField('B_insertafter'),
			'B'
		);
		$this->assertSame(
			$FieldListB->dataFieldByName('B_insertafter'),
			$B_insertafter,
			'Field on nested tab can be inserted after'
		);
		$this->assertEquals(0, $tabB2->fieldPosition('B_pre'));
		$this->assertEquals(1, $tabB2->fieldPosition('B'));
		$this->assertEquals(2, $tabB2->fieldPosition('B_insertafter'));
		$this->assertEquals(3, $tabB2->fieldPosition('B_post'));
	}
	/**
	 * FieldList::changeFieldOrder() should place specified fields in given
	 * order then add any unspecified remainders at the end. Can be given an
	 * array or list of arguments.
	 */
	public function testChangeFieldOrder() {
		$fieldNames = array('A','B','C','D','E');
		$setArray = new FieldList();
		$setArgs = new FieldList();
		foreach ($fieldNames as $fN) {
			$setArray->push(new TextField($fN));
			$setArgs->push(new TextField($fN));
		}

		$setArray->changeFieldOrder(array('D','B','E'));
		$this->assertEquals(0, $setArray->fieldPosition('D'));
		$this->assertEquals(1, $setArray->fieldPosition('B'));
		$this->assertEquals(2, $setArray->fieldPosition('E'));
		$this->assertEquals(3, $setArray->fieldPosition('A'));
		$this->assertEquals(4, $setArray->fieldPosition('C'));

		$setArgs->changeFieldOrder('D','B','E');
		$this->assertEquals(0, $setArgs->fieldPosition('D'));
		$this->assertEquals(1, $setArgs->fieldPosition('B'));
		$this->assertEquals(2, $setArgs->fieldPosition('E'));
		$this->assertEquals(3, $setArgs->fieldPosition('A'));
		$this->assertEquals(4, $setArgs->fieldPosition('C'));

		unset($setArray, $setArgs);
	}
	
	public function testFieldPosition() {
		$set = new FieldList(
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

	/**
	 * FieldList::forTemplate() returns a concatenation of FieldHolder values.
	 */
	public function testForTemplate() {
		$set = new FieldList(
			$a = new TextField('A'),
			$b = new TextField('B')
		);

		$this->assertEquals($a->FieldHolder() . $b->FieldHolder(), $set->forTemplate());
	}

	/**
	 * FieldList::forTemplate() for an action list returns a concatenation of Field values.
	 * Internally, this works by having FormAction::FieldHolder return just the field, but it's an important
	 * use-case to test.
	 */
	public function testForTemplateForActionList() {
		$set = new FieldList(
			$a = new FormAction('A'),
			$b = new FormAction('B')
		);

		$this->assertEquals($a->Field() . $b->Field(), $set->forTemplate());
	}

	public function testMakeFieldReadonly() {
		$FieldList = new FieldList(
			new TabSet('Root', new Tab('Main',
				new TextField('A'),
				new TextField('B')
			)
		));

		$FieldList->makeFieldReadonly('A');
		$this->assertTrue(
			$FieldList->dataFieldByName('A')->isReadonly(),
			'Field nested inside a TabSet and FieldList can be marked readonly by FieldList->makeFieldReadonly()'
		);
	}

	/**
	 * Test VisibleFields and HiddenFields
	 */
	public function testVisibleAndHiddenFields() {
		$fields = new FieldList(
			new TextField("A"),
			new TextField("B"),
			new HiddenField("C"),
			new Tabset("Root",
				new Tab("D",
					new TextField("D1"),
					new HiddenField("D2")
				)
			)
		);

		$hidden = $fields->HiddenFields();
		// Inside hidden fields, all HiddenField objects are included, even nested ones
		$this->assertNotNull($hidden->dataFieldByName('C'));
		$this->assertNotNull($hidden->dataFieldByName('D2'));
		// Visible fields are not
		$this->assertNull($hidden->dataFieldByName('B'));
		$this->assertNull($hidden->dataFieldByName('D1'));

		$visible = $fields->VisibleFields();
		// Visible fields exclude top level HiddenField objects
		$this->assertNotNull($visible->dataFieldByName('A'));
		$this->assertNull($visible->dataFieldByName('C'));
		// But they don't exclude nested HiddenField objects.  This is a limitation; you should
		// put all your HiddenFields at the top level.
		$this->assertNotNull($visible->dataFieldByName('D2'));
	}

	public function testRewriteTabPath() {
		$originalDeprecation = Deprecation::dump_settings();
		Deprecation::notification_version('2.4');

		$fields = new FieldList(
			new Tabset("Root",
				$tab1Level1 = new Tab("Tab1Level1",
					$tab1Level2 = new Tab("Tab1Level2"),
					$tab2Level2 = new Tab("Tab2Level2")
				),
				$tab2Level1 = new Tab("Tab2Level1")
			)
		);
		$fields->setTabPathRewrites(array(
			'/Root\.Tab1Level1\.([^.]+)$/' => 'Root.Tab1Level1Renamed.\\1',
			'/Root\.Tab1Level1$/' => 'Root.Tab1Level1Renamed',
		));
		$method = new ReflectionMethod($fields, 'rewriteTabPath');
		$method->setAccessible(true);
		$this->assertEquals(
			'Root.Tab1Level1Renamed',
			$method->invoke($fields, 'Root.Tab1Level1Renamed'),
			"Doesn't rewrite new name"
		);
		$this->assertEquals(
			'Root.Tab1Level1Renamed',
			$method->invoke($fields, 'Root.Tab1Level1'),
			'Direct aliasing on toplevel'
		);
		$this->assertEquals(
			'Root.Tab1Level1Renamed.Tab1Level2',
			$method->invoke($fields, 'Root.Tab1Level1.Tab1Level2'),
			'Indirect aliasing on toplevel'
		);

		Deprecation::restore_settings($originalDeprecation);
	}

	public function testRewriteTabPathFindOrMakeTab() {
		$originalDeprecation = Deprecation::dump_settings();
		Deprecation::notification_version('2.4');

		$fields = new FieldList(
			new Tabset("Root",
				$tab1Level1 = new Tab("Tab1Level1Renamed",
					$tab1Level2 = new Tab("Tab1Level2"),
					$tab2Level2 = new Tab("Tab2Level2")
				),
				$tab2Level1 = new Tab("Tab2Level1")
			)
		);
		$fields->setTabPathRewrites(array(
			'/Root\.Tab1Level1\.([^.]+)$/' => 'Root.Tab1Level1Renamed.\\1',
			'/Root\.Tab1Level1$/' => 'Root.Tab1Level1Renamed',
		));

		$this->assertEquals($tab1Level1, $fields->findOrMakeTab('Root.Tab1Level1'),
			'findOrMakeTab() with toplevel tab under old name'
		);
		$this->assertEquals($tab1Level1, $fields->findOrMakeTab('Root.Tab1Level1Renamed'),
			'findOrMakeTab() with toplevel tab under new name'
		);
		$this->assertEquals($tab1Level2, $fields->findOrMakeTab('Root.Tab1Level1.Tab1Level2'),
			'findOrMakeTab() with nested tab under old parent tab name'
		);
		$this->assertEquals($tab1Level2, $fields->findOrMakeTab('Root.Tab1Level1Renamed.Tab1Level2'),
			'findOrMakeTab() with nested tab under new parent tab name'
		);

		Deprecation::restore_settings($originalDeprecation);
	}

}
