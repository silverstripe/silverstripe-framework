<?php
/**
 * @package framework
 * @subpackage tests
 */
class SQLFormatterTest extends SapphireTest {

	public function testNewlineHanding() {
		$formatter = new SQLFormatter();

		$sqlBefore = <<<SQL
SELECT Test.Foo, Test.Bar FROM Test WHERE 'From' = "Where"
SQL;
		$sqlAfter = <<<SQL
SELECT Test.Foo, Test.Bar
FROM Test
WHERE 'From' = "Where"
SQL;

		$this->assertEquals($formatter->formatPlain($sqlBefore), $sqlAfter,
			'correct replacement of newlines and don\'t replace non-uppercase tokens'
		);

		$sqlBefore = <<<SQL
SELECT Test.Foo, Test.Bar
FROM Test
WHERE
  'From' = "Where"
SQL;
		$sqlAfter = <<<SQL
SELECT Test.Foo, Test.Bar
FROM Test
WHERE
  'From' = "Where"
SQL;
		$this->assertEquals($formatter->formatPlain($sqlBefore), $sqlAfter,
			'Leave existing newlines and indentation in place'
		);
	}

}
