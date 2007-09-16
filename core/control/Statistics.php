<?php

/**
 * Statistics class for gathering and formatting of statistical data for tables and charts in
 * both public and administrative contexts.
 */

class Statistics extends Controller {

	function __construct()
	{

	}

	static function TrendChart($table, $filter = "day", $name, $type, $color)
	{

		$top = <<<END
<div id="trendchart"><canvas id="tchart" height="300" width="500"></canvas></div>
		<script type="text/javascript">\n
END;

		$bot = <<<END

		</script>
END;

		$ds = "var tchartdata = { \n";

		foreach($table as $class) {
			$record = DataObject::get($class, "", "Created DESC");
			$total = $record->TotalItems();

			$props = $record->toArray();
			$props = $props[0]->toMap();
			$startyear = new DateTime($props['Created']);
			$startyear = $startyear->Format('Y');
			$startmonth = new DateTime($props['Created']);
			$startmonth = $startmonth->Format('m');

			if($filter == "day") {
				$days = new DateTime($props['Created']);
				$days = $days->Format('t');

				$sum = 0;

				$ds .= "{$class}Set: [";

				for($i = 1; $i <= $days; $i++) {

					foreach($record as $v) {
						$props = $v->toMap();
						$currdate = new DateTime($props['Created']);
						$curryear = $currdate->Format('Y');
						$currmonth = $currdate->Format('m');
						$currday = $currdate->Format('j');
						if($curryear == $startyear && $currmonth == $startmonth && $currday == $i) {
							$sum++;
						}
					}

					$ds .= "[".($i-1).", {$sum}], ";

				}
				$ds .= "[]],\n";


			} else if($filter == "month") {

				$sum = 0;

				$ds .= "{$class}Set: [";

				for($i = 0; $i <= 11; $i++) {
					$imonth = date('F', mktime(0,0,0,$i+1,1,1));

					foreach($record as $v) {
						$props = $v->toMap();
						$currdate = new DateTime($props['Created']);
						$curryear = $currdate->Format('Y');
						$currmonth = $currdate->Format('m');
						$currday = $currdate->Format('j');
						if($curryear == $startyear && $currmonth == $i) {
							$sum++;
						}
					}

					$ds .= "[{$i}, {$sum}], ";

				}

				$ds .= "[]],\n";



			}
		}

		$xt = "xTicks: [";
		if($filter == "month") {
			for($i = 0; $i <= 11; $i++) {
				$imonth = date('F', mktime(0,0,0,$i+1,1,1));
				$xt .= "{v:{$i}, label:'{$imonth}'}, ";
			}
		} else if($filter == "day") {
			for($i = 1; $i <= $days; $i++) {
				$xt .= "{v:".($i-1).", label:'{$i}'}, ";
			}

		}

		$opts = <<<END
var options = {

	axis: {labelFontSize: 10},

	padding: {left: 30, right: 0, top: 10, bottom: 30},

	backgroundColor: '#cccccc',

	colorScheme: '{$color}',\n\n

END;
		$opts .= $xt . "]\n};";


			return $top . $ds . "\n};\n\n" /*. $opts*/ . "\n\n" . $bot;
	}

	static function UserRecordTable() {
		$records = DataObject::get('Member');
		$top = <<<END
		<div id="usertable">
		<table class="sortable-onload-1 rowstyle-alt no-arrow paginate-10 statstable" border="0" cellspacing="1" cellpadding="0">
			<thead>
				<tr><th class="sortable-numeric">ID</th><th class="sortable-text">Email</th><th class="sortable-sortDatetime">Joined</th></tr>
			</thead>
			<tbody>
END;
		$bod = "";
		foreach($records as $x) {
			$r = $x->toMap();
			$id = $r['ID'];
			$email = $r['Email'];
			$date = date("F j, Y G:i:s", strtotime($r['Created']));
			$bod .= "<tr><td>$id</td><td>$email</td><td>$date</td></tr>";
		}
		$bot = <<<END
			</tbody>
		</table>
		</div>
END;
		//$js = "\n\n<script type=\"text/javascript\">\n\tvar usertable = new TableKit('usertable');\nusertable.initialize();\n</script>\n";
		return $top . $bod . $bot;
	}

	static function Collect() {
		$hit = new PageView();
		$hit->record();
		return;
	}

	static function getRecentViews($limit = 15) {
		$records = DataObject::get('PageView', null, 'Created DESC', null, $limit);
		$top = <<<END
		<div id="viewtable">
		<table class="sortable-onload-1 rowstyle-alt no-arrow paginate-10 statstable" border="0" cellspacing="0" cellpadding="0">
			<thead>
				<tr><th class="sortable-numeric">ID</th><th class="sortable-sortDatetime">Time</th><th class="sortable-text">Browser</th><th class="sortable-text">OS</th><th>User</th><th class="sortable-text">Page</th></tr>
			</thead>
			<tbody>
END;
		$bod = "";
		foreach($records as $x) {
			$r = $x->toMap();
			$id = $r['ID'];
			$time = $r['Created'];
			$browser = $r['Browser'] . " " . $r['BrowserVersion'];
			$os = $r['OS'];
			$user = $r['UserID'];
			$page = $r['PageID'];
			$bod .= "<tr><td>$id</td><td>$time</td><td>$browser</td><td>$os</td><td>$user</td><td>$page</td></tr>";
		}
		$bot = <<<END
			</tbody>
		</table>
		</div>
END;
		return $top . $bod . $bot;
	}

	static function getViews($time = 'all') {
		switch($time) {
			case 'all':
				$records = DataObject::get('PageView');
				break;
			case 'year':
				$pt = time() - 31556926;
				$pt = date("Y-m-d H:i:s", $pt);
				$ct = time() + 10;
				$ct = date("Y-m-d H:i:s", $ct);
				$records = DataObject::get('PageView', "Created >= '$pt' AND Created <= '$ct'");
				break;
			case 'month':
				$pt = time() - 2629744;
				$pt = date("Y-m-d H:i:s", $pt);
				$ct = time() + 10;
				$ct = date("Y-m-d H:i:s", $ct);
				$records = DataObject::get('PageView', "Created >= '$pt' AND Created <= '$ct'");
				break;
			case 'week':
				$pt = time() - 604800;
				$pt = date("Y-m-d H:i:s", $pt);
				$ct = time() + 10;
				$ct = date("Y-m-d H:i:s", $ct);
				$records = DataObject::get('PageView', "Created >= '$pt' AND Created <= '$ct'");
				break;
			case 'day':
				$pt = time() - 86400;
				$pt = date("Y-m-d H:i:s", $pt);
				$ct = time() + 10;
				$ct = date("Y-m-d H:i:s", $ct);
				$records = DataObject::get('PageView', "Created >= '$pt' AND Created <= '$ct'");
				break;
			case 'hour':
				$pt = time() - 3600;
				$pt = date("Y-m-d H:i:s", $pt);
				$ct = time() + 10;
				$ct = date("Y-m-d H:i:s", $ct);
				$records = DataObject::get('PageView', "Created >= '$pt' AND Created <= '$ct'");
				break;
			case 'minute':
				$pt = time() - 60;
				$pt = date("Y-m-d H:i:s", $pt);
				$ct = time() + 10;
				$ct = date("Y-m-d H:i:s", $ct);
				$records = DataObject::get('PageView', "Created >= '$pt' AND Created <= '$ct'");
				break;
			default:
				$records = DataObject::get('PageView');
		}
		$top = <<<END
		<div id="viewtable">
		<table class="sortable-onload-1 rowstyle-alt no-arrow paginate-10 statstable" border="0" cellspacing="1" cellpadding="0">
			<thead>
				<tr><th class="sortable-numeric">ID</th><th class="sortable-sortDatetime">Time</th><th class="sortable-text">Browser</th><th class="sortable-text">OS</th><th class="sortable-text">User</th><th class="sortable-text">Page</th></tr>
			</thead>
			<tbody>
END;
		$bod = "";
		foreach($records as $x) {
			$r = $x->toMap();
			$id = $r['ID'];
			$time = $r['Created'];
			$browser = $r['Browser'] . " " . $r['BrowserVersion'];
			$os = $r['OS'];
			$user = $r['UserID'];
			$page = $r['PageID'];
			$bod .= "<tr><td>$id</td><td>$time</td><td>$browser</td><td>$os</td><td>$user</td><td>$page</td></tr>";
		}
		$bot = <<<END
			</tbody>
		</table>
		</div>
END;
		return $top . $bod . $bot;
	}

	static function BrowserChart($type = "Pie", $color = "blue") {
		$top = <<<END
<div id="browserchart"><canvas id="bchart" height="300" width="500"></canvas></div>

		<script type="text/javascript">\n
END;

		$bot = <<<END

		</script>
END;

		$ds = "var bchartdata = { \n'Set': [";

		$records = DataObject::get('PageView');
		$browsers = array();
		foreach($records as $r) {
			$ra = $r->toMap();
			$cb = $ra['Browser'] . " " . $ra['BrowserVersion'];
			if($browsers[$cb] >= 1) {
				$browsers[$cb]++;
			} else {
				$browsers[$cb] = 1;
			}
		}

		$xt = "xTicks: [";
		$i = 0;
		foreach($browsers as $bn => $bc) {
			$ds .= "[{$i}, {$bc}], ";
			$xt .= "{v:" . $i . ", label:'" . $bn . "'}, ";
			$i++;
		}
		$ds .= "]\n";

		$opts = <<<END
var boptions = {

	axis: {
				lineWidth:			1.0,
				lineColor:			'#000000',
				tickSize:			3.0,
				labelColor:			'#666666',
				labelFont:			'Tahoma',
				labelFontSize:		20,
				labelWidth: 		50.0
		},

	padding: {left: 30, right: 0, top: 10, bottom: 30},

	backgroundColor: '#cccccc',

	colorScheme: '{$color}',\n\n

END;
		$opts .= $xt . "]\n};";


		return $top . $ds . "\n};\n\n" /*. $opts*/ . "\n\n" . $bot;
	}

}

?>
