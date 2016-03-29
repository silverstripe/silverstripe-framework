# How to restrict access to admin by IP address.

If your organisation knows everyone who is accessing your cms has a fixed IP address you may want to restrict access to 
/admin and /admin/*anything* to a those IP addresses only.

## A single IP address

This example locks down access to only the local system. This would allow a developer with Silverstripe installed 
locally to access the cms of the installation but no one else.

````
<IfModule mod_rewrite.c>
  SetEnv HTTP_MOD_REWRITE On
  RewriteEngine On
  RewriteBase '/'

  RewriteCond %{REQUEST_URI} ^(.*)?(admin/(.*)|admin)$
  RewriteCond %{REMOTE_ADDR} !^127\.0\.0\.1$
  RewriteRule ^(.*)$ - [R=403,L]
  
</IfModule>
````

## Multiple IP addresses

This example locks down access to two IP addresses. In this case it's the loopback IP and google.

````
<IfModule mod_rewrite.c>
  SetEnv HTTP_MOD_REWRITE On
  RewriteEngine On
  RewriteBase '/'

  RewriteCond %{REQUEST_URI} ^(.*)?(admin/(.*)|admin)$
  RewriteCond %{REMOTE_ADDR} !^(127\.0\.0\.1|216\.58\.208\.36)$
  RewriteRule ^(.*)$ - [R=403,L]
  
</IfModule>
````
