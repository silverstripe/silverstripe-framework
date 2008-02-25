<?php

/**
 * @package sapphire
 * @subpackage misc
 */

/**
 * Ioncube Performance Suite management
 * @package sapphire
 * @subpackage misc
 */
class IPS extends Controller {
	function index() {
		echo "<h1>Ioncube Performance Suite Management</h1>";
		
		if(function_exists('ips_version')) {
			echo "<p>Running IPS version " . ips_version() . "</p>";

			if($stats = ips_get_stats()) {
				echo "<ul>";
				foreach($stats as $statName => $statValue) {
					echo "<li><b>$statName:</b> $statValue</li>";
				}
				echo "</ul>";
			} else {
				echo "<p style=\"color: orange\">IPS installed but disabled</p>";
			}
			
		} else {
			echo "<p style=\"color: red\">Not running IPS</p>";
		}
	}
	
}


?>