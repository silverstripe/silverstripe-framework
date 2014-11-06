<?php

class DbDatetimeTest extends FunctionalTest {

	protected static $fixture_file = 'DbDatetimeTest.yml';

	protected $extraDataObjects = array('DbDatetimeTest_Team');

	protected $offset;

	protected $adapter;

	/**
	 * Check if dates match more or less. This takes into the account the db query
	 * can overflow to the next second giving offset readings.
	 */
	private function matchesRoughly($date1, $date2, $comment = '', $offset) {
		$allowedDifference = 5 + abs($offset); // seconds

		$time1 = is_numeric($date1) ? $date1 : strtotime($date1);
		$time2 = is_numeric($date2) ? $date2 : strtotime($date2);

		$this->assertTrue(abs($time1-$time2)<$allowedDifference,
			$comment . " (times differ by " . abs($time1-$time2) . " seconds)");
	}

	private function getDbNow() {
		$query = 'SELECT ' . $this->adapter->formattedDatetimeClause('now', '%U');
		return DB::query($query)->value();
	}

	/**
	 * Needs to be run within a test*() context.
	 *
	 * @return Int Offset in seconds
	 */
	private function checkPreconditions() {
		// number of seconds of php and db time are out of sync
		$offset = time() - strtotime(DB::query('SELECT ' . DB::get_conn()->now())->value());
		$threshold = 5; // seconds

		if($offset > 5) {
			$this->markTestSkipped('The time of the database is out of sync with the webserver by '
				. abs($offset) . ' seconds.');
		}

		if(method_exists($this->adapter, 'supportsTimezoneOverride') && !$this->adapter->supportsTimezoneOverride()) {
			$this->markTestSkipped("Database doesn't support timezone overrides");
		}

		return $offset;
	}

	public function setUp() {
		parent::setUp();
		$this->adapter = DB::get_conn();
	}

	public function testCorrectNow() {
		$offset = $this->checkPreconditions();

		$clause = $this->adapter->formattedDatetimeClause('now', '%U');
		$result = DB::query('SELECT ' . $clause)->value();
		$this->assertRegExp('/^\d*$/', (string) $result);
		$this->assertTrue($result>0);
	}

	public function testDbDatetimeFormat() {
		$offset = $this->checkPreconditions();

		$clause = $this->adapter->formattedDatetimeClause('1973-10-14 10:30:00', '%H:%i, %d/%m/%Y');
		$result = DB::query('SELECT ' . $clause)->value();
		$this->matchesRoughly($result, date('H:i, d/m/Y', strtotime('1973-10-14 10:30:00')), 'nice literal time',
			$offset);

		$clause = $this->adapter->formattedDatetimeClause('now', '%d');
		$result = DB::query('SELECT ' . $clause)->value();
		$this->matchesRoughly($result, date('d', $this->getDbNow()), 'todays day', $offset);

		$clause = $this->adapter->formattedDatetimeClause('"Created"', '%U') . ' AS test FROM "DbDateTimeTest_Team"';
		$result = DB::query('SELECT ' . $clause)->value();
		$this->matchesRoughly($result, strtotime(DataObject::get_one('DbDateTimeTest_Team')->Created),
			'fixture ->Created as timestamp', $offset);
	}

	public function testDbDatetimeInterval() {
		$offset = $this->checkPreconditions();

		$clause = $this->adapter->datetimeIntervalClause('1973-10-14 10:30:00', '+18 Years');
		$result = DB::query('SELECT ' . $clause)->value();
		$this->matchesRoughly($result, '1991-10-14 10:30:00', 'add 18 years', $offset);

		$clause = $this->adapter->datetimeIntervalClause('now', '+1 Day');
		$result = DB::query('SELECT ' . $clause)->value();
		$this->matchesRoughly($result, date('Y-m-d H:i:s', strtotime('+1 Day', $this->getDbNow())), 'tomorrow',
			$offset);

		$query = new SQLQuery();
		$query->setSelect(array());
		$query->selectField($this->adapter->datetimeIntervalClause('"Created"', '-15 Minutes'), 'test')
			->setFrom('"DbDateTimeTest_Team"')
			->setLimit(1);

		$result = $query->execute()->value();
		$this->matchesRoughly($result,
				date('Y-m-d H:i:s', strtotime(DataObject::get_one('DbDateTimeTest_Team')->Created) - 900),
				'15 Minutes before creating fixture', $offset);
	}

	public function testDbDatetimeDifference() {
		$offset = $this->checkPreconditions();

		$clause = $this->adapter->datetimeDifferenceClause('1974-10-14 10:30:00', '1973-10-14 10:30:00');
		$result = DB::query('SELECT ' . $clause)->value();
		$this->matchesRoughly($result/86400, 365, '1974 - 1973 = 365 * 86400 sec', $offset);

		$clause = $this->adapter->datetimeDifferenceClause(date('Y-m-d H:i:s', strtotime('-15 seconds')), 'now');
		$result = DB::query('SELECT ' . $clause)->value();
		$this->matchesRoughly($result, -15, '15 seconds ago - now', $offset);

		$clause = $this->adapter->datetimeDifferenceClause('now',
			$this->adapter->datetimeIntervalClause('now', '+45 Minutes'));
		$result = DB::query('SELECT ' . $clause)->value();
		$this->matchesRoughly($result, -45 * 60, 'now - 45 minutes ahead', $offset);

		$query = new SQLQuery();
		$query->setSelect(array());
		$query->selectField($this->adapter->datetimeDifferenceClause('"LastEdited"', '"Created"'), 'test')
			->setFrom('"DbDateTimeTest_Team"')
			->setLimit(1);

		$result = $query->execute()->value();
		$lastedited = Dataobject::get_one('DbDateTimeTest_Team')->LastEdited;
		$created = Dataobject::get_one('DbDateTimeTest_Team')->Created;
		$this->matchesRoughly($result, strtotime($lastedited) - strtotime($created),
			'age of HomePage record in seconds since unix epoc', $offset);
	}

}

class DbDateTimeTest_Team extends DataObject implements TestOnly {
	private static $db = array(
		'Title' => 'Varchar'
	);
}
