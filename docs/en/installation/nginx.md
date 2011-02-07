# Nginx

These instructions are also covered on the [Nginx Wiki](http://wiki.nginx.org/SilverStripe)

The prerequisite is that you have already installed Nginx and you are able to run PHP files via the FastCGI-wrapper from
Nginx.

Now you need to setup a virtual host in Nginx with the following configuration settings:

	server {
	        listen   80;
	        server_name  yoursite.com;
	
	        root   /home/yoursite.com/httpdocs;
	        index  index.html index.php;
	
	        if (!-f $request_filename) {
	                rewrite ^/(.*?)(\?|$)(.*)$ /sapphire/main.php?url=$1&$3 last;
	        }
	
	        error_page  404  /sapphire/main.php;
	
	        location ~ \.php$ {
	                fastcgi_pass   127.0.0.1:9000;
	                fastcgi_index  index.php;
	                fastcgi_param  SCRIPT_FILENAME  /home/yoursite.com/httpdocs$fastcgi_script_name;
	                include fastcgi_params;
	        }
	}


The above configuration will setup a new virtual host `yoursite.com` with rewrite rules suited for SilverStripe. The
location block at the bottom will pass all php scripts to the FastCGI-wrapper.

Now you can proceed with the SilverStripe installation normally.
