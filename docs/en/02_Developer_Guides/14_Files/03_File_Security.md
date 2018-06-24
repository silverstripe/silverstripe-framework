summary: Manage access permission to assets

# File Security

## Security overview

File security is an important concept, and is as essential as managing any other piece of data that exists 
in your system. As pages and dataobjects can be either versioned, or restricted to view by authenticated
members, it is necessary at times to apply similar logic to any files which are attached to these objects
in the same way.

Out of the box SilverStripe Framework comes with an asset storage mechanism with two stores, a public
store and a protected one. Most operations which act on assets work independently of this mechanism,
without having to consider whether any specific file is protected or public, but can normally be
instructed to favour private or protected stores in some cases.

For instance, in order to write an asset to a protected location you can use the following additional
config option:


```php
$store = singleton(AssetStore::class);
$store->setFromString('My protected content', 'Documents/Mydocument.txt', null, null, [
    'visibility' => AssetStore::VISIBILITY_PROTECTED
]);
```

## User access control

Access for files is granted on a per-session basis, rather than on a per-member basis, via
whitelisting accessed assets. This means that access to any protected asset must be made prior to the user
actually attempting to download that asset. This is normally done in the PHP request that generated
the response containing the link to that file.

An automated system will, in most cases, handle this whitelisting for you. Calls to getURL()
will automatically whitelist access to that file for the current user. Using this as a guide, you can easily
control access to embedded assets at a template level.


```ss
<ul class="files">
    <% loop $File %>
        <% if $canView %>
            <li><a href="$URL">Download $Title</a></li>
        <% else %>
            <li>Permission denied for $Title</li>
        <% end_if %>
    <% end_loop >
</ul>
```

Users who are able to guess the value of $URL will not be able to access those urls without being
authorised by this code.

In order to ensure protected assets are not leaked publicly, but are properly whitelisted for 
authorised users, the following should be considered:

* Caching mechanisms which prevent `$URL` being invoked for the user's request (such as `$URL` within a
  partial cache block) will not whitelist those files automatically. You can manually whitelist a
  file via PHP for the current user instead, by using the following code to grant access.



```php
use SilverStripe\CMS\Controllers\ContentController;

class PageController extends ContentController 
{
    public function init() 
    {
        parent::init();
        
        // Whitelist the protected files on this page for the current user
        $file = $this->File();
        if($file->canView()) {
            $file->grantFile();
        }
    }
}
```

* If a user does not have access to a file, you can still generate the URL but suppress the default
  permission whitelist by invoking the getter as a method, but pass in a falsey value as a parameter.
  (or '0' in template as a workaround for all parameters being cast as string)


```php
<% if not $canView %>
    <!-- The user will be denied if they follow this url -->
    <li><a href="$getURL(0)">Access to $Title is denied</a></li>
<% else %>
```

* Alternatively, if a user has already been granted access, you can explicitly revoke their access using
  the `revokeFile` method.

```php
use SilverStripe\CMS\Controllers\ContentController;

class PageController extends ContentController 
{
    public function init() 
    {
        parent::init();
        
        // Whitelist the protected files on this page for the current user
        $file = $this->File();
        if($file->canView()) {
            $file->grantFile();
        } else {
            // Will revoke any historical grants
            $file->revokeFile();
        }
    }
}
```

## Controlling asset visibility

The asset API provides three main mechanisms for setting the visibility of an asset. Note that
these operations are applied on a per file basis, and unlike `revoke` or `grant` methods
these do not affect visibility for specific users.

Visibility can be specified when files are created via one of the `AssetStore::VISIBILITY_PROTECTED`
or `AssetStore::VISIBILITY_PUBLIC` constants. It's advisable to ensure the visibility of any file
is declared as early as possible, so that potentially sensitive content never touches any
public facing area.

E.g.

```php
$object->MyFile->setFromLocalFile($tmpFile['Path'], $filename, null, null, [
    'visibility' => AssetStore::VISIBILITY_PROTECTED
]);
```

You can also adjust the visibility of any existing file to either public or protected.

```php
// This will make the file available only when a user calls `->grant()`
$object->SecretFile->protectFile();

// This file will be available to everyone with the URL
$object->PublicFile->publishFile();
```

<div class="notice" markdown="1">
One thing to note is that all variants of a single file will be treated as
a single entity for access control, so specific variants cannot be individually controlled.
</div>

