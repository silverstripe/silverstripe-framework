# Nginx

These instructions are also covered in less detail on the
[Nginx Wiki](http://wiki.nginx.org/SilverStripe).

The prerequisite is that you have already installed Nginx and you are
able to run PHP files via the FastCGI-wrapper from Nginx.

Now you need to set up a virtual host in Nginx with configuration settings
that are similar to those shown below.
<div class="notice" markdown='1'>
If you don't fully understand the configuration presented here, consult the
[nginx documentation](http://nginx.org/en/docs/).

Especially be aware of [accidental php-execution](https://nealpoole.com/blog/2011/04/setting-up-php-fastcgi-and-nginx-dont-trust-the-tutorials-check-your-configuration/ "Don't trust the tutorials") when extending the configuration.
</div>
But enough of the disclaimer, on to the actual configuration — typically in `nginx.conf`:

	server {
		listen 80;
		server_name example.com;
		
		root /var/www/example.com;
		
		# SSL configuration (optional, but recommended for security)
		# (remember to actually force logins to use ssl)
		include ssl
		
		include silverstripe3.conf;
		include htaccess.conf;
		
		# rest of the server section is optional, but helpful
		# maintenance page if it exists
		error_page 503 @maintenance;
		if (-f $document_root/maintenance.html ) {
			return 503;
		}
		location @maintenance {
			try_files /maintenance.html =503;
		}
		
		# always show SilverStripe's version of 500 error page
		error_page 500 /assets/error-500.html;
		
		# let the user's browser cache static files (e.g. 2 weeks)
		expires 2w;
		
		# in case your machine is slow, increase the timeout
		# (also remembers php's own timeout settings)
		#fastcgi_read_timeout 300s;
	}

Here is the include file `silverstripe3.conf`:

	location / {
		try_files $uri @silverstripe;
	}
	
	# only needed for installation - disable this location (and remove the
	# index.php and install.php files) after you installed SilverStripe
	# (you did read the blogentry linked above, didn't you)
	location ~ ^/(index|install).php {
		fastcgi_split_path_info ^((?U).+\.php)(/?.+)$;
		include fastcgi.conf;
		fastcgi_pass unix:/run/php-fpm/php-fpm-silverstripe.sock;
	}
	
	# whitelist php files that are called directly and need to be interpreted
	location = /framework/thirdparty/tinymce/tiny_mce_gzip.php {
		include fastcgi.conf;
		fastcgi_pass unix:/run/php-fpm/php-fpm-silverstripe.sock;
	}
	location = /framework/thirdparty/tinymce-spellchecker/rpc.php {
		include fastcgi.conf;
		fastcgi_pass unix:/run/php-fpm/php-fpm-silverstripe.sock;
	}
	
	location @silverstripe {
		expires off;
		include fastcgi.conf;
		fastcgi_pass unix:/run/php-fpm/php-fpm-silverstripe.sock;
		# note that specifying a fixed script already protects against execution
		# of arbitrary files, but remember the advice above for any other rules
		# you add yourself (monitoring, etc,....)
		fastcgi_param SCRIPT_FILENAME $document_root/framework/main.php;
		fastcgi_param SCRIPT_NAME /framework/main.php;
		fastcgi_param QUERY_STRING url=$uri&$args;
		
		# tuning is up to your expertise, but buffer_size needs to be >= 8k,
		# otherwise you'll get  "upstream sent too big header while reading
		# response header from upstream" errors.
		fastcgi_buffer_size 8k;
		#fastcgi_buffers 4 32k;
		#fastcgi_busy_buffers_size 64k;
	}

<div class="warning" markdown='1'>
With only the above configuration, nginx would hand out any existing file
uninterpreted, so it would happily serve your precious configuration files,
including all your private api-keys and whatnot to any random visitor. So you
**must** restrict access further.
</div>
You don't need to use separate files, but it is easier to have the permissive
rules distinct from the restricting ones.

Here is the include file `htaccess.conf`:

	# Don't try to find nonexisting stuff in assets (esp. don't pass through php)
	location ^~ /assets/ {
		try_files $uri =404;
	}
	
	# Deny access to silverstripe-cache, vendor or composer.json/.lock
	location ^~ /silverstripe-cache/ {
		deny all;
	}
	location ^~ /vendor/ {
		deny all;
	}
	location ~ /composer\.(json|lock) {
		deny all;
	}
	
	# Don't serve up any "hidden" files or directories
	# (starting with dot, like .htaccess or .git)
	# also don't serve web.config files
	location ~ /(\.|web\.config) {
		deny all;
	}
	
	# Block access to yaml files (and don't forget about backup
	# files that editors tend to leave behind)
	location ~ \.(yml|bak|swp)$ {
		deny all;
	}
	location ~ ~$ {
		deny all;
	}
	
	# generally don't serve any php-like files
	# (as they exist, they would be served as regular files, and not interpreted.
	# But as those can contain configuration data, this is bad nevertheless)
	# If needed, you can always whitelist entries.
	location ~ \.(php|php[345]|phtml|inc)$ {
		deny all;
	}
	location ~ ^/(cms|framework)/silverstripe_version$ {
		deny all;
	}

Here is the optional include file `ssl`:

	listen 443 ssl;
	ssl_certificate server.crt;
	ssl_certificate_key server.key;
	ssl_session_timeout 5m;
	ssl_protocols SSLv3 TLSv1;
	ssl_ciphers ALL:!ADH:!EXPORT56:RC4+RSA:+HIGH:+MEDIUM:+LOW:+SSLv3:+EXP;

The above configuration sets up a virtual host `example.com` with
rewrite rules suited for SilverStripe. The location block named
`@silverstripe` passes all requests that aren't matched by one of the other
location rules (and cannot be satisfied by serving an existing file) to
SilverStripe framework's main.php script, that is run by the FastCGI-wrapper,
that in turn is accessed via a Unix socket.

Now you can proceed with the SilverStripe installation normally.
