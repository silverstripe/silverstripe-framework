---
title: Configure Lighttpd
summary: Write a custom config for nginx
---

# Nginx

These instructions are also covered on the
[Nginx Wiki](https://www.nginx.com/resources/wiki/start/topics/recipes/silverstripe/).

The prerequisite is that you have already installed Nginx and you are
able to run PHP files via the FastCGI-wrapper from Nginx.

Now you need to set up a virtual host in Nginx with configuration settings
that are similar to those shown below.

[notice]
If you don't fully understand the configuration presented here, consult the
[nginx documentation](http://nginx.org/en/docs/).

Especially be aware of [accidental php-execution](https://nealpoole.com/blog/2011/04/setting-up-php-fastcgi-and-nginx-dont-trust-the-tutorials-check-your-configuration/ "Don't trust the tutorials") when extending the configuration.
[/notice]

But enough of the disclaimer, on to the actual configuration — typically in `nginx.conf`:

```nginx
server {
  include mime.types;
  default_type  application/octet-stream;
  client_max_body_size 0; # Manage this in php.ini
  listen 80;
  root /path/to/ss/folder;
  server_name example.com www.example.com;

  # Defend against SS-2015-013 -- http://www.silverstripe.org/software/download/security-releases/ss-2015-013
  if ($http_x_forwarded_host) {
    return 400;
  }

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
    fastcgi_buffer_size 32k;
    fastcgi_busy_buffers_size 64k;
    fastcgi_buffers 4 32k;
    fastcgi_keep_conn on;
    fastcgi_pass   127.0.0.1:9000;
    fastcgi_index  index.php;
    fastcgi_param  SCRIPT_FILENAME $document_root$fastcgi_script_name;
    include        fastcgi_params;
  }

  # Denials
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
  location ~* README.*$ {
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
}
```

The above configuration sets up a virtual host `example.com` with
rewrite rules suited for SilverStripe. The location block for framework
php files passes all the php scripts to the FastCGI-wrapper via a TCP
socket.

Now you can proceed with the SilverStripe installation normally.
