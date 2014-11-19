# Nginx

These instructions are also covered on the
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
		root /path/to/ss/folder;
	
		server_name site.com www.site.com;
	
		location / {
	        try_files $uri @silverstripe;
	    }
	 
	    location ~ ^/(index|install).php {
	        fastcgi_split_path_info ^((?U).+\.php)(/?.+)$;
	        include fastcgi_params;
	        fastcgi_pass unix:/var/run/php5-fpm.sock;
	    }
	 
	    # whitelist php files that are called directly and need to be interpreted
	    location = /framework/thirdparty/tinymce/tiny_mce_gzip.php {
	        include fastcgi_params;
	        fastcgi_pass unix:/var/run/php5-fpm.sock;
	    }
	 
	    location = /framework/thirdparty/tinymce-spellchecker/rpc.php {
	        include fastcgi_params;
	        fastcgi_pass unix:/var/run/php5-fpm.sock;
	    }
	 
	    location @silverstripe {
	        expires off;
	        include fastcgi_params;
	        fastcgi_pass unix:/var/run/php5-fpm.sock;
	        fastcgi_param SCRIPT_FILENAME $document_root/framework/main.php;
	        fastcgi_param SCRIPT_NAME /framework/main.php;
	        fastcgi_param QUERY_STRING url=$uri&$args;
	        fastcgi_buffer_size 128k;
			fastcgi_buffers 4 256k;
			fastcgi_busy_buffers_size 256k;
			proxy_connect_timeout 90;
			proxy_send_timeout 180;
			proxy_read_timeout 180;
			proxy_buffer_size 128k;
			proxy_buffers 4 256k;
			proxy_busy_buffers_size 256k;
			proxy_intercept_errors on;
	    }
	 
	    #
	    # Error Pages
	    #
	    error_page 503 @maintenance;
	 
	    if (-f $document_root/maintenance.html ) {
	        return 503;
	    }
	 
	    location @maintenance {
	        try_files /maintenance.html =503;
	    }
	 
	    error_page 500 /assets/error-500.html;
	 
	    #
	    # Deny access to protected folder/files
	    #
	    location ^~ /assets/ {
	        try_files $uri =404;
	    }
	 
	    location ^~ /silverstripe-cache/ {
	        deny all;
	    }
	 
	    location ^~ /vendor/ {
	        deny all;
	    }
	 
	    location ~ /composer\.(json|lock) {
	        deny all;
	    }
	 
	    location ~ /(\.|web\.config) {
	        deny all;
	    }
	 
	    location ~ \.(yml|bak|swp)$ {
	        deny all;
	    }
	 
	    location ~ ~$ {
	        deny all;
	    }
	 
	    location ~ \.(php|php[345]|phtml|inc)$ {
	        deny all;
	    }
	 
	    location ~ ^/(cms|framework)/silverstripe_version$ {
	        deny all;
	    }

	}

The above configuration sets up a virtual host `site.com` with
rewrite rules suited for SilverStripe. The location block for php files
passes all php scripts to the FastCGI-wrapper via a TCP socket.

Now you can proceed with the SilverStripe installation normally.
