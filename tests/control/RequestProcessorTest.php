<?php
/**
 * Tests for the {@link RequestProcessor} class.
 *
 * @package framework
 * @subpackage tests
 */
class RequestProcessorTest extends SapphireTest {

	public function testPreRequest() {
		$first = $this->getMock('PreRequestFilter');
		$first->expects($this->once())->method('preRequest');

		$second = $this->getMock('PreRequestFilter');
		$second->expects($this->once())->method('preRequest');

		$processor = new RequestProcessor();
		$processor->setFilters(array($first, $second));

		$this->assertTrue($processor->preRequest(
			new SS_HTTPRequest('GET', ''),
			new Session(null),
			new DataModel()
		));
	}

	public function testPreRequestFailure() {
		$first = $this->getMock('PreRequestFilter');
		$first->expects($this->once())
		      ->method('preRequest')
		      ->will($this->returnValue(false));

		$second = $this->getMock('PreRequestFilter');
		$second->expects($this->never())->method('preRequest');

		$processor = new RequestProcessor();
		$processor->setFilters(array($first, $second));

		$this->assertFalse($processor->preRequest(
			new SS_HTTPRequest('GET', ''),
			new Session(null),
			new DataModel()
		));
	}

	public function testPostRequest() {
		$first = $this->getMock('PostRequestFilter');
		$first->expects($this->once())->method('postRequest');

		$second = $this->getMock('PostRequestFilter');
		$second->expects($this->once())->method('postRequest');

		$processor = new RequestProcessor();
		$processor->setFilters(array($first, $second));

		$this->assertTrue($processor->postRequest(
			new SS_HTTPRequest('GET', ''),
			new SS_HTTPResponse(),
			new DataModel()
		));
	}

	public function testPostRequestFailure() {
		$first = $this->getMock('PostRequestFilter');
		$first->expects($this->once())
			->method('postRequest')
			->will($this->returnValue(false));

		$second = $this->getMock('PostRequestFilter');
		$second->expects($this->never())->method('postRequest');

		$processor = new RequestProcessor();
		$processor->setFilters(array($first, $second));

		$this->assertFalse($processor->postRequest(
			new SS_HTTPRequest('GET', ''),
			new SS_HTTPResponse(),
			new DataModel()
		));
	}

}
