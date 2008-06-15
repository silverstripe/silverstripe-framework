<?php
/**
 * @package sapphire
 * @subpackage tests
 */
class FormTest extends SapphireTest {
	
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
	
}
?>