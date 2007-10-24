<?php

/**
 * OpenID storage class
 *
 * @author Markus Lanthaler <markus@silverstripe.com>
 */


/**
 * Require the {@link Auth_OpenID OpenID utility function class}
 */
require_once 'Auth/OpenID.php';


/**
 * Require the {@link Auth_OpenID_OpenIDStore storage class}
 */
require_once 'Auth/OpenID/Interface.php';


/**
 * Require the
 * {@link Auth_OpenID_DatabaseConnection database connection class}
 */
require_once "Auth/OpenID/DatabaseConnection.php";



/**
 * OpenID storage class
 *
 * This is the interface for the store objects the OpenID library uses.
 * It is a single class that provides all of the persistence mechanisms that
 * the OpenID library needs, for both servers and consumers.
 *
 * @author Markus Lanthaler <markus@silverstripe.com>
 */
class OpenIDStorage extends Auth_OpenID_OpenIDStore {

	/**
	 * This static variable is used to decrease the number of table existence
	 * checks
	 *
	 * @todo Create the tables during installation, so we can reduce the
	 *       number of needed SQL queries to a minimum and we don't need this
	 *       variable anymore
	 */
	private static $S_checkedTableExistence = false;


	/**
	 * Constructor
	 *
	 * The constructor will check (once per script execution; with help of a
	 * static variable) if the needed tables exist, otherwise it will create
	 * them.
	 *
	 * @param string $associations_table This is an optional parameter to
	 *                                   specify the name of the table used
	 *                                   for storing associations.
	 *                                   The default value is
	 *                                   'authentication_openid_associations'.
	 * @param string $nonces_table This is an optional parameter to specify
	 *                             the name of the table used for storing
	 *                             nonces.
	 *                             The default value is
	 *                             'authentication_openid_nonces'.
	 *
	 *
	 * @todo Should the max. nonce age be configurable?
	 * @todo Create the tables during installation, so we can reduce the
	 *       number of needed SQL queries.
	 */
	function __construct($associations_table = null, $nonces_table = null) {
		if(is_null($associations_table))
			$associations_table = 'authentication_openid_associations';

		if(is_null($nonces_table))
			$nonces_table = 'authentication_openid_nonces';

		$connection = new OpenIDDatabaseConnection();

		//-------------------------------------------------------------------//
		// This part normally resided in the Auth_OpenID_SQLStore class, but
		// due to a name conflict of the DB class we can't simple inherit from
		// it!

		$this->associations_table_name = "oid_associations";
		$this->nonces_table_name = "oid_nonces";

		// Check the connection object type to be sure it's a PEAR compatible
		// database connection.
		if (!(is_object($connection) &&
				(is_subclass_of($connection, 'db_common') ||
				 is_subclass_of($connection,
												'auth_openid_databaseconnection')))) {
			trigger_error("Auth_OpenID_SQLStore expected PEAR compatible " .
										"connection  object (got ".get_class($connection).")",
										E_USER_ERROR);
			return;
		}

		$this->connection = $connection;


		if($associations_table) {
			$this->associations_table_name = $associations_table;
		}

		if($nonces_table) {
			$this->nonces_table_name = $nonces_table;
		}

		$this->max_nonce_age = 6 * 60 * 60;


		// Be sure to run the database queries with auto-commit mode
		// turned OFF, because we want every function to run in a
		// transaction, implicitly.  As a rule, methods named with a
		// leading underscore will NOT control transaction behavior.
		// Callers of these methods will worry about transactions.
		$this->connection->autoCommit(false);

		// Create an empty SQL strings array.
		$this->sql = array();

		// Call this method (which should be overridden by subclasses)
		// to populate the $this->sql array with SQL strings.
		$this->setSQL();

		// Verify that all required SQL statements have been set, and
		// raise an error if any expected SQL strings were either
		// absent or empty.
		list($missing, $empty) = $this->_verifySQL();

		if($missing) {
			trigger_error("Expected keys in SQL query list: " .
										implode(", ", $missing),
										E_USER_ERROR);
			return;
		}

		if($empty) {
			trigger_error("SQL list keys have no SQL strings: " .
										implode(", ", $empty),
										E_USER_ERROR);
			return;
		}

		// Add table names to queries.
		$this->_fixSQL();
		//--------------------------------------------------------------------------------


		if(self::$S_checkedTableExistence == false) {
			$table_list = (!isset(DB::getConn()->tableList))
				? $table_list = DB::tableList()
				: DB::getConn()->tableList;

			$this->connection->autoCommit(true);

				if(!isset($table_list[strtolower($this->associations_table_name)]))
					$this->create_assoc_table();

				if(!isset($table_list[strtolower($this->nonces_table_name)]))
					$this->create_nonce_table();

			$this->connection->autoCommit(false);
			DB::tableList();

			self::$S_checkedTableExistence = true;
		}
	}


