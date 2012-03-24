# Lightttpd

1. Lighttpd works fine so long as you provide a custom config. Add the following to lighttpd.conf **BEFORE** installing
Silverstripe. Replace "yoursite.com" and "/home/yoursite/public_html/" below.

	
	$HTTP["host"] == "yoursite.com" {
	    server.document-root = "/home/yoursite/public_html/"
	
	    # Disable directory listings
	    dir-listing.activate = "disable"
	
	    # Deny access to template files
	    url.access-deny += ( ".ss" )
	    static-file.exclude-extensions += ( ".ss" )
	
	    # Deny access to SilverStripe command-line interface
	    $HTTP["url"] =~ "^/framework/cli-script.php" {
	       url.access-deny = ( "" )
	    }
	
	    # Disable FastCGI in assets directory (so that PHP files are not executed)
	    $HTTP["url"] =~ "^/assets/" {
	       fastcgi.server = ()
	    }
	
	    # Rewrite URLs so they are nicer
	    url.rewrite-once = (
	       "^/.*\.[A-Za-z0-9]+.*?$" => "$0",
	       "^/(.*?)(\?|$)(.*)" => "/framework/main.php?url=$1&$3"
	    )
	
	    # Show SilverStripe error page
	    server.error-handler-404 = "/framework/main.php" 
	}


Rewrite rules do not check for file existence as they do on Apache. There is a ticket about it for Lighttpd:
[http://redmine.lighttpd.net/issues/985](http://redmine.lighttpd.net/issues/985).

2. Extract the SilverStripe software to your lighttpd installation, and run http://yoursite.com/install.php and the
installation should proceed normally.

## Multiple installations of SilverStripe on the same host (www.yourhost.com)

Running multiple installations of Silverstripe on the same host is a bit more tricky, but not impossible.  I would
recommend using subdomains instead if you can, for exampe: site1.yourdomain.com and site2.yourdomain.com, it makes
things a lot simpler, as you just use two of the above host example blocks. But if you really must run multiple copies
of Silverstripe on the same host, you can use something like this (be warned, it's quite nasty):

	
	$HTTP["host"] == "yoursite.com" {
	   url.rewrite-once = (
	      "(?i)(/copy1/.*\.([A-Za-z0-9]+))(.*?)$" => "$0",
	      "(?i)(/copy2/.*\.([A-Za-z0-9]+))(.*?)$" => "$0",
	      "^/copy1/(.*?)(\?|$)(.*)" => "/copy1/framework/main.php?url=$1&$3",
	      "^/copy2/(.*?)(\?|$)(.*)" => "/copy2/framework/main.php?url=$1&$3"
	   )
	   $HTTP["url"] =~ "^/copy1/" {
	      server.error-handler-404 = "/copy1/framework/main.php"
	   }
	   $HTTP["url"] =~ "^/copy2/" {
	      server.error-handler-404 = "/copy2/framework/main.php"
	   }
	}


Note: It doesn't work properly if the directory name copy1 or copy2 on your server has a dot in it, and you then open
the image editor inside admin, I found that out the hard way when using a directory name of silverstripe-v2.2.2 after
directly unzipping the Silverstripe tarball leaving the name as is. I haven't found a solution for that yet, but for now
this method still works properly if you just don't use dots in the directory names.

## Installing lighttpd on Debian

*  aptitude install lighttpd *(and php5-cgi, mysql-server, etc, as necessary.)*
    * if apache is already running, lighttpd can still be safely installed. It will complain it cannot start because
port 80 is in use. After installing lighttpd, edit /etc/lighttpd/lighttpd.conf  and set: "server.port = 81" for example,
and run /etc/init.d/lighttpd restart
    * edit /etc/lighttpd/conf-available/10-fastcgi.conf and set socket to: /var/run/lighttpd/php.socket
    * enable fastcgi module with /usr/sbin/lighty-enable-mod
*  /etc/init.d/lighttpd restart
*  You should now be able to view PHP files in your now-working lighttpd server.
*  Follow the top instructions on adding the rewrite rules, and then install SilverStripe.

## More about lighttpd

Learn more about the lighttpd webserver at http://www.lighttpd.net/
