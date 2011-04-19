<?php

class DataQueryTest extends SapphireTest {
	/**
	 * Test the join() method of the DataQuery object
	 */
	function testJoin() {
		$dq = new DataQuery('Member');
		$dq->join("INNER JOIN \"Group_Members\" ON \"Group_Members\".\"MemberID\" = \"Member\".\"ID\"");
		$this->assertContains("INNER JOIN \"Group_Members\" ON \"Group_Members\".\"MemberID\" = \"Member\".\"ID\"", $dq->sql());
	}
}

?>