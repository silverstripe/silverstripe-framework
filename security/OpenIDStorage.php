<?php

/**
 * OpenID storage class
 *
 * @author Markus Lanthaler <markus@silverstripe.com>
 */


/**
 * Require the {@link Auth_OpenID_MySQLStore MySQL storage class}
 */
require_once "Auth/OpenID/MySQLStore.php";


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
class OpenIDStorage extends Auth_OpenID_MySQLStore {

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
	function __construct($associations_table = null, $nonces_table = null)
	{
		if(is_null($associations_table))
			$associations_table = 'authentication_openid_associations';

		if(is_null($nonces_table))
			$nonces_table = 'authentication_openid_nonces';

		$connection = new OpenIDDatabaseConnection();

		parent::__construct($connection, $associations_table, $nonces_table);


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
	function setSQL()
	{
		parent::setSQL();

		$this->sql['nonce_table'] =
				"CREATE TABLE %s (\n".
				"  server_url VARCHAR(2047),\n".
				"  timestamp INTEGER,\n".
				"  salt CHAR(40),\n".
				"  UNIQUE (server_url(255), timestamp, salt)\n".
				")";

		$this->sql['assoc_table'] =
				"CREATE TABLE %s (\n".
				"  server_url BLOB,\n".
				"  handle VARCHAR(255),\n".
				"  secret BLOB,\n".
				"  issued INTEGER,\n".
				"  lifetime INTEGER,\n".
				"  assoc_type VARCHAR(64),\n".
				"  PRIMARY KEY (server_url(255), handle)\n".
				")";
	}


	/**
	 * Constitutes the passed value a database error?
	 *
	 * @return Returns TRUE if $value constitutes a database error; returns
	 *         FALSE otherwise.
	 * @access private
	 */
	function isError($value)
	{
		return ($value === false);
	}


	/**
	 * Create the nonce table
	 *
	 * @return bool Returns TRUE on success, FALSE on failure.
	 */
	function create_nonce_table()
	{
		return $this->resultToBool(
			$this->connection->query($this->sql['nonce_table']));
	}


	/**
	 * Create the associations table
	 *
	 * @return bool Returns TRUE on success, FALSE on failure.
	 */
	function create_assoc_table()
	{
		return $this->resultToBool(
			$this->connection->query($this->sql['assoc_table']));
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
	public function query($sql, $params = array())
	{
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
	public function getOne($sql, $params = array())
	{
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
	public function getRow($sql, $params = array())
	{
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
	public function getAll($sql, $params = array())
	{
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
	public function autoCommit($mode)
	{
	}


	/**
	 * Starts a transaction on this connection, if supported.
	 */
	public function begin()
	{
	}


	/**
	 * Commits a transaction on this connection, if supported.
	 */
	public function commit()
	{
	}


	/**
	 * Performs a rollback on this connection, if supported.
	 */
	public function rollback()
	{
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
	private function generateQuery($sql, $params = array())
	{
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
	private function quote($in)
	{
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