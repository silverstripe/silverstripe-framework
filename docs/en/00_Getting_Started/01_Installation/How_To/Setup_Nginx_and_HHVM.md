title: Nginx and HHVM
summary: Setting up Nginx and HHVM on Debian/Ubuntu using packages.

# Nginx and HHVM

[HHVM](http://hhvm.com/) is a faster alternative to PHP, in that it runs in a virtual machine
and uses just-in-time (JIT) compilation to achieve better performance over standard PHP.

Installation on Debian or Ubuntu is relatively straightforward, in that HHVM already provide
packages available to use.

Install apt sources on Debian 7 (wheezy):

	wget -O - http://dl.hhvm.com/conf/hhvm.gpg.key | sudo apt-key add -
	echo deb http://dl.hhvm.com/debian wheezy main | sudo tee /etc/apt/sources.list.d/hhvm.list

Install apt sources on Ubuntu 14.04 (trusty):

	wget -O - http://dl.hhvm.com/conf/hhvm.gpg.key | sudo apt-key add -
	echo deb http://dl.hhvm.com/ubuntu trusty main | sudo tee /etc/apt/sources.list.d/hhvm.list

Now install the required packages:

	sudo apt-get update
	sudo apt-get install hhvm libgmp-dev libmemcached-dev

Start HHVM automatically on boot:

	sudo update-rc.d hhvm defaults

Please see [Prebuilt Packages for HHVM on HHVM wiki](https://github.com/facebook/hhvm/wiki/Prebuilt%20Packages%20for%20HHVM) for more
installation options.

Assuming you already have nginx installed, you can then run a script to enable support for
nginx and/or apache2 depending on whether they are installed or not:

	sudo /usr/share/hhvm/install_fastcgi.sh

For nginx, this will place a file at `/etc/nginx/hhvm.conf` which you can use to include in
your nginx server definitions to provide support for PHP requests.

In order to get SilverStripe working, you need to add some custom nginx configuration.

Create `/etc/nginx/silverstripe.conf` and add this configuration:

	fastcgi_buffer_size 32k;
	fastcgi_busy_buffers_size 64k;
	fastcgi_buffers 4 32k;
	
	location / {
		try_files $uri /framework/main.php?url=$uri&$query_string;
	}
	
	error_page 404 /assets/error-404.html;
	error_page 500 /assets/error-500.html;
	
	location ^~ /assets/ {
		try_files $uri =404;
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

The above script passes all non-static file requests to `/framework/main.php` in the webroot which relies on
`hhvm.conf` being included prior so that php requests are handled.

Now in your nginx `server` configuration you can then include the `hhvm.conf` and `silverstripe.conf` files
to complete the configuration required for PHP/HHVM and SilverStripe.

e.g. `/etc/nginx/sites-enabled/mysite`:

	server {
		listen 80;
		root /var/www/mysite;
		server_name www.mysite.com;
	
		error_log /var/log/nginx/mysite.error.log;
		access_log /var/log/nginx/mysite.access.log;
	
		include /etc/nginx/hhvm.conf;
		include /etc/nginx/silverstripe.conf;
	}

For more information on nginx configuration, please see the [nginx installation](nginx) page.
