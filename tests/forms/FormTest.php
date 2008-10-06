<?php
/**
 * @package sapphire
 * @subpackage tests
 */
class FormTest extends FunctionalTest {
	
	public function testLoadDataFromRequest() {
		$form = new Form(
			new Controller(),
			'Form',
			new FieldSet(
				new TextField('key1'),
				new TextField('namespace[key2]'),
				new TextField('namespace[key3][key4]'),
				new TextField('othernamespace[key5][key6][key7]')
			),
			new FieldSet()
		);
		
		// url would be ?key1=val1&namespace[key2]=val2&namespace[key3][key4]=val4&othernamespace[key5][key6][key7]=val7
		$requestData = array(
			'key1' => 'val1',
			'namespace' => array(
				'key2' => 'val2',
				'key3' => array(
					'key4' => 'val4',
				)
			),
			'othernamespace' => array(
				'key5' => array(
					'key6' =>array(
						'key7' => 'val7'
					)
				)
			)
		);
		
		$form->loadDataFrom($requestData);
		
		$fields = $form->Fields();
		$this->assertEquals($fields->fieldByName('key1')->Value(), 'val1');
		$this->assertEquals($fields->fieldByName('namespace[key2]')->Value(), 'val2');
		$this->assertEquals($fields->fieldByName('namespace[key3][key4]')->Value(), 'val4');
		$this->assertEquals($fields->fieldByName('othernamespace[key5][key6][key7]')->Value(), 'val7');
	}
	
	public function testFormMethodOverride() {
		$form = $this->getStubForm();
		$form->setFormMethod('GET');
		$this->assertNull($form->dataFieldByName('_method'));
		
		$form = $this->getStubForm();
		$form->setFormMethod('PUT');
		$this->assertEquals($form->dataFieldByName('_method')->Value(), 'put',
			'PUT override in forms has PUT in hiddenfield'
		);
		$this->assertEquals($form->FormMethod(), 'post',
			'PUT override in forms has POST in <form> tag'
		);
		
		$form = $this->getStubForm();
		$form->setFormMethod('DELETE');
		$this->assertEquals($form->dataFieldByName('_method')->Value(), 'delete',
			'PUT override in forms has PUT in hiddenfield'
		);
		$this->assertEquals($form->FormMethod(), 'post',
			'PUT override in forms has POST in <form> tag'
		);
	}
	
	protected function getStubForm() {
		return new Form(
			new Controller(),
			'Form',
			new FieldSet(new TextField('key1')),
			new FieldSet()
		);
	}
	
}
?>