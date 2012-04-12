<?php
/**
 * Interface for database helper classes.
 * @package framework
 */
interface DatabaseConfigurationHelper {

	/**
	 * Ensure that the database function for connectivity is available.
	 * If it is, we assume the PHP module for this database has been setup correctly.
	 * 
	 * @param array $databaseConfig Associative array of db configuration, e.g. "server", "username" etc
	 * @return boolean
	 */
	public function requireDatabaseFunctions($databaseConfig);

	/**
	 * Ensure that the database server exists.
	 * @param array $databaseConfig Associative array of db configuration, e.g. "server", "username" etc
	 * @return array Result - e.g. array('okay' => true, 'error' => 'details of error')
	 */
	public function requireDatabaseServer($databaseConfig);

	/**
	 * Ensure a database connection is possible using credentials provided.
	 * The established connection resource is returned with the results as well.
	 * 
	 * @param array $databaseConfig Associative array of db configuration, e.g. "server", "username" etc
	 * @return array Result - e.g. array('okay' => true, 'connection' => mysql link, 'error' => 'details of error')
	 */
	public function requireDatabaseConnection($databaseConfig);

	/**
	 * Ensure that the database connection is able to use an existing database,
	 * or be able to create one if it doesn't exist.
	 * 
	 * @param array $databaseConfig Associative array of db configuration, e.g. "server", "username" etc
	 * @return array Result - e.g. array('okay' => true, 'existsAlready' => 'true')
	 */
	public function requireDatabaseOrCreatePermissions($databaseConfig);

}