## How file access is protected

Public urls to files do not change, regardless of whether the file is protected or public. Similarly,
operations which modify files do not normally need to be told whether the file is protected or public
either. This provides a consistent method for interacting with files.

In day to day operation, moving assets to or between either of these stores does not normally
alter these asset urls, as the routing mechanism will infer file access requirements dynamically.
This allows you to prepare predictable file urls on a draft site, which will not change once 
the page is published, but will be protected in the mean time.

For instance, consider two files `OldCompanyLogo.gif` in the public store, and `NewCompanyLogo.gif`
in the protected store, awaiting publishing.

Internally your folder structure would look something like:


```
assets/
    .htaccess
    .protected/
        .htaccess
        a870de278b/
            NewCompanyLogo.gif
    33be1b95cb/
        OldCompanyLogo.gif
```

The urls for these two files, however, do not reflect the physical structure directly.

* `http://www.myapp.com/assets/33be1b95cb/OldCompanyLogo.gif` will be served directly from the web server,
  and will not invoke a php request.
* `http://www.myapp.com/assets/a870de278b/NewCompanyLogo.gif` will be routed via a 404 handler to PHP,
  which will be passed to the `[ProtectedFileController](api:SilverStripe\Assets\Storage\ProtectedFileController)` controller, which will serve
  up the content of the hidden file, conditional on a permission check.

When the file `NewCompanyLogo.gif` is made public, the url will not change, but the file location
will be moved to `assets/a870de278b/NewCompanyLogo.gif`, and will be served directly via
the web server, bypassing the need for additional PHP requests.

```php
use SilverStripe\Assets\Storage\AssetStore;

$store = singleton(AssetStore::class);
$store->publish('NewCompanyLogo.gif', 'a870de278b475cb75f5d9f451439b2d378e13af1');
```

After this the filesystem will now look like below:

```
assets/
    .htaccess
    .protected/
        .htaccess
    33be1b95cb/
        OldCompanyLogo.gif
    a870de278b/
        NewCompanyLogo.gif
```

## Performance considerations

In order to ensure that a site does not invoke any unnecessary PHP processes when serving up files,
it's important to ensure that your server is configured correctly. Serving public files via PHP
will add unnecessary load to your server, but conversely, attempting to serve protected files
directly may lead to necessary security checks being omitted.

See the web server setting section below for more information on configuring your server properly

### Performance: Static caching

If you are deploying your site to a server configuration that makes use of static caching, it's essential
that you ensure any page or dataobject cached adequately publishes any linked assets. This is due to the
fact that static caching will bypass any PHP request, which would otherwise be necessary to whitelist
protected files for these users.

This is especially important when dealing with draft content, as frontend caches should not attempt to
cache protected content being served to authenticated users. This can be achieved by configuring your cache
correctly to skip `Pragma: no-cache` headers and the `bypassStaticCache` cookie.

## Configuring protected assets

### Configuring: Protected folder location

In the default SilverStripe configuration, protected assets are placed within the web root into the
`assets/.protected` folder, into which is also generated a `.htaccess` or `web.config` configured
to deny any and all direct web requests.

In order to better ensure these files are protected, it's recommended to move this outside of the web
root altogether.

For instance, given your web root is in the folder `/sites/myapp/www`, you can tell the asset store
to put protected files into `/sites/myapp/protected` with the below `.env` setting:

```
SS_PROTECTED_ASSETS_PATH="/sites/myapp/protected"
```

### Configuring: File types

In addition to configuring file locations, it's also important to ensure that you have allowed the
appropriate file extensions for your instance. This can be done by setting the `File.allowed_extensions`
config.

```yaml
SilverStripe\Assets\File: 
  allowed_extensions: 
    - 7zip 
    - xzip
```

<div class="warning" markdown="1">
Any file not included in this config, or in the default list of extensions, will be blocked from
any requests to the assets directory. Invalid files will be blocked regardless of whether they
exist or not, and will not invoke any PHP processes.
</div>

### Configuring: Protected file headers

In certain situations, it's necessary to customise HTTP headers required either by
intermediary caching services, or by the client, or upstream caches.

When a protected file is served it will also be transmitted with all headers defined by the
`SilverStripe\Filesystem\Flysystem\FlysystemAssetStore.file_response_headers` config.
You can customise this with the below config:

