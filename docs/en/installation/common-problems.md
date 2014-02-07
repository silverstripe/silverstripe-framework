# Common Problems

From time to time, things will go wrong.  Here's a few things to try when you're confused.

See ["8 Common SilverStripe Errors Explain (and solved!) (leftandmain.com)"](http://www.leftandmain.com/silverstripe-tips/2010/09/08/8-common-silverstripe-errors-explained-and-solved/)
for more common problems.

## The output shows only "Website Error"

This first and foremost means that your environment is set to "live mode" (see [environment-management]), which disallows
detailed error messages for security reasons. You'll typically need to get your environment into "dev mode" to see more
information.

If you can log-in to the CMS as an administrator, append `?isDev=1` to any URL to temporarily set your browsing session into
"dev mode". If you can't log-in in the first place because of the error, add this directive to your `mysite/_config/config.yml`
(don't forget to remove it afterwards!):

	:::php
	Director:
	  # temporary debugging statement
	  environment_type: 'dev'

<div class="warning" markdown='1'>
On "live" environments, the `?isDev=1` solution is preferred, as it means that your other visitors don't see ugly
(and potentially security sensitive) PHP errors as well.
</div>

## My templates don't update on page refresh

Putting ?flush=1 on the end of any SilverStripe URL will clear out all cached content; this is a pretty common solution
to a lot of development problems.  Here are some specifics situations:

*  You've created a new SS or PHP file
*  You've edited a nested template (one inserted with the `<% include %>` tag)
*  You've published a new copy of your site
*  You've upgraded your version of SilverStripe

## A SQL query fails with "Column not found" or "Table not found"

Whenever you change the model definitions in PHP (e.g. when adding a property to the [$db](api:DataObject::$db) array,
creating a new page type), SilverStripe will need to update the database. Visiting `http://localhost/dev/build` in
your browser runs a script that will check the database schema and update it as necessary.  Putting `?flush=1` on the
end makes sure that nothing that's linked to the old database structure will be carried over.  If things aren't saving,
pages aren't loading, or other random things aren't working it's possible that the database hasn't been updated to
handle the new code.  Here are some specifics situations:

*  You've created a new page type / other data object type
*  You've change the type of one of your database fields
*  You've published a new copy of your site
*  You've upgraded your version of SilverStripe

## My edited CMS content doesn't show on the website

If you've set up your site and it used to be working, but now it's suddenly totally broken, you may have forgotten to
publish your draft content.  Go to the CMS and use the "publish" button.  You can visit `admin/pages/publishall` to publish
every page on the site, if that's easier.

## I can see unparsed PHP output in my browser

Please make sure all code inside `*.php` files is wrapped in classes. Due to the way `[api:ManifestBuilder]`
includes all files with this extension, any **procedural code will be executed on every call**. The most common error here
is putting a test.php/phpinfo.php file in the document root. See [datamodel](/topics/datamodel) and [controllers](/topics/controller)
for ways how to structure your code.

Also, please check that you have PHP enabled on the webserver, and you're running PHP 5.1 or later.
The web-based [SilverStripe installer](/installation) can help you with this.

## I've got file permission problems during installation

The php installer needs to be able to write files during installation, which should be restricted again afterwards. It
needs to create/have write-access to:

 * The main installation directory (for creating .htaccess file and assets directory)
 * The mysite folder (to create _config.php)
 * After the install, the assets directory is the only directory that needs write access.
 * Image thumbnails will not show in the CMS if permission is not given 

## I have whitespace before my HTML output, triggering quirks mode or preventing cookies from being set

SilverStripe only uses class declarations in PHP files, and doesn't output any content
directly outside of these declarations. It's easy to accidentally add whitespace
or any other characters before the `<?php` opening bracket at the start of the document,
or after the `?>` closing braket at the end of the document.

Since we're dealing with hundreds of included files, identifying these mistakes manually can be tiresome.
The best way to detect whitespace is to look through your version control system for any uncommitted changes. 
If that doesn't work out, here's a little script to run checks in all relevant PHP files.
Save it as `check.php` into your webroot, and run it as `php check.php` (or open it in your browser).
After using the script (and fixing errors afterwards), please remember to remove it again.

```php
<?php
// Check for whitespace around PHP brackets which show in output,
// and hence can break HTML rendering and HTTP operations.
$path = dirname(__FILE__);
$files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path), RecursiveIteratorIterator::SELF_FIRST);
$matched = false;
foreach($files as $name => $file){
	if($file->getExtension() != 'php') continue;
	if(preg_match('/thirdparty|vendor/',$file->getPathname())) continue;
    $content = file_get_contents($file->getPathname());
    if(preg_match('/^[[:blank:]]+<\?' . 'php/', $content)) {
		echo sprintf("%s: Space before opening bracket\n", $file->getPathname());
		$matched = true;
	}
    if(preg_match('/^\?' . '>\n?[[:blank:]]+/m', $content)) {
    	echo sprintf("%s: Space after closing bracket\n", $file->getPathname());
    	$matched = true;
    }
}
```