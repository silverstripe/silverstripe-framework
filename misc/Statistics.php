<?php
/**
 * Statistics class for gathering and formatting of statistical data for tables and charts in
 * both public and administrative contexts.
 * 
 * @package cms
 */

class Statistics extends Controller {

	/**
	 * Enable logging of browser information
	 * based on the third-party Browscap library.
	 * Turned off by default.
	 * 
	 * @var boolean 
	 */
	public static $browscap_enabled = false;

	static function trend_chart($table, $filter = "day", $name, $type, $color) {
	        $trendstrl = _t('Statistics.TRENDS', 'Trends');
		$legendtrl = _t('Statistics.LEGEND', 'Legend');
		$top = <<<HTML
<div id="trendchart" style="display: none">
<h2>{$trendstrl}</h2>
<div><canvas id="chart" height="400" width="700"></canvas></div>
<div id="chart_legend"><legend>{$legendtrl}</legend></div>
</div>
		<script type="text/javascript">\n
HTML;

		$bot = <<<HTML
var chart = new Plotr.{$type}Chart('chart',options);

		chart.addDataset(chartdata);


		chart.render();

		chart.addLegend($('chart_legend'));

		</script>
HTML;

		$ds = "var chartdata = { \n";

		foreach($table as $class) {
			$record = DataObject::get($class, "", $class.".Created DESC");
			$total = $record->TotalItems();

			$props = $record->toArray();
			$props = $props[0]->toMap();
			$startyear = new SSDatetime($props['Created']);
			$startyear = $startyear->Format('Y');
			$startmonth = new SSDatetime($props['Created']);
			$startmonth = $startmonth->Format('m');


			if($filter == "day") {
				$days = new SSDatetime($props['Created']);
				$days = $days->Format('t');

				$sum = 0;

				$ds .= "{$class}: [";

				for($i = 1; $i <= $days; $i++) {

					foreach($record as $v) {
						$props = $v->toMap();
						$currdate = new SSDatetime($props['Created']);
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
						$currdate = new SSDatetime($props['Created']);
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

		$opts = <<<HTML
var options = {

	axisLabelFontSize:		10,

	padding: {left: 30, right: 0, top: 10, bottom: 30},

	backgroundColor: '#cccccc',

	colorScheme: '{$color}',\n\n

HTML;
		$opts .= $xt . "]\n};";


			return $top . $ds . "\n};\n\n" . $opts . "\n\n" . $bot;
	}

	static function user_record_table() {
		$records = DataObject::get('Member');
		$baseURL = Director::baseURL();
		$registereduserstrl = _t('Statistics.REGISTEREDUSERS', 'Registered Users');
		$exporttrl = _t('Statistics.CSVEXPORT', 'Export as CSV');
		$idtrl = _t('Statistics.ID', 'ID');
		$emailtrl = _t('Statistics.EMAIL', 'Email');
		$joinedtrl = _t('Statistics.JOINED');
		$top = <<<HTML
		<div id="usertable" style="display: none">
		<h2>{$registereduserstrl}</h2>
		<p><a href="$baseURL/admin/statistics/usercsv">{$exporttrl}</a></p>
		<table class="sortable-onload-1 rowstyle-alt no-arrow paginate-10 statstable" border="0" cellspacing="1" cellpadding="0">
			<thead>
			        <tr><th class="sortable-numeric">{$idtrl}</th><th class="sortable-text">{$emailtrl}</th><th class="sortable-sortDatetime">{$joinedtrl}</th></tr>
			</thead>
			<tbody>
HTML;
		$bod = "";
		foreach($records as $x) {
			$r = $x->toMap();
			$id = $r['ID'];
			$email = $r['Email'];
			$date = date("F j, Y G:i:s", strtotime($r['Created']));
			$bod .= "<tr><td>$id</td><td>$email</td><td>$date</td></tr>";
		}
		$bot = <<<HTML
			</tbody>
		</table>
		</div>
HTML;
		//$js = "\n\n<script type=\"text/javascript\">\n\tvar usertable = new TableKit('usertable');\nusertable.initialize();\n</script>\n";
		return $top . $bod . $bot;
	}

	static function collect() {
		$hit = new PageView();
		$hit->record();
		return;
	}

	static function get_recent_views($limit = 15) {
		$records = DataObject::get('PageView', null, 'Created DESC', null, $limit);
		$recentpvtrl = _t('Statistics.RECENTPAGEVIEWS', 'Recent Page Views');
		$idtrl = _t('Statistics.ID', 'ID');
		$timetrl = _t('Statistics.TIME', 'Time');
		$browsertrl = _t('Statistics.BROWSER', 'Browser');
		$ostrl = _t('Statistics.OSABREV', 'OS');
		$usertrl = _t('Statistics.USER', 'User');
		$pagetrl = _t('Statistics.PAGE', 'Page');
		$top = <<<HTML
		<div id="recentviewtable">
		<h2>{$recentpvtrl}</h2>
		<table class="sortable-onload-1 rowstyle-alt no-arrow paginate-10 statstable" border="0" cellspacing="1" cellpadding="0">
			<thead>
			        <tr><th class="sortable-numeric">{$idtrl}</th><th class="sortable-sortDatetime">{$timetrl}</th><th class="sortable-text">{$browsertrl}</th><th class="sortable-text">{$ostrl}</th><th>{$usertrl}</th><th class="sortable-text">{$pagetrl}</th></tr>
			</thead>
			<tbody>
HTML;
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
		$bot = <<<HTML
			</tbody>
		</table>
		</div>
HTML;
		return $top . $bod . $bot;
	}

	static function get_views($time = 'all') {
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
		$baseURL = Director::baseURL();
		$pageviewstrl = _t('Statistics.PAGEVIEWS', 'Page Views');
		$idtrl = _t('Statistics.ID', 'ID');
		$timetrl = _t('Statistics.TIME', 'Time');
		$browsertrl = _t('Statistics.BROWSER', 'Browser');
		$ostrl = _t('Statistics.OSABREV', 'OS');
		$usertrl = _t('Statistics.USER', 'User');
		$pagetrl = _t('Statistics.PAGE', 'Page');
		$exporttrl = _t('Statistics.CSVEXPORT', 'Export as CSV');
		$top = <<<HTML
		<div id="viewtable" style="display: none">
		<h2>{$pageviewstrl}</h2>
		<p><a href="$baseURL/admin/statistics/viewcsv">{$exporttrl}</a></p>
		<table class="sortable-onload-1 rowstyle-alt no-arrow paginate-10 statstable" border="0" cellspacing="1" cellpadding="0">
			<thead>
				<tr><th class="sortable-numeric">{$idtrl}</th><th class="sortable-sortDatetime">{$timetrl}</th><th class="sortable-text">{$browsertrl}</th><th class="sortable-text">{$ostrl}</th><th class="sortable-text">{$usertrl}</th><th class="sortable-text">{$pagetrl}</th></tr>
			</thead>
			<tbody>
HTML;
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
		$bot = <<<HTML
			</tbody>
		</table>
		</div>
HTML;
		return $top . $bod . $bot;
	}

	static function browser_chart($type = "Pie", $color = "blue") {
	        $browserstrl = _t('Statistics.BROWSERS', 'Browsers');
		$top = <<<HTML
<div id="browserchart" style="display: none">
<h2>{$browserstrl}</h2>
<div><canvas id="bchart" height="400" width="700"></canvas></div>
</div>

		<script type="text/javascript">\n
HTML;

		$bot = <<<HTML
var bchart = new Plotr.{$type}Chart('bchart', boptions);

		bchart.addDataset(bchartdata);


		bchart.render();

		</script>
HTML;

		$ds = "var bchartdata = { \n'Set': [";

		$records = DataObject::get('PageView');
		$browsers = array();
		foreach($records as $r) {
			$ra = $r->toMap();
			$cb = $ra['Browser'] . " " . $ra['BrowserVersion'];
			if(isset($browsers[$cb]) && $browsers[$cb] >= 1) {
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

		$opts = <<<HTML
var boptions = {

	axisLabelFontSize:		10,


	padding: {left: 30, right: 0, top: 10, bottom: 30},

	backgroundColor: '#cccccc',

	colorScheme: '{$color}',\n\n

HTML;
		$opts .= $xt . "]\n};";


		return $top . $ds . "\n};\n\n" . $opts . "\n\n" . $bot;
	}

	static function os_chart($type = "Pie", $color = "blue") {
	        $ostrl = _t('Statistics.OS', 'Operating Systems');
		$top = <<<HTML
<div id="oschart" style="display: none">
<h2>{$ostrl}</h2>
<div><canvas id="ochart" height="400" width="700"></canvas></div>
</div>

		<script type="text/javascript">\n
HTML;

		$bot = <<<HTML
var ochart = new Plotr.{$type}Chart('ochart', ooptions);

		ochart.addDataset(ochartdata);


		ochart.render();

		</script>
HTML;

		$ds = "var ochartdata = { \n'Set': [";

		$records = DataObject::get('PageView');
		$oss = array();
		foreach($records as $r) {
			$ra = $r->toMap();
			$cb = $ra['OS'];
			if(isset($oss[$cb]) && $oss[$cb] >= 1) {
				$oss[$cb]++;
			} else {
				$oss[$cb] = 1;
			}
		}

		$xt = "xTicks: [";
		$i = 0;
		foreach($oss as $bn => $bc) {
			$ds .= "[{$i}, {$bc}], ";
			$xt .= "{v:" . $i . ", label:'" . $bn . "'}, ";
			$i++;
		}
		$ds .= "]\n";

		$opts = <<<HTML
var ooptions = {

	axisLabelFontSize:		10,


	padding: {left: 30, right: 0, top: 10, bottom: 30},

	backgroundColor: '#cccccc',

	colorScheme: '{$color}',\n\n

HTML;
		$opts .= $xt . "]\n};";


		return $top . $ds . "\n};\n\n" . $opts . "\n\n" . $bot;
	}

	static function activity_chart($type = "Pie", $color = "blue") {
	        $useracttrl = _t('Statistics.USERACTIVITY', 'User Activity');
		$top = <<<HTML
<div id="uacchart" style="display: none">
<h2>{$useracttrl}</h2>
<div><canvas id="uchart" height="400" width="700"></canvas></div>
</div>

		<script type="text/javascript">\n
HTML;

		$bot = <<<HTML
var uacchart = new Plotr.{$type}Chart('uchart', uacoptions);

		uacchart.addDataset(uacchartdata);


		uacchart.render();

		</script>
HTML;

		$ds = "var uacchartdata = { \n'Set': [";

		$records = DataObject::get('PageView');
		$users = array();
		foreach($records as $r) {
			$ra = $r->toMap();
			$cb = $ra['UserID'];
			if($cb == -1) {
				continue;
			}
			if(isset($users[$cb]) && $users[$cb] >= 1) {
				$users[$cb]++;
			} else {
				$users[$cb] = 1;
			}
		}

		$xt = "xTicks: [";
		$i = 0;
		foreach($users as $bn => $bc) {
			$ds .= "[{$i}, {$bc}], ";
			$xt .= "{v:" . $i . ", label:'" . $bn . "'}, ";
			$i++;
		}
		$ds .= "]\n";

		$opts = <<<HTML
var uacoptions = {

	axisLabelFontSize:		10,


	padding: {left: 30, right: 0, top: 10, bottom: 30},

	backgroundColor: '#cccccc',

	colorScheme: '{$color}',\n\n

HTML;
		$opts .= $xt . "]\n};";


		return $top . $ds . "\n};\n\n" . $opts . "\n\n" . $bot;
	}

	static function get_view_csv($time = 'all') {
		$data = "ID, ClassName, Created, LastEdited, Browser, FromExternal, Referrer, SearchEngine, Keywords, OS, PageID, UserID, BrowserVersion, IP\n";

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

		foreach($records as $x) {
			$r = $x->toMap();
			$data .= implode(', ', $r) . "\n";
		}

		return $data;
	}

	static function get_user_csv() {
		$data = "ID, ClassName, Created, LastEdited, FirstName, Surname, Email, Password, NumVisit, LastVisited, Bounced, AutoLoginHash, AutoLoginExpired, BlacklistedEmail, RememberLoginToken, IdentityURL, PasswordEncryption, Salt\n";

		$records = DataObject::get('Member');

		foreach($records as $x) {
			$r = $x->toMap();
			$data .= implode(', ', $r) . "\n";
		}
		return $data;
	}

}

?>