	/**
	 * This method is called by the constructor to set values in $this->sql,
	 * which is an array keyed on sql name.
	 *
	 * @access private
	 */
	function setSQL() {
		$this->sql['nonce_table'] =
				"CREATE TABLE %s (\n".
				"  server_url VARCHAR(2047),\n".
				"  timestamp INTEGER,\n".
				"  salt CHAR(40),\n".
				"  UNIQUE (server_url(255), timestamp, salt)\n".
				") DEFAULT CHARACTER SET latin1";

		$this->sql['assoc_table'] =
				"CREATE TABLE %s (\n".
				"  server_url BLOB,\n".
				"  handle VARCHAR(255),\n".
				"  secret BLOB,\n".
				"  issued INTEGER,\n".
				"  lifetime INTEGER,\n".
				"  assoc_type VARCHAR(64),\n".
				"  PRIMARY KEY (server_url(255), handle)\n".
				") DEFAULT CHARACTER SET latin1";

		$this->sql['set_assoc'] =
				"REPLACE INTO %s VALUES (?, ?, !, ?, ?, ?)";

		$this->sql['get_assocs'] =
				"SELECT handle, secret, issued, lifetime, assoc_type FROM %s ".
				"WHERE server_url = ?";

		$this->sql['get_assoc'] =
				"SELECT handle, secret, issued, lifetime, assoc_type FROM %s ".
				"WHERE server_url = ? AND handle = ?";

		$this->sql['remove_assoc'] =
				"DELETE FROM %s WHERE server_url = ? AND handle = ?";

		$this->sql['add_nonce'] =
				"INSERT INTO %s (server_url, timestamp, salt) VALUES (?, ?, ?)";

		$this->sql['get_expired'] =
				"SELECT server_url FROM %s WHERE issued + lifetime < ?";
	}


	/**
	 * Constitutes the passed value a database error?
	 *
	 * @return Returns TRUE if $value constitutes a database error; returns
	 *         FALSE otherwise.
	 * @access private
	 */
	function isError($value) {
		return ($value === false);
	}


	/**
	 * Create the nonce table
	 *
	 * @return bool Returns TRUE on success, FALSE on failure.
	 */
	function create_nonce_table() {
		return $this->resultToBool(
			$this->connection->query($this->sql['nonce_table']));
	}


	/**
	 * Create the associations table
	 *
	 * @return bool Returns TRUE on success, FALSE on failure.
	 */
	function create_assoc_table() {
		return $this->resultToBool(
			$this->connection->query($this->sql['assoc_table']));
	}


	/**
	 * Check if a table exists
	 *
	 * @param string $table_name Table to check
	 * @return bool Returns TRUE if the table exists, otherwise FALSE.
	 */
		function tableExists($table_name)
		{
			return !$this->isError($this->connection->query(sprintf(
				"SELECT * FROM %s LIMIT 0", $table_name)));
		}


	/**
	 * Converts a query result to a boolean
	 *
	 * @param object $obj Query result
	 * @return bool If the result is a database error according to
	 *              {@link isError()}, this returns FALSE; otherwise, this
	 *              returns TRUE.
	 */
	function resultToBool($obj)
	{
		if($this->isError($obj)) {
			return false;
		} else {
			return true;
		}
	}


