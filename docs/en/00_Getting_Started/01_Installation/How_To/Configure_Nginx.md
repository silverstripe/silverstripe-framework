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

```

The above configuration sets up a virtual host `example.com` with
rewrite rules suited for SilverStripe. The location block for framework
php files passes all the php scripts to the FastCGI-wrapper via a TCP
socket.

Now you can proceed with the SilverStripe installation normally.
