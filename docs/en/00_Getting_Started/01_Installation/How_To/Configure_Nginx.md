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

## Caveats about the sample configuration below

* It does not cover serving securely over HTTPS.
* It uses the new filesystem layout (with `public` directory) introduced in version 4.1.0. If your installation has been upgraded to 4.1+ from an older version and you have not [upgraded to the public folder](/changelogs/4.1.0.md), see the version of this documentation for version 4.0.
* The regular expression for allowed file types must be manually updated if the File.allowed_extensions list is updated.
* The error pages for 502 (Bad Gateway) and 503 (Service Unavailable) need to be manually created and published in the CMS (assuming use of the silverstripe/errorpage module).

```nginx
server {
  include mime.types;
  default_type  application/octet-stream;
  client_max_body_size 0; # Manage this in php.ini (upload_max_filesize & post_max_size)
  listen 80;
  root /path/to/ss/folder/public;
  server_name example.com www.example.com;

  # Defend against SS-2015-013 -- http://www.silverstripe.org/software/download/security-releases/ss-2015-013
  if ($http_x_forwarded_host) {
    return 400;
  }

  location / {
      try_files $uri /index.php?$query_string;
  }

  error_page 404 /assets/error-404.html;
  error_page 500 /assets/error-500.html;

  # See caveats
  error_page 502 /assets/error-500.html;
  error_page 503 /assets/error-500.html;

  # Support assets & resources #

  # Never serve .gitignore, .htaccess, or .method
  location ~ /\.(gitignore|htaccess|method)$ {
      return 403;
  }

  # Handle allowed file types (see caveats)
  # Pass unfound files to SilverStripe to check draft images
  location ~ ^/assets/.*\.(?i:css|js|ace|arc|arj|asf|au|avi|bmp|bz2|cab|cda|csv|dmg|doc|docx|dotx|flv|gif|gpx|gz|hqx|ico|jpeg|jpg|kml|m4a|m4v|mid|midi|mkv|mov|mp3|mp4|mpa|mpeg|mpg|ogg|ogv|pages|pcx|pdf|png|pps|ppt|pptx|potx|ra|ram|rm|rtf|sit|sitx|tar|tgz|tif|tiff|txt|wav|webm|wma|wmv|xls|xlsx|xltx|zip|zipx)$ {
      sendfile on;
      try_files $uri /index.php?$query_string;
  }

  # Allow the error pages. Fail with 404 Not found.
  location ~ ^/assets/error-\d\d\d\.html$ {
      try_files $uri =404;
  }

  # Fail all other assets requests as 404 Not found
  # Could also use 403 Forbidden or 444 (nginx drops the connection)
  location ~ ^/assets/ {
      return 404;
  }

  # End of assets & resources support #

  location /index.php {
    fastcgi_buffer_size 32k;
    fastcgi_busy_buffers_size 64k;
    fastcgi_buffers 4 32k;
    fastcgi_keep_conn on;
    fastcgi_pass   127.0.0.1:9000;
    fastcgi_index  index.php;
    fastcgi_param  SCRIPT_FILENAME $document_root$fastcgi_script_name;
    include        fastcgi_params;
  }
}
```

The above configuration sets up a virtual host `example.com` with
rewrite rules suited for SilverStripe. The location block for index.php
passes the php script to the FastCGI-wrapper via a TCP socket.

Now you can proceed with the SilverStripe installation normally.