	/**
	 * Resets the store by removing all records from the store's tables.
	 */
	function reset()
	{
		$this->connection->query(sprintf("DELETE FROM %s",
																		 $this->associations_table_name));

		$this->connection->query(sprintf("DELETE FROM %s",
																		 $this->nonces_table_name));
	}


	/**
	 * Check if all the required SQL statements are set
	 *
	 * @return array Returns an array of in the form of
	 *               array($missing, $empty) containing the missing and
	 *               empty SQL statements.
	 */
	private function _verifySQL()
	{
		$missing = array();
		$empty = array();

		$required_sql_keys = array('nonce_table',
															 'assoc_table',
															 'set_assoc',
															 'get_assoc',
															 'get_assocs',
															 'remove_assoc',
															 'get_expired',
															 );

		foreach($required_sql_keys as $key) {
			if(!array_key_exists($key, $this->sql)) {
				$missing[] = $key;
			} else if(!$this->sql[$key]) {
				$empty[] = $key;
			}
		}

		return array($missing, $empty);
	}


	/**
	 * Fix SQL statements
	 *
	 * This function replaces the place holders in the set SQL statements
	 * with the right table names.
	 */
	private function _fixSQL()
	{
		$replacements = array(array('value' => $this->nonces_table_name,
																'keys' => array('nonce_table',
																								'add_nonce')
																),
													array('value' => $this->associations_table_name,
																'keys' => array('assoc_table',
																								'set_assoc',
																								'get_assoc',
																								'get_assocs',
																								'remove_assoc',
																								'get_expired')
																)
													);

		foreach($replacements as $item) {
			$value = $item['value'];
			$keys = $item['keys'];

			foreach($keys as $k) {
				if(is_array($this->sql[$k])) {
					foreach($this->sql[$k] as $part_key => $part_value) {
						$this->sql[$k][$part_key] = sprintf($part_value,
																								$value);
					}
				} else {
					$this->sql[$k] = sprintf($this->sql[$k], $value);
				}
			}
		}
	}


	/**
	 * Decode a BLOB field
	 *
	 * @param mixed $blob The BLOB field's value
	 * @return mixed The decoded BLOB value
	 */
	function blobDecode($blob)
	{
		return $blob;
	}


	/**
	 * Encode a value for a BLOB field
	 *
	 * @param mixed $blob The value for the BLOB field
	 * @return mixed The encoded value
	 */
	function blobEncode($blob)
	{
		return "0x" . bin2hex($blob);
	}


	/**
	 * Create the needed tables
	 *
	 * @return boolean Returns TRUE on success or FALSE on failure.
	 */
	function createTables()
	{
		$this->connection->autoCommit(true);
		$n = $this->create_nonce_table();
		$a = $this->create_assoc_table();
		$this->connection->autoCommit(false);

		if($n && $a) {
			return true;
		} else {
			return false;
		}
	}


	/**
	 * Set an association
	 *
	 * Helper function for {@link storeAssociation()}.
	 *
	 * @param string $server_url The server URL
	 * @param string $handle The handle for the association
	 * @param string $secret The secret
	 * @param string $issued When was the association issued?
	 * @param string $lifetime The lifetime of the association
	 * @param string $assoc_type The association type
	 * @return
	 */
	private function _set_assoc($server_url, $handle, $secret, $issued,
											$lifetime, $assoc_type)
	{
			return $this->connection->query($this->sql['set_assoc'],
																			array($server_url,
																						$handle,
																						$secret,
																						$issued,
																						$lifetime,
																						$assoc_type));
	}


	/**
	 * Store an association
	 *
	 * @param string $server_url The URL of the server
	 * @param Auth_OpenID_Association The association object to store
	 */
	function storeAssociation($server_url, Auth_OpenID_Association $association)
	{
		if($this->resultToBool($this->_set_assoc($server_url,
				$association->handle, $this->blobEncode($association->secret),
				$association->issued, $association->lifetime,
				$association->assoc_type))) {
			$this->connection->commit();
		} else {
			$this->connection->rollback();
		}
	}


