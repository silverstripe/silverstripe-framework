<?php

/**
 * @package framework
 * @subpackage tests
 */
class MySQLDatabaseConfigurationHelperTest extends SapphireTest {

	/**
	 * Tests that invalid names are disallowed
	 */
	public function testInvalidDatabaseNames() {
		$helper = new MySQLDatabaseConfigurationHelper();

		// Reject filename unsafe characters
		$this->assertEmpty($helper->checkValidDatabaseName('database%name'));
		$this->assertEmpty($helper->checkValidDatabaseName('database?name'));
		$this->assertEmpty($helper->checkValidDatabaseName('database|name'));
		$this->assertEmpty($helper->checkValidDatabaseName('database<name'));
		$this->assertEmpty($helper->checkValidDatabaseName('database"name'));

		// Reject additional characters
		$this->assertEmpty($helper->checkValidDatabaseName('database.name'));
		$this->assertEmpty($helper->checkValidDatabaseName('database\name'));
		$this->assertEmpty($helper->checkValidDatabaseName('database/name'));

		// Reject blank
		$this->assertEmpty($helper->checkValidDatabaseName(""));
	}

	/**
	 * Tests that valid names are allowed
	 */
	public function testValidDatabaseNames() {
		$helper = new MySQLDatabaseConfigurationHelper();

		// Names with spaces
		$this->assertNotEmpty($helper->checkValidDatabaseName('database name'));

		// Basic latin characters
		$this->assertNotEmpty($helper->checkValidDatabaseName('database_name'));
		$this->assertNotEmpty($helper->checkValidDatabaseName('UPPERCASE_NAME'));
		$this->assertNotEmpty($helper->checkValidDatabaseName('name_with_numbers_1234'));

		// Extended unicode names
		$this->assertNotEmpty($helper->checkValidDatabaseName('亝亞亟')); // U+4E9D, U+4E9E, U+4E9F
		$this->assertNotEmpty($helper->checkValidDatabaseName('おかが')); // U+304A, U+304B, U+304C
		$this->assertNotEmpty($helper->checkValidDatabaseName('¶»Ã')); // U+00B6, U+00BB, U+00C3
	}

	public function testDatabaseCreateCheck() {

		$helper = new MySQLDatabaseConfigurationHelper();

		// Accept all privileges
		$this->assertNotEmpty($helper->checkDatabasePermissionGrant(
			'database_name',
			'create',
			"GRANT ALL PRIVILEGES ON *.* TO 'root'@'localhost' IDENTIFIED BY PASSWORD 'XXXX' WITH GRANT OPTION"
		));

		// Accept create (mysql syntax)
		$this->assertNotEmpty($helper->checkDatabasePermissionGrant(
			'database_name',
			'create',
			"GRANT CREATE, SELECT ON *.* TO 'root'@'localhost' IDENTIFIED BY PASSWORD 'XXXX' WITH GRANT OPTION"
		));

		// Accept create on this database only
		$this->assertNotEmpty($helper->checkDatabasePermissionGrant(
			'database_name',
			'create',
			"GRANT ALL PRIVILEGES, CREATE ON \"database_name\".* TO 'root'@'localhost' IDENTIFIED BY PASSWORD 'XXXX'"
				. " WITH GRANT OPTION"
		));

		// Accept create on any database (alternate wildcard syntax)
		$this->assertNotEmpty($helper->checkDatabasePermissionGrant(
			'database_name',
			'create',
			"GRANT CREATE ON \"%\".* TO 'root'@'localhost' IDENTIFIED BY PASSWORD 'XXXX' WITH GRANT OPTION"
		));
	}

	public function testDatabaseCreateFail() {

		$helper = new MySQLDatabaseConfigurationHelper();

		// Don't be fooled by create routine
		$this->assertEmpty($helper->checkDatabasePermissionGrant(
			'database_name',
			'create',
			"GRANT SELECT, CREATE ROUTINE ON *.* TO 'user'@'localhost' IDENTIFIED BY PASSWORD 'XXXX' WITH GRANT OPTION"
		));

		// Or create view
		$this->assertEmpty($helper->checkDatabasePermissionGrant(
			'database_name',
			'create',
			"GRANT CREATE VIEW, SELECT ON *.* TO 'user'@'localhost' IDENTIFIED BY PASSWORD 'XXXX' WITH GRANT OPTION"
		));

		// Don't accept permission if only given on a single subtable
		$this->assertEmpty($helper->checkDatabasePermissionGrant(
			'database_name',
			'create',
			"GRANT CREATE, SELECT ON *.\"onetable\" TO 'user'@'localhost' IDENTIFIED BY PASSWORD 'XXXX' "
				. "WITH GRANT OPTION"
		));

		// Don't accept permission on wrong database
		$this->assertEmpty($helper->checkDatabasePermissionGrant(
			'database_name',
			'create',
			"GRANT ALL PRIVILEGES, CREATE ON \"wrongdb\".* TO 'user'@'localhost' IDENTIFIED BY PASSWORD 'XXXX' "
				. "WITH GRANT OPTION"
		));

		// Don't accept wrong permission
		$this->assertEmpty($helper->checkDatabasePermissionGrant(
			'database_name',
			'create',
			"GRANT UPDATE ON \"%\".* TO 'user'@'localhost' IDENTIFIED BY PASSWORD 'XXXX' WITH GRANT OPTION"
		));

		// Don't accept sneaky table name
		$this->assertEmpty($helper->checkDatabasePermissionGrant(
			'grant create on . to',
			'create',
			"GRANT UPDATE ON \"grant create on . to\".* TO 'user'@'localhost' IDENTIFIED BY PASSWORD 'XXXX' WITH "
				. "GRANT OPTION"
		));
	}
}
