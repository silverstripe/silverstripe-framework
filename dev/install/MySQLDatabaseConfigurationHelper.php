<?php

/**
 * This is a helper class for the SS installer.
 *
 * It does all the specific checking for MySQLDatabase
 * to ensure that the configuration is setup correctly.
 *
 * @package framework
 * @subpackage model
 */
class MySQLDatabaseConfigurationHelper implements DatabaseConfigurationHelper {

	/**
	 * Create a connection of the appropriate type
	 *
	 * @param array $databaseConfig
	 * @param string $error Error message passed by value
	 * @return mixed|null Either the connection object, or null if error
	 */
	protected function createConnection($databaseConfig, &$error) {
		$error = null;
		try {
			switch($databaseConfig['type']) {
				case 'MySQLDatabase':
					$conn = @new MySQLi($databaseConfig['server'], $databaseConfig['username'],
										$databaseConfig['password']);
					if($conn && empty($conn->connect_errno)) {
						$conn->query("SET sql_mode = 'ANSI'");
						return $conn;
					} else {
						$error = ($conn->connect_errno)
							? $conn->connect_error
							: 'Unknown connection error';
						return null;
					}
				case 'MySQLPDODatabase':
					// May throw a PDOException if fails
					$conn = @new PDO('mysql:host='.$databaseConfig['server'], $databaseConfig['username'],
									$databaseConfig['password']);
					if($conn) {
						$conn->query("SET sql_mode = 'ANSI'");
						return $conn;
					} else {
						$error = 'Unknown connection error';
						return null;
					}
				default:
					$error = 'Invalid connection type';
					return null;
			}
		} catch(Exception $ex) {
			$error = $ex->getMessage();
			return null;
		}
	}

	/**
	 * Helper function to quickly extract a column from a mysqi_result
	 *
	 * @param mixed $results mysqli_result or enumerable list of rows
	 * @return array Resulting data
	 */
	protected function column($results) {
		$array = array();
		if($results instanceof mysqli_result) {
			while($row = $results->fetch_array()) {
				$array[] = $row[0];
			}
		} else {
			foreach($results as $row) {
				$array[] = $row[0];
			}
		}
		return $array;
	}

	public function requireDatabaseFunctions($databaseConfig) {
		$data = DatabaseAdapterRegistry::get_adapter($databaseConfig['type']);
		return !empty($data['supported']);
	}

	public function requireDatabaseServer($databaseConfig) {
		$connection = $this->createConnection($databaseConfig, $error);
		$success = !empty($connection);

		return array(
			'success' => $success,
			'error' => $error
		);
	}

	public function getDatabaseVersion($databaseConfig) {
		$conn = $this->createConnection($databaseConfig, $error);
		if(!$conn) {
			return false;
		} elseif($conn instanceof MySQLi) {
			return $conn->server_info;
		} elseif($conn instanceof PDO) {
			return $conn->getAttribute(PDO::ATTR_SERVER_VERSION);
		}
		return false;
	}

	/**
	 * Ensure that the MySQL server version is at least 5.0.
	 *
	 * @param array $databaseConfig Associative array of db configuration, e.g. "server", "username" etc
	 * @return array Result - e.g. array('success' => true, 'error' => 'details of error')
	 */
	public function requireDatabaseVersion($databaseConfig) {
		$version = $this->getDatabaseVersion($databaseConfig);
		$success = false;
		$error = '';
		if($version) {
			$success = version_compare($version, '5.0', '>=');
			if(!$success) {
				$error = "Your MySQL server version is $version. It's recommended you use at least MySQL 5.0.";
			}
		} else {
			$error = "Could not determine your MySQL version.";
		}
		return array(
			'success' => $success,
			'error' => $error
		);
	}

	public function requireDatabaseConnection($databaseConfig) {
		$conn = $this->createConnection($databaseConfig, $error);
		$success = !empty($conn);

		// Check database name only uses valid characters
		if($success && !$this->checkValidDatabaseName($databaseConfig['database'])) {
			$success = false;
			$error = 'Invalid characters in database name.';
		}

		return array(
			'success' => $success,
			'error' => $error
		);
	}

	/**
	 * Determines if a given database name is a valid Silverstripe name.
	 *
	 * @param string $database Candidate database name
	 * @return boolean
	 */
	public function checkValidDatabaseName($database) {

		// Reject filename unsafe characters (cross platform)
		if(preg_match('/[\\\\\/\?%\*\:\|"\<\>\.]+/', $database)) return false;

		// Restricted to characters in the ASCII and Extended ASCII range
		// @see http://dev.mysql.com/doc/refman/5.0/en/identifiers.html
		return preg_match('/^[\x{0001}-\x{FFFF}]+$/u', $database);
	}

	/**
	 * Checks if a specified grant proves that the current user has the specified
	 * permission on the specified database
	 *
	 * @param string $database Database name
	 * @param string $permission Permission to check for
	 * @param string $grant MySQL syntax grant to check within
	 * @return boolean
	 */
	public function checkDatabasePermissionGrant($database, $permission, $grant) {
		// Filter out invalid database names
		if(!$this->checkValidDatabaseName($database)) return false;

		// Escape all valid database patterns (permission must exist on all tables)
		$dbPattern = sprintf(
			'((%s)|(%s)|(%s))',
			preg_quote("\"$database\".*"),
			preg_quote('"%".*'),
			preg_quote('*.*')
		);
		$expression = '/GRANT[ ,\w]+((ALL PRIVILEGES)|('.$permission.'(?! ((VIEW)|(ROUTINE)))))[ ,\w]+ON '.
			$dbPattern.'/i';
		return preg_match($expression, $grant);
	}

	/**
	 * Checks if the current user has the specified permission on the specified database
	 *
	 * @param mixed $conn Connection object
	 * @param string $database Database name
	 * @param string $permission Permission to check
	 * @return boolean
	 */
	public function checkDatabasePermission($conn, $database, $permission) {
		$grants = $this->column($conn->query("SHOW GRANTS FOR CURRENT_USER"));
		foreach($grants as $grant) {
			if($this->checkDatabasePermissionGrant($database, $permission, $grant)) {
				return true;
			}
		}
		return false;
	}

	public function requireDatabaseOrCreatePermissions($databaseConfig) {
		$success = false;
		$alreadyExists = false;
		$conn = $this->createConnection($databaseConfig, $error);
		if($conn) {
			$list = $this->column($conn->query("SHOW DATABASES"));
			if(in_array($databaseConfig['database'], $list)) {
				$success = true;
				$alreadyExists = true;
			} else{
				// If no database exists then check DDL permissions
				$alreadyExists = false;
				$success = $this->checkDatabasePermission($conn, $databaseConfig['database'], 'CREATE');
			}
		}

		return array(
			'success' => $success,
			'alreadyExists' => $alreadyExists
		);
	}

	public function requireDatabaseAlterPermissions($databaseConfig) {
		$conn = $this->createConnection($databaseConfig, $error);
		$success = $this->checkDatabasePermission($conn, $databaseConfig['database'], 'ALTER');
		return array(
			'success' => $success,
			'applies' => true
		);
	}
}