	/**
	 * Get an association
	 *
	 * This is a helper function for {@link getAssociation()}
	 *
	 * @param string $server_url The server URL
	 * @param string $server_url The handle
	 * @return array Returns the association row or NULL on error.
	 */
	private function _get_assoc($server_url, $handle)
	{
		$result = $this->connection->getRow($this->sql['get_assoc'],
																				array($server_url, $handle));
		if($this->isError($result)) {
			return null;
		} else {
			return $result;
		}
	}


	/**
	 * Get all associations for a specific server URL
	 *
	 * This is a helper function for {@link getAssociation()}
	 *
	 * @param string $server_url The server URL
	 * @return array Returns the association rows or an empty array on error.
	 */
	private function _get_assocs($server_url)
	{
		$result = $this->connection->getAll($this->sql['get_assocs'],
																				array($server_url));

		if($this->isError($result)) {
			return array();
		} else {
			return $result;
		}
	}


	/**
	 * Get an association
	 *
	 * @param string $server_url The URL of the server for which the
	 *                           associations should be retrieved
	 * @param string $handle Optional: The handle if one specific association
	 *                       should be returned. If set to NULL the most
	 *                       recently issued one will be returned.
	 * @return Auth_OpenID_Association The association or NULL if not found.
	 */
	function getAssociation($server_url, $handle = null)
	{
		if($handle !== null) {
			$assoc = $this->_get_assoc($server_url, $handle);

			$assocs = array();
			if($assoc) {
				$assocs[] = $assoc;
			}
		} else {
			$assocs = $this->_get_assocs($server_url);
		}

		if(!$assocs || (count($assocs) == 0)) {
			return null;
		} else {
			$associations = array();

			foreach ($assocs as $assoc_row) {
				$assoc = new Auth_OpenID_Association($assoc_row['handle'],
																						 $assoc_row['secret'],
																						 $assoc_row['issued'],
																						 $assoc_row['lifetime'],
																						 $assoc_row['assoc_type']);

				$assoc->secret = $this->blobDecode($assoc->secret);

				if($assoc->getExpiresIn() == 0) {
					$this->removeAssociation($server_url, $assoc->handle);
				} else {
					$associations[] = array($assoc->issued, $assoc);
				}
			}

			if($associations) {
				$issued = array();
				$assocs = array();
				foreach($associations as $key => $assoc) {
						$issued[$key] = $assoc[0];
						$assocs[$key] = $assoc[1];
				}

				array_multisort($issued, SORT_DESC, $assocs, SORT_DESC,
												$associations);

				// return the most recently issued one.
				list($issued, $assoc) = $associations[0];
				return $assoc;
			} else {
				return null;
			}
		}
	}


	/**
	 * Remove an association
	 *
	 * @param string $server_url The server URL
	 * @param string $server_url The handle
	 * @return array Returns the association row or NULL on error.
	 */
	function removeAssociation($server_url, $handle)
	{
		if($this->_get_assoc($server_url, $handle) == null) {
			return false;
		}

		if($this->resultToBool($this->connection->query(
				$this->sql['remove_assoc'], array($server_url, $handle)))) {
			$this->connection->commit();
		} else {
			$this->connection->rollback();
		}

		return true;
	}


	/**
	 * Get the expired associations
	 *
	 * @return array Returns an array of expired server URLs
	 */
	function getExpired()
	{
		$sql = $this->sql['get_expired'];
		$result = $this->connection->getAll($sql, array(time()));

		$expired = array();

		foreach($result as $row) {
			$expired[] = $row['server_url'];
		}

		return $expired;
	}


	/**
	 * Store a nonce
	 *
	 * This is a helper function for {@link useNonce()}.
	 *
	 * @param string $server_url The URL of the server for which the nonce is
	 *                           used
	 * @param string $timestamp The timestamp of the creation of the nonce
	 * @param string $salt The value of the nonce
	 * @return bool Returns TRUE on success, FALSE on failure.
	 */
	private function _add_nonce($server_url, $timestamp, $salt)
	{
		$sql = $this->sql['add_nonce'];
		$result = $this->connection->query($sql, array($server_url,
																									 $timestamp,
																									 $salt));
		if($this->isError($result)) {
			$this->connection->rollback();
		} else {
			$this->connection->commit();
		}
		return $this->resultToBool($result);
	}


