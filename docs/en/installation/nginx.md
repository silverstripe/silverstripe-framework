# Nginx

These instructions are also covered in less detail on the
[Nginx Wiki](http://wiki.nginx.org/SilverStripe).

The prerequisite is that you have already installed Nginx and you are
able to run PHP files via the FastCGI-wrapper from Nginx.

Now you need to set up a virtual host in Nginx with the following
configuration settings:

	server {
		listen 80;
		
		# SSL configuration (optional, but recommended for security)
		include ssl
		
		root /var/www/example.com;
		index index.php index.html index.htm;
		
		server_name example.com;

		include silverstripe3;
		include htaccess;
	}

Here is the include file `silverstripe3`:

	location / {
		try_files $uri @silverstripe;
	}
 
	location @silverstripe {
		include fastcgi_params;
		
		# Defend against arbitrary PHP code execution
		# NOTE: You should have "cgi.fix_pathinfo = 0;" in php.ini
		# More info:
		# https://nealpoole.com/blog/2011/04/setting-up-php-fastcgi-and-nginx-dont-trust-the-tutorials-check-your-configuration/
		fastcgi_split_path_info ^(.+\.php)(/.+)$;
		
		fastcgi_param SCRIPT_FILENAME $document_root/sapphire/main.php;
		fastcgi_param SCRIPT_NAME /sapphire/main.php;
		fastcgi_param QUERY_STRING url=$uri&$args;
		
		fastcgi_pass unix:/var/run/php5-fpm.sock;
		fastcgi_index index.php;
		fastcgi_buffer_size 32k;
		fastcgi_buffers 4 32k;
		fastcgi_busy_buffers_size 64k;
	}


Here is the include file `htaccess`:

	# Don't serve up any .htaccess files
	location ~ /\.ht {
		deny all;
	}
	
	# Deny access to silverstripe-cache
	location ~ ^/silverstripe-cache {
		deny all;
	}

	# Deny access to composer
	location ~ ^/(vendor|composer.json|composer.lock) {
		deny all;
	}
	
	# Don't execute scripts in the assets
	location ^~ /assets/ {
		try_files $uri $uri/ =404;
	}
	
	# cms & sapphire .htaccess rules
	location ~ ^/(cms|sapphire|mysite)/.*\.(php|php[345]|phtml|inc)$ {
		deny all;
	}
	location ~ ^/(cms|sapphire)/silverstripe_version$ {
		deny all;
	}
	location ~ ^/sapphire/.*(main|static-main|rpc|tiny_mce_gzip)\.php$ {
		allow all;
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
`@silverstripe` passes all php scripts to the FastCGI-wrapper via a Unix
socket. This example is from a site running Ubuntu with the php5-fpm
package.

Now you can proceed with the SilverStripe installation normally.
