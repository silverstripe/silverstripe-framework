title: MySQL SSL Support
summary: Setting up MySQL SSL certificates to work with Silverstripe

# MySQL SSL Support: Why do I need it?

In a typical Silverstripe set up, you will only need to use a single host to function as the web server, email server, database server, among others.

In some cases, however, you may be required to connect to a database on a remote host. Connecting to a remote host without SSL encryption exposes your data to [packet sniffing](http://www.linuxjournal.com/content/packet-sniffing-basics) and may compromise the security of your Silverstripe instance.

This article demonstrates how to generate SSL certificates using MySQL and implementing them in Silverstripe.

<div class="notice" markdown='1'>
This article assumes that you have `MySQL` and `OpenSSL` installed.
</div>


## Generating Certificates

There are three components to an SSL certificate implementation. The first two components are the ***private key***, and the ***public certificate***, which are mathematically-generated, symetrical pieces of the puzzle that allow [public-key cryptography](https://en.wikipedia.org/wiki/Public-key_cryptography) to work. The third component is the [Certificate Authority (CA) certificate](https://en.wikipedia.org/wiki/Certificate_authority) that signs the pubic key to prove its validity.

In the case of MySQL, we will need to generate three sets of certificates, namely:

- the CA key and certificate
- the server key and certificate
- the client key and certificate

We also need to sign the certificates with our generated CA.

The commands below illustrate how to do so on your MySQL host.

<div class="notice" markdown='1'>
The following commands will work on Linux/Unix based servers. For other servers such as windows, refer to the [MySQL documentation](https://dev.mysql.com/doc/refman/5.7/en/creating-ssl-files-using-openssl.html)
</div>


	:::bash

	# Create directory
	sudo mkdir ssl
	cd ssl

	# Generate CA key and CA cert
	sudo openssl genrsa 2048 | sudo tee -a ca-key.pem
	sudo openssl req -new -x509 -nodes -days 365000 -key ca-key.pem -out ca-cert.pem

	# Generate SERVER key and server certificate signing request
	# IMPORTANT: the common name of the certificate should match the domain name of your host!
	sudo openssl rsa -in server-key.pem -out server-key.pem
	sudo openssl req -newkey rsa:2048 -days 365000 -nodes -keyout server-key.pem -out server-req.pem

	# Generate and sign SERVER certificate
	sudo openssl x509 -req -in server-req.pem -days 365000 -CA ca-cert.pem -CAkey ca-key.pem -set_serial 01 -out server-cert.pem

	# Generate CLIENT key and certificate signing request
	sudo openssl rsa -in client-key.pem -out client-key.pem
	sudo openssl req -newkey rsa:2048 -days 365000 -nodes -keyout client-key.pem -out client-req.pem

	# Generate and sign CLIENT certificate
	sudo openssl x509 -req -in client-req.pem -days 365000 -CA ca-cert.pem -CAkey ca-key.pem -set_serial 01 -out client-cert.pem

	# Verify validity of generated certificates
	sudo openssl verify -CAfile ca-cert.pem server-cert.pem client-cert.pem

<div class="warning" markdown='1'>
After generating the certificates, make sure to set the correct permissions to prevent unauthorized access to your keys! 

It is critical that the key files (files ending in *key.pem) are kept secret. Once these files are exposed, you will need to regenerate the certificates to prevent exposing your data traffic. 
</div>

	:::bash
	# Set permissions readonly permissions and change owner to root
	sudo chown root:root *.pem 
	sudo chmod 440 *.pem

	# Server certificates need to be readable by mysql
	sudo chgrp mysql server*.pem
	sudo mv *.pem /etc/mysql/ssl


## Setting up MySQL to use SSL certificates

<div class="notice" markdown='1'>
For Debian/Ubuntu instances, the configuration file is usually in `/etc/mysql/my.cnf`. Refer to your MySQL manual for more information
</div>

We must edit the MySQL configuration to use the newly generated certificates.

Edit your MySQL configuration file as follows. 


	[mysqld]
	...
	ssl-ca=/etc/mysql/ca-cert.pem
	ssl-cert=/etc/mysql/server-cert.pem
	ssl-key=/etc/mysql/server-key.pem

	# IMPORTANT! When enabling MySQL remote connections, make sure to take adequate steps to secure your machine from unathorized access!
	bind-address=0.0.0.0

<div class="warning" markdown='1'>
Enabling remote connections to your MySQL instance introduces various security risks. Make sure to take appropriate steps to secure your instance by using a strong password, disabling MySQL root access, and using a firewall to only accept qualified hosts, for example.
</div>

Make sure to restart your MySQL instance to reflect the changes.

	:::bash
	sudo service mysql restart


## Setting up Silverstripe to connect to MySQL

Now that we have successfully setup the SSL your MySQL host, we now need to configure Silverstripe to use the certificates.

### Copying SSL Certificates

First we need to copy the client certificate files to the Silverstripe instance. You will need to copy:

- `client-key.pem`
- `client-cert.pem`
- `ca-cert.pem`

<div class="warning" markdown='1'>
Make sure to only copy `client-key.pem`, `client-cert.pem`, and `ca-cert.pem` to avoid leaking your credentials!
</div>

On your Silverstripe instance:

	:::bash
	# Secure copy over SSH via rsync command. You may use an alternative method if desired. 
	rsync -avP user@db1.example.com:/path/to/client/certs /path/to/secure/folder

	#  Depending on your web server configuration, allow web server to read to SSL files
	sudo chown -R www-data:www-data /path/to/secure/folder
	sudo chmod 750 /path/to/secure/folder
	sudo chmod 400 /path/to/secure/folder/*

### Setting up _ss_environment.php to use SSL certificates

<div class="notice" markdown='1'>
`SS_DATABASE_SERVER does not accept IP-based hostnames. Also, if the domain name of the host does not match the common name you used to generate the server certificate, you will get an `SSL certificate mismatch error`.
</div>

Add or edit your `_ss_environment.php` configuration file. (See [Environment Management](/getting_started/environment_management) for more information.) 

	:::php
	<?php

	// These four define set the database connection details.
	define('SS_DATABASE_CLASS', 'MySQLPDODatabase');

	define('SS_DATABASE_SERVER', 'db1.example.com');
	define('SS_DATABASE_USERNAME', 'dbuser');
	define('SS_DATABASE_PASSWORD', '<password>');

	// These define the paths to the SSL key, certificate, and CA certificate bundle.
	define('SS_DATABASE_SSL_KEY', '/home/newdrafts/mysqlssltest/client-key.pem');
	define('SS_DATABASE_SSL_CERT', '/home/newdrafts/mysqlssltest/client-cert.pem');
	define('SS_DATABASE_SSL_CA', '/home/newdrafts/mysqlssltest/ca-	cert.pem');

	// When using SSL connections, you also need to supply a username and password to override the default settings
	define('SS_DEFAULT_ADMIN_USERNAME', 'username');
	define('SS_DEFAULT_ADMIN_PASSWORD', 'password');


When running the installer, make sure to check on the `Use _ss_environment file for configuration` option under the `Database Configuration` section to use the environment file.

## Conclusion

That's it! We hope that this article was able to help you configure your remote MySQL SSL secure database connection.