	/**
	 * Store a nonce
	 *
	 * @param string $server_url The URL of the server for which the nonce is
	 *                           used
	 * @param string $timestamp The timestamp of the creation of the nonce
	 * @param string $salt The value of the nonce
	 * @return bool Returns TRUE on success, FALSE on failure.
	 */
	function useNonce($server_url, $timestamp, $salt)
	{
		return $this->_add_nonce($server_url, $timestamp, $salt);
	}


	/**
	 * "Octifies" a binary string by returning a string with escaped octal
	 * bytes
	 *
	 * This is used for preparing binary data for PostgreSQL BYTEA fields.
	 *
	 * @param string $str The binary string to octify
	 * @return string The octified string
	 */
	private function _octify($str)
	{
			$result = "";
			for ($i = 0; $i < Auth_OpenID::bytes($str); $i++) {
					$ch = substr($str, $i, 1);
					if ($ch == "\\") {
							$result .= "\\\\\\\\";
					} else if (ord($ch) == 0) {
							$result .= "\\\\000";
					} else {
							$result .= "\\" . strval(decoct(ord($ch)));
					}
			}
			return $result;
	}


	/**
	 * "Unoctifies" octal-escaped data from PostgreSQL and returns the
	 * resulting ASCII (possibly binary) string.
	 *
	 * @param string $str The octified string
	 * @return string The unoctified (binary) string
	 */
	private function _unoctify($str)
	{
		$result = "";
		$i = 0;
		while($i < strlen($str)) {
			$char = $str[$i];
			if($char == "\\") {
				// Look to see if the next char is a backslash and
				// append it.
				if ($str[$i + 1] != "\\") {
					$octal_digits = substr($str, $i + 1, 3);
					$dec = octdec($octal_digits);
					$char = chr($dec);
					$i += 4;
				} else {
					$char = "\\";
					$i += 2;
				}
			} else {
				$i += 1;
			}

			$result .= $char;
		}

		return $result;
	}
}



/**
 * Wrapper that emulates PEAR connection functionality which is needed for
 * the {@link OpenIDStorage} class.
 *
 * @author Markus Lanthaler <markus@silverstripe.com>
 *
 * @todo If the new database abstraction adds support for transactions and
 *       prepared statements (placeholders) use that code without emulating
 *       it here.
 */
class OpenIDDatabaseConnection extends Auth_OpenID_DatabaseConnection {

	/**
	 * Run an SQL query with the specified parameters, if any.
	 *
	 * @param string $sql An SQL string with placeholders. A '?' is used for
	 *                    values to put into quotes, and a '!' for values that
	 *                    should not be quoted.
	 * @param array $params An array of parameters to insert into the SQL
	 *                      string using this connection's escaping mechanism.
	 * @return mixed $result The result of calling this connection's internal
	 *                       query function.
	 *                       The type of result depends on the underlying
	 *                       database engine. This method is usually used when
	 *                       the result of a query is not important, like a
	 *                       DDL query.
	 */
	public function query($sql, $params = array()) {
		if(($sql = $this->generateQuery($sql, $params)) === false)
			return false;

		return DB::query($sql);
	}


	/**
	 * Run an SQL query and return the first column of the first row
	 * of the result set, if any.
	 *
	 * @param string $sql An SQL string with placeholders. A '?' is used for
	 *                    values to put into quotes, and a '!' for values that
	 *                    should not be quoted.
	 * @param array $params An array of parameters to insert into the SQL
	 *                      string using this connection's escaping mechanism.
	 * @return mixed $result The value of the first column of the first row of
	 *                       the result set.
	 *                       FALSE if no such result was found.
	 */
	public function getOne($sql, $params = array()) {
		if(($sql = $this->generateQuery($sql, $params)) === false)
			 return false;

		if(($result = DB::query($sql)) === false)
			return false;

		return $result->value();
	}