```yaml
SilverStripe\Filesystem\Flysystem\FlysystemAssetStore:
  file_response_headers:
    Pragma: 'no-cache'
```

### Configuring: Archive behaviour

By default, the default extension `AssetControlExtension` will control the disposal of assets
attached to objects when those objects are archived. For example, unpublished versioned objects
will automatically have their attached assets moved to the protected store. The archive of 
draft or (or deletion of unversioned objects) will have those assets permanantly deleted
(along with all variants).

Note that regardless of this setting, the database record will still be archived in the
version history for all Versioned DataObjects.

In some cases, it may be preferable to have any assets retained for archived versioned dataobjects,
instead of deleting them. This uses more disk storage, but will allow the full recovery of archived
records and files.

This can be applied to DataObjects on a case by case basis by setting the `keep_archived_assets`
config to true on that class. Note that this feature only works with dataobjects with
the `Versioned` extension.

```php
use SilverStripe\ORM\DataObject;

class MyVersiondObject extends DataObject 
{
    /** Ensure assets are archived along with the DataObject */
    private static $keep_archived_assets = true;
    /** Versioned */
    private static $extensions = ['Versioned'];
}
```

The extension can also be globally disabled by removing it at the root level:

```yaml
SilverStripe\ORM\DataObject:
  AssetControl: null
```

### Configuring: Web server settings

If the default server configuration is not appropriate for your specific environment, then you can
further customise the .htaccess or web.config by editing one or more of the below:

* `PublicAssetAdapter_HTAccess.ss`: Template for public permissions on the Apache server.
* `PublicAssetAdapter_WebConfig.ss`: Template for public permissions on the IIS server.
* `ProtectedAssetAdapter_HTAccess.ss`: Template for the protected store on the Apache server (should deny all requests).
* `ProtectedAssetAdapter_WebConfig.ss`: Template for the protected store on the IIS server (should deny all requests).

Each of these files will be regenerated on ?flush, so it is important to ensure that these files are
overridden at the template level, not via manually generated configuration files.

#### Configuring Web Server: Apache server

In order to ensure that public files are served correctly, you should check that your `assets/.htaccess`
bypasses PHP requests for files that do exist. The default template
(declared by `PublicAssetAdapter_HTAccess.ss`) has the following section, which may be customised in your project:

```
# Non existant files passed to requesthandler
RewriteCond %{REQUEST_URI} ^(.*)$
RewriteCond %{REQUEST_FILENAME} !-f
RewriteRule .* ../index.php [QSA]
```

You will need to ensure that your core apache configuration has the necessary `AllowOverride`
settings to support the local .htaccess file.

Although assets have a 404 handler which routes to a PHP handler, .php files within assets itself
should not be allowed to be marked as executable.

When securing your server you should ensure that you protect against both files that can be uploaded as
executable on the server, as well as protect against accidental upload of `.htaccess` which bypasses
this file security.

For instance your server configuration should look similar to the below:

```
<Directory "/var/www/superarcade/public/assets">
  php_admin_flag engine off
</Directory>
```

The `php_admin_flag` will protect against uploaded `.htaccess` files accidentally re-enabling script
execution within the assets directory.

#### Configuring Web Server: Windows IIS 7.5+

Configuring via IIS requires the Rewrite extension to be installed and configured properly.
Any rules declared for the assets folder should be able to dynamically serve up existing files,
while ensuring non-existent files are processed via the Framework.

The default rule for IIS is as below (only partial configuration displayed):

```
<rule name="Secure and 404 File rewrite" stopProcessing="true">
    <match url="^(.*)$" />
    <conditions>
        <add input="{REQUEST_FILENAME}" matchType="IsFile" negate="true" />
        <add input="../index.php" matchType="IsFile" />
    </conditions>
    <action type="Rewrite" url="../index.php" appendQueryString="true" />
</rule>
```

You will need to make sure that the `allowOverride` property of your root web.config is not set
to false, to allow these to take effect.

#### Configuring Web Server: Other server types

If using a server configuration which must be configured outside of the web or asset root, you
will need to make sure you manually configure these rules.

For instance, this will allow your nginx site to serve files directly, while ensuring
dynamic requests are processed via the Framework:

```
location ^~ /assets/ {
    sendfile on;
    try_files $uri index.php?$query_string;
}
```
