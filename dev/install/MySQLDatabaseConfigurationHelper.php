<?php
/**
 * This is a helper class for the SS installer.
 * 
 * It does all the specific checking for MySQLDatabase
 * to ensure that the configuration is setup correctly.
 * 
 * @package mssql
 */
class MySQLDatabaseConfigurationHelper implements DatabaseConfigurationHelper {

	/**
	 * Ensure that the database function for connectivity is available.
	 * If it is, we assume the PHP module for this database has been setup correctly.
	 * 
	 * @param array $databaseConfig Associative array of db configuration, e.g. "server", "username" etc
	 * @return boolean
	 */
	public function requireDatabaseFunctions($databaseConfig) {
		return (function_exists('mysql_connect')) ? true : false;
	}

	/**
	 * Ensure that the database server exists.
	 * @param array $databaseConfig Associative array of db configuration, e.g. "server", "username" etc
	 * @return array Result - e.g. array('okay' => true, 'error' => 'details of error')
	 */
	public function requireDatabaseServer($databaseConfig) {
		$okay = false;
		$conn = @mysql_connect($databaseConfig['server'], null, null);
		if($conn || mysql_errno() < 2000) {
			$okay = true;
		} else {
			$okay = false;
			$error = mysql_error();
		}
		return array(
			'okay' => $okay,
			'error' => $error
		);
	}

	/**
	 * Ensure a database connection is possible using credentials provided.
	 * The established connection resource is returned with the results as well.
	 * 
	 * @param array $databaseConfig Associative array of db configuration, e.g. "server", "username" etc
	 * @return array Result - e.g. array('okay' => true, 'connection' => mysql link, 'error' => 'details of error')
	 */
	public function requireDatabaseConnection($databaseConfig) {
		$okay = false;
		$conn = @mysql_connect($databaseConfig['server'], $databaseConfig['username'], $databaseConfig['password']);
		if($conn) {
			$okay = true;
		} else {
			$okay = false;
			$error = mysql_error();
		}
		return array(
			'okay' => $okay,
			'connection' => $conn,
			'error' => $error
		);
	}

	/**
	 * Ensure that the database connection is able to use an existing database,
	 * or be able to create one if it doesn't exist.
	 * 
	 * @param array $databaseConfig Associative array of db configuration, e.g. "server", "username" etc
	 * @return array Result - e.g. array('okay' => true, 'existsAlready' => 'true')
	 */
	public function requireDatabaseOrCreatePermissions($databaseConfig) {
		$okay = false;
		$existsAlready = false;
		$conn = @mysql_connect($databaseConfig['server'], $databaseConfig['username'], $databaseConfig['password']);
		if(@mysql_select_db($databaseConfig['database'], $conn)) {
			$okay = true;
			$existsAlready = true;
		} else {
			if(@mysql_query("CREATE DATABASE testing123", $conn)) {
				mysql_query("DROP DATABASE testing123", $conn);
				$okay = true;
				$existsAlready = false;
			}
		}
		return array(
			'okay' => $okay,
			'existsAlready' => $existsAlready
		);
	}

}