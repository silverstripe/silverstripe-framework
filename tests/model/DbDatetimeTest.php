<?php

class DbDatetimeTest extends FunctionalTest {
	static $fixture_file = 'sapphire/tests/model/DbDatetimeTest.yml';
	
	/**
	 * Check if dates match more or less. This takes into the account the db query
	 * can overflow to the next second giving offset readings.
	 */
	private function matchesRoughly($date1, $date2, $comment = '') {
		$allowedDifference = 5; // seconds
		
		$time1 = is_numeric($date1) ? $date1 : strtotime($date1);
		$time2 = is_numeric($date2) ? $date2 : strtotime($date2);
		
		$this->assertTrue(abs($time1-$time2)<$allowedDifference, $comment . " ($date1, $date2)");
	}
	
	private function getDbNow() {
		$query = 'SELECT ' . $this->adapter->formattedDatetimeClause('now', '%U');
		return DB::query($query)->value();
	}
	
	function setUp() {
		parent::setUp();
		$this->adapter = DB::getConn();
		$this->supportDbDatetime = method_exists($this->adapter, 'datetimeIntervalClause');
	}

	function testCorrectNow() {
		if($this->supportDbDatetime) {
			$query = 'SELECT ' . $this->adapter->formattedDatetimeClause('now', '%U');
			$result = DB::query($query)->value();
			$this->assertRegExp('/^\d*$/', (string) $result);
			$this->assertTrue($result>0);
		}
	}

	function testDbDatetimeFormat() {
		if($this->supportDbDatetime) {
			$query = 'SELECT ' . $this->adapter->formattedDatetimeClause('1973-10-14 10:30:00', '%H:%i, %d/%m/%Y');
			$result = DB::query($query)->value();
			$this->matchesRoughly($result, date('H:i, d/m/Y', strtotime('1973-10-14 10:30:00')), 'nice literal time');

			$query = 'SELECT ' . $this->adapter->formattedDatetimeClause('now', '%d');
			$result = DB::query($query)->value();
			$this->matchesRoughly($result, date('d', $this->getDbNow()), 'todays day');

			$query = 'SELECT ' . $this->adapter->formattedDatetimeClause('"Created"', '%U') . ' AS test FROM "SiteTree" WHERE "URLSegment" = \'home\'';
			$result = DB::query($query)->value();

			$this->matchesRoughly($result, strtotime(DataObject::get_one('SiteTree',"\"URLSegment\" = 'home'")->Created), 'SiteTree[home]->Created as timestamp');
		}
	}
	
	function testDbDatetimeInterval() {
		if($this->supportDbDatetime) {

			$query = 'SELECT ' . $this->adapter->datetimeIntervalClause('1973-10-14 10:30:00', '+18 Years');
			$result = DB::query($query)->value();
			$this->matchesRoughly($result, '1991-10-14 10:30:00', 'add 18 years');

			$query = 'SELECT ' . $this->adapter->datetimeIntervalClause('now', '+1 Day');
			$result = DB::query($query)->value();
			$this->matchesRoughly($result, date('Y-m-d H:i:s', strtotime('+1 Day', $this->getDbNow())), 'tomorrow');

			$query = 'SELECT ' . $this->adapter->datetimeIntervalClause('"Created"', '-15 Minutes') . ' AS "test" FROM "SiteTree" WHERE "URLSegment" = \'home\'';
			$result = DB::query($query)->value();
			$this->matchesRoughly($result, date('Y-m-d H:i:s', strtotime(Dataobject::get_one('SiteTree',"\"URLSegment\" = 'home'")->Created) - 900), '15 Minutes before creating SiteTree[home]');

		}
	}
	
	function testDbDatetimeDifference() {
		if($this->supportDbDatetime) {

			$query = 'SELECT ' . $this->adapter->datetimeDifferenceClause('1974-10-14 10:30:00', '1973-10-14 10:30:00');
			$result = DB::query($query)->value();
			$this->matchesRoughly($result/86400, 365, '1974 - 1973 = 365 * 86400 sec');

			$query = 'SELECT ' . $this->adapter->datetimeDifferenceClause(date('Y-m-d H:i:s', strtotime('-15 seconds')), 'now');
			$result = DB::query($query)->value();
			$this->matchesRoughly($result, -15, '15 seconds ago - now');

			$query = 'SELECT ' . $this->adapter->datetimeDifferenceClause('now', $this->adapter->datetimeIntervalClause('now', '+45 Minutes'));
			$result = DB::query($query)->value();
			$this->matchesRoughly($result, -45 * 60, 'now - 45 minutes ahead');

			$query = 'SELECT ' . $this->adapter->datetimeDifferenceClause('"LastEdited"', '"Created"') . ' AS "test" FROM "SiteTree" WHERE "URLSegment" = \'home\'';
			$result = DB::query($query)->value();
			$lastedited = Dataobject::get_one('SiteTree',"\"URLSegment\" = 'home'")->LastEdited;
			$created = Dataobject::get_one('SiteTree',"\"URLSegment\" = 'home'")->Created;
			$this->matchesRoughly($result, strtotime($lastedited) - strtotime($created), 'age of HomePage record in seconds since unix epoc');

		}
	}
	
}
