# Nginx

These instructions are also covered on the
[Nginx Wiki](http://wiki.nginx.org/SilverStripe).

The prerequisite is that you have already installed Nginx and you are
able to run PHP files via the FastCGI-wrapper from Nginx.

Now you need to set up a virtual host in Nginx with the following
configuration settings:

	server {
		listen 80;
		root /path/to/ss/folder;
	
		server_name site.com www.site.com;
	
		location / {
			try_files $uri /framework/main.php?url=$uri&$query_string;
		}
	
		error_page 404 /assets/error-404.html;
		error_page 500 /assets/error-500.html;
	
		location ^~ /assets/ {
			sendfile on;
			try_files $uri =404;
		}
	
		location ~ /framework/.*(main|rpc|tiny_mce_gzip)\.php$ {
			fastcgi_keep_conn on;
			fastcgi_pass   127.0.0.1:9000;
			fastcgi_index  index.php;
			fastcgi_param  SCRIPT_FILENAME $document_root$fastcgi_script_name;
			include        fastcgi_params;
		}
	
		location ~ /(mysite|framework|cms)/.*\.(php|php3|php4|php5|phtml|inc)$ {
			deny all;
		}
	
		location ~ /\.. {
			deny all;
		}
	
		location ~ \.ss$ {
			satisfy any;
			allow 127.0.0.1;
			deny all;
		}
	
		location ~ web\.config$ {
			deny all;
		}
	
		location ~ \.ya?ml$ {
			deny all;
		}
	
		location ^~ /vendor/ {
			deny all;
		}
	
		location ~* /silverstripe-cache/ {
			deny all;
		}
	
		location ~* composer\.(json|lock)$ {
			deny all;
		}
	
		location ~* /(cms|framework)/silverstripe_version$ {
			deny all;
		}
	
		location ~ \.php$ {
	                fastcgi_keep_conn on;
	                fastcgi_pass   127.0.0.1:9000;
	                fastcgi_index  index.php;
	                fastcgi_param  SCRIPT_FILENAME $document_root$fastcgi_script_name;
	                include        fastcgi_params;
		}
	}

The above configuration sets up a virtual host `site.com` with
rewrite rules suited for SilverStripe. The location block for php files
passes all php scripts to the FastCGI-wrapper via a TCP socket.

Now you can proceed with the SilverStripe installation normally.
