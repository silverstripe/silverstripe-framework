# Nginx

These instructions are also covered on the
[Nginx Wiki](https://www.nginx.com/resources/wiki/start/topics/recipes/silverstripe/).

The prerequisite is that you have already installed Nginx and you are
able to run PHP files via the FastCGI-wrapper from Nginx.

Now you need to set up a virtual host in Nginx with configuration settings
that are similar to those shown below.

<div class="notice" markdown='1'>
If you don't fully understand the configuration presented here, consult the
[nginx documentation](http://nginx.org/en/docs/).

Especially be aware of [accidental php-execution](https://nealpoole.com/blog/2011/04/setting-up-php-fastcgi-and-nginx-dont-trust-the-tutorials-check-your-configuration/ "Don't trust the tutorials") when extending the configuration.
</div>

But enough of the disclaimer, on to the actual configuration — typically in `nginx.conf`. This assumes
you are running your site configuration with a separate `public/` webroot folder.

	server {
		listen 80;
		root /var/www/the-website/public;

		server_name site.com www.site.com;

		# Defend against SS-2015-013 -- http://www.silverstripe.org/software/download/security-releases/ss-2015-013
		if ($http_x_forwarded_host) {
			return 400;
		}

		location / {
			try_files $uri /index.php?$query_string;
		}

		error_page 404 /assets/error-404.html;
		error_page 500 /assets/error-500.html;

		location ^~ /assets/ {
			location ~ /\. {
				deny all;
			}
			sendfile on;
			try_files $uri /index.php?$query_string;
		}
		
		location ~ /\.. {
			deny all;
		}

		location ~ web\.config$ {
			deny all;
		}

		location ~ \.php$ {
			fastcgi_keep_conn on;
			fastcgi_pass   127.0.0.1:9000;
			fastcgi_index  index.php;
			fastcgi_param  SCRIPT_FILENAME $document_root$fastcgi_script_name;
			include        fastcgi_params;
			fastcgi_buffer_size 32k;
			fastcgi_busy_buffers_size 64k;
			fastcgi_buffers 4 32k;
		}
	}

The above configuration sets up a virtual host `site.com` with
rewrite rules suited for SilverStripe. The location block for php files
passes all php scripts to the FastCGI-wrapper via a TCP socket.

Now you can proceed with the SilverStripe installation normally.