	/**
	 * Run an SQL query and return the first row of the result set, if
	 * any.
	 *
	 * @param string $sql An SQL string with placeholders. A '?' is used for
	 *                    values to put into quotes, and a '!' for values that
	 *                    should not be quoted.
	 * @param array $params An array of parameters to insert into the SQL
	 *                      string using this connection's escaping mechanism.
	 * @return array $result The first row of the result set, if any, keyed on
	 *                       column name.
	 *                       FALSE if no such result was found.
	 */
	public function getRow($sql, $params = array()) {
		if(($sql = $this->generateQuery($sql, $params)) === false)
			return false;

		if(($result = DB::query($sql)) === false)
			return false;

		return $result->record();
	}


	/**
	 * Run an SQL query with the specified parameters, if any.
	 *
	 * @param string $sql An SQL string with placeholders. A '?' is used for
	 *                    values to put into quotes, and a '!' for values that
	 *                    should not be quoted.
	 * @param array $params An array of parameters to insert into the SQL
	 *                      string using this connection's escaping mechanism.
	 * @return array $result An array of arrays representing the result of the
	 *                       query; each array is keyed on column name.
	 */
	public function getAll($sql, $params = array()) {
		if(($sql = $this->generateQuery($sql, $params)) === false)
			 return false;

		if(($result = DB::query($sql)) === false)
			return false;

		for($result_array = array(); $result->valid(); $result->next()) {
			array_push($result_array, $result->current());
		}

		return $result_array;
	}


	/**
	 * Sets auto-commit mode on this database connection.
	 *
	 * @param bool $mode TRUE if auto-commit is to be used; FALSE if not.
	 */
	public function autoCommit($mode) {
	}


	/**
	 * Starts a transaction on this connection, if supported.
	 */
	public function begin() {
	}


	/**
	 * Commits a transaction on this connection, if supported.
	 */
	public function commit() {
	}


	/**
	 * Performs a rollback on this connection, if supported.
	 */
	public function rollback() {
	}


	/**
	 * Formats input so it can be safely used in a query
	 *
	 * @param string $sql An SQL string with placeholders. A '?' is used for
	 *                    values to put into quotes, and a '!' for values that
	 *                    should not be quoted.
	 * @param array $params An array of parameters to insert into the SQL
	 *                      string using this connection's escaping mechanism.
	 * @return bool|string $result A valid SQL string with all parameters
	 *                             properly escaped or FALSE if an invalid SQL
	 *                             string or an invalid number of parameters
	 *                             was passed.
	 */
	private function generateQuery($sql, $params = array()) {
		$tokens   = preg_split('/((?<!\\\)[&?!])/', $sql, -1,
													 PREG_SPLIT_DELIM_CAPTURE);
		$token     = 0;
		$types     = array();
		$newtokens = array();

		foreach ($tokens as $val) {
			switch ($val) {
				case '?':
					$types[$token++] = 'SCALAR';
					break;
				case '!':
					$types[$token++] = 'MISC';
					break;
				default:
					$newtokens[] = preg_replace('/\\\([&?!])/', "\\1", $val);
			}
		}

		if(count($types) != count($params))
			return false;


		$realquery = $newtokens[0];
		$i = 0;

		foreach($params as $value) {
			if($types[$i] == 'SCALAR') {
				$realquery .= $this->quote($value);
			} else {
				$realquery .= $value;
			}

			$realquery .= $newtokens[++$i];
		}

		return $realquery;
	}


	/**
	 * Formats input so it can be safely used in a query
	 *
	 * @param mixed $in The data to be formatted
	 * @return mixed The formatted data. The format depends on the input's
	 *               PHP type-
	 */
	private function quote($in) {
		if(is_int($in)) {
			return $in;
		} elseif(is_float($in)) {
			return "'" . Convert::raw2sql(
				str_replace(',', '.', strval(floatval($in)))) . "'";
		} elseif(is_bool($in)) {
			return ($in) ? '1' : '0';
		} elseif(is_null($in)) {
			return 'NULL';
		} else {
			return "'" . Convert::raw2sql($in) . "'";
		}
	}
}


?>