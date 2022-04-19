---
title: Allowed file types
summary: Control the type of files that can be stored in your Silverstripe CMS project
icon: lock
---

# Allowed file types

Not every kind of file should be stored in a CMS's asset management system. For example, allowing users to upload JavaScript files could lead to a risk of Cross-Site Scripting (XSS) attacks.

Out of the box, your Silverstripe CMS project will limit what type of files can be uploaded into the assets management section. There's two type of restriction in place based on:
* the extensions of the files
* the MIME type of the files.

## File extensions validation

The `silverstripe/assets` module ships with a whitelist of allowed file extensions. Any file with an extensions not in this whitelist will not be allowed to be stored in Silverstripe's assets management system.

The whitelist is controlled by the `SilverStripe\Assets\File::$allowed_extensions` variable.

You can whitelist additional file extensions by adding them in your YML configuration.
```yml
SilverStripe\Assets\File:
  allowed_extensions:
    - 7zip
    - xzip
```

Any file not included in this config, or in the default list of extensions, will be blocked from
any requests to the assets directory. Invalid files will be blocked regardless of whether they
exist or not, and will not invoke any PHP processes.

[warning]
While SVG images are a popular format to display images on the web, they are not included in the file extension whitelist because they can contain arbitrary scripts that will be executed when the image is rendered in a browser. Allowing CMS users to upload SVG images would be a significant XSS risk. We strongly advise developers against whitelisting the `svg` file extension.
[/warning]

You can also remove pre-existing entries from the whitelist by setting them to `false`.

```yml
SilverStripe\Assets\File:
  allowed_extensions:
    zip: false
```

## MIME type validation

Another type of validation that can be applied to files uploaded in Silverstripe CMS is MIME type validation. When MIME type validation is enabled, Silverstripe will analyse the content of files at upload time to determine their MIME type and will reject files with invalid type.

MIME type validation also uses a whitelist of allowed MIME types.

### Enabling MIME type validation

You need to install the `silverstripe/mimevalidator` module in your project to enable MIME type validation. If your project uses `silverstripe/recipe-core` 4.6.0 or greater, or any version of the Common Web Platform, the `silverstripe/mimevalidator` module will already be installed and enabled.

Look at the `app/_config/mimevalidator.yml` to view the default configuration.

You can explicitly require the module by running this command

```sh
composer require silverstripe/mimevalidator
```

#### Enable globally

In your `app/_config/config.yml` file:

```yml
SilverStripe\Core\Injector\Injector:
  SilverStripe\Assets\Upload_Validator:
    class: SilverStripe\MimeValidator\MimeUploadValidator
```

#### Enable on an individual upload field

```php
$field = UploadField::create();
$field->setValidator(MimeUploadValidator::create());
```

#### Adding MIME types

By default MIME types are checked against `HTTP.MimeTypes` config set in framework. This can be limiting as this only
allows for one MIME type per extension. To allow for multiple MIME types per extension, you can add these in your YAML
config as below:

```yml
SilverStripe\MimeValidator\MimeUploadValidator:
  MimeTypes:
    ics:
      - 'text/plain'
      - 'text/calendar'
```

## Adding new image types {#add-image-format}

Silverstripe CMS support JPEG, GIF, PNG and WebP image formats out of the box. Silverstripe CMS can be configured to support other less common image formats (e.g.: AVIF). For this to work, your version of PHP and of the [`intervention/image` library](https://intervention.io/) must support these alternative image formats.

For example, this snippet can be added to the configuration of older Silverstripe CMS projects to allow them to work with WebP images. 

```yml
---
Name: myproject-assetsfiletypes
After: '#assetsfiletypes'
---
SilverStripe\Assets\File:
  file_types:
    webp: 'WebP Image'
  allowed_extensions:
    - webp
  app_categories:
    image:
      - webp
    image/supported:
      - webp
  class_for_file_extension:
    webp: SilverStripe\Assets\Image

SilverStripe\Assets\Storage\DBFile:
  supported_images:
    - image/webp

---
Name: myproject-mimevalidator
After: '#mimevalidator'
Only:
  moduleexists: silverstripe/mimevalidator
---
SilverStripe\MimeValidator\MimeUploadValidator:
  MimeTypes:
    webp:
      - image/webp
```
