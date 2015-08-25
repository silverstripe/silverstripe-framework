<?php
/**
 * @package framework
 * @subpackage tests
 */
class TaskRunnerTest extends SapphireTest {

	public function testTaskEnabled() {
		$runner = new TaskRunner();
		$method = new ReflectionMethod($runner, 'taskEnabled');
		$method->setAccessible(true);

		$this->assertTrue($method->invoke($runner, 'TaskRunnerTest_EnabledTask'),
			'Enabled task incorrectly marked as disabled');
		$this->assertFalse($method->invoke($runner, 'TaskRunnerTest_DisabledTask'),
			'Disabled task incorrectly marked as enabled');
		$this->assertFalse($method->invoke($runner, 'TaskRunnerTest_AbstractTask'),
			'Disabled task incorrectly marked as enabled');
		$this->assertTrue($method->invoke($runner, 'TaskRunnerTest_ChildOfAbstractTask'),
			'Enabled task incorrectly marked as disabled');
	}

}

class TaskRunnerTest_EnabledTask extends BuildTask {
	protected $enabled = true;

	public function run($request) {
		// NOOP
	}
}

class TaskRunnerTest_DisabledTask extends BuildTask {
	protected $enabled = false;

	public function run($request) {
		// NOOP
	}
}

abstract class TaskRunnerTest_AbstractTask extends BuildTask {
	protected $enabled = true;

	public function run($request) {
		// NOOP
	}
}

class TaskRunnerTest_ChildOfAbstractTask extends TaskRunnerTest_AbstractTask {
	protected $enabled = true;

	public function run($request) {
		// NOOP
	}
}
