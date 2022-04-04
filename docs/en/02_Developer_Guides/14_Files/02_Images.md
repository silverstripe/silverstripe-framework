---
summary: Learn how to crop and resize images in templates and PHP code
---

# Image

Image files can be stored either through the [`Image`](api:SilverStripe\Assets\Image) dataobject, or though [`DBFile`](api:SilverStripe\Assets\Storage\DBFile) fields.
In either case, the same image resizing and manipulation functionality is available though the common
[`ImageManipulation`](api:SilverStripe\Assets\ImageManipulation) trait.

## Usage

### Managing images through form fields

Images can be uploaded like any other file, through [`FileField`](api:SilverStripe\Forms\FileField).
Allows upload of images through limiting file extensions with `setAllowedExtensions()`.

### Inserting images into the WYSIWYG editor

Images can be inserted into [`HTMLValue`](api:SilverStripe\View\Parsers\HTMLValue) database fields
through the built-in WYSIWYG editor. In order to retain a relationship
to the underlying [`Image`](api:SilverStripe\Assets\Image) records, images are saved as [shortcodes](/developer-guides/extending/shortcodes).
The shortcode (`[image id="<id>" alt="My text" ...]`) will be converted
into an `<img>` tag on your website automatically.

See [HTMLEditorField](/forms/field-types/htmleditorfield).

### Manipulating images in Templates

You can manipulate images directly from templates to create images that are
resized and cropped to suit your needs.  This doesn't affect the original
image or clutter the CMS with any additional files, and any images you create
in this way are cached for later use. In most cases the pixel aspect ratios of
images are preserved (meaning images are not stretched).

![](../../_images/image-methods.jpg)

Here are some examples, assuming the `$Image` object has dimensions of 200x100px:


```ss
// Scaling functions
$Image.ScaleWidth(150) // Returns a 150x75px image
$Image.ScaleMaxWidth(100) // Returns a 100x50px image (like ScaleWidth but prevents up-sampling)
$Image.ScaleHeight(150) // Returns a 300x150px image (up-sampled. Try to avoid doing this)
$Image.ScaleMaxHeight(150) // Returns a 200x100px image (like ScaleHeight but prevents up-sampling)
$Image.Fit(300,300) // Returns an image that fits within a 300x300px boundary, resulting in a 300x150px image (up-sampled)
$Image.FitMax(300,300) // Returns a 200x100px image (like Fit but prevents up-sampling)

// Warning: This method can distort images that are not the correct aspect ratio
$Image.ResizedImage(200, 300) // Forces dimensions of this image to the given values.

// Cropping functions
$Image.Fill(150,150) // Returns a 150x150px image resized and cropped to fill specified dimensions (up-sampled)
$Image.FillMax(150,150) // Returns a 100x100px image (like Fill but prevents up-sampling)
$Image.CropWidth(150) // Returns a 150x100px image (trims excess pixels off the x axis from the center)
$Image.CropHeight(50) // Returns a 200x50px image (trims excess pixels off the y axis from the center)

// Padding functions (add space around an image)
$Image.Pad(100,100) // Returns a 100x100px padded image, with white bars added at the top and bottom
$Image.Pad(100, 100, CCCCCC) // Same as above but with a grey background

// Metadata
$Image.Width // Returns width of image
$Image.Height // Returns height of image
$Image.Orientation // Returns Orientation
$Image.Title // Returns the friendly file name
$Image.Name // Returns the actual file name
$Image.FileName // Returns the actual file name including directory path from web root
$Image.Link // Returns relative URL path to image
$Image.AbsoluteLink // Returns absolute URL path to image
```

Image methods are chainable. Example:

```ss
<body style="background-image:url($Image.ScaleWidth(800).CropHeight(800).Link)">
```

### Padded Image Resize

The Pad method allows you to resize an image with existing ratio and will
pad any surplus space. You can specify the color of the padding using a hex code such as FFFFFF or 000000.

You can also specify a level of transparency to apply to the padding color in a fourth param. This will only effect
png images.


```php
$Image.Pad(80, 80, FFFFFF, 50) // white padding with 50% transparency
$Image.Pad(80, 80, FFFFFF, 100) // white padding with 100% transparency
$Image.Pad(80, 80, FFFFFF) // white padding with no transparency
```

### Manipulating images in PHP

The image manipulation functions can be used in your code with the same names, example: `$image->Fill(150,150)`.

Some of the MetaData functions need to be prefixed with 'get', example `getHeight()`, `getOrientation()` etc.

Please refer to the [`ImageManipulation`](api:SilverStripe\Assets\ImageManipulation) API documentation for specific functions.

### Creating custom image functions

You can also create your own functions by decorating the `Image` class.


```php
use SilverStripe\Core\Extension;
class ImageExtension extends Extension
{
    public function Square($width)
    {
        $variant = $this->owner->variantName(__FUNCTION__, $width);
        return $this->owner->manipulateImage($variant, function (\SilverStripe\Assets\Image_Backend $backend) use($width) {
            $clone = clone $backend;
            $resource = clone $backend->getImageResource();
            $resource->fit($width);
            $clone->setImageResource($resource);
            return $clone;
        });
    }

    public function Blur($amount = null)
    {
        $variant = $this->owner->variantName(__FUNCTION__, $amount);
        return $this->owner->manipulateImage($variant, function (\SilverStripe\Assets\Image_Backend $backend) use ($amount) {
            $clone = clone $backend;
            $resource = clone $backend->getImageResource();
            $resource->blur($amount);
            $clone->setImageResource($resource);
            return $clone;
        });
    }

}
```

```yml
SilverStripe\Assets\Image:
  extensions:
    - ImageExtension
SilverStripe\Assets\Storage\DBFile:
  extensions:
    - ImageExtension
```

### Form Upload

For usage on a website form, see [`FileField`](api:SilverStripe\Assets\FileField).

### Image Quality

#### Source images

Whenever Silverstripe CMS performs a manipulation on an image, it saves the output
as a new image file, and applies compression during the process. If the source
image already had lossy compression applied, this leads to the image being
compressed twice over which can produce a poor result. To ensure the best
quality output images, it's recommended to upload high quality source images 
(minimal or no compression) in to your asset store, and let Silverstripe CMS take
care of applying compression.

Very high resolution images may cause GD to crash (especially on shared hosting 
environments where resources are limited) so a good size for website images is 
around 2000px on the longest edge.

#### Forced resampling

Since the 'master' images in your asset store may have a large file size, Silverstripe CMS
can apply compression to your images to save bandwidth - even if no other manipulation
(such as a crop or resize) is taking place. In many cases this can result in a smaller
overall file size, which may be appropriate for streaming to web users.

Please note that turning this feature on can increase the server memory requirements,
and is off by default to conserve resources.

You can turn this on with the below config:

```yml
---
Name: resamplefiles
---
SilverStripe\Assets\File:
  force_resample: true
SilverStripe\Assets\Storage\DBFile:
  force_resample: true
```

#### Resampled image quality

To adjust the quality of the generated images when they are resampled, add the
following to your `app/_config/config.yml` file:


```yml
SilverStripe\Core\Injector\Injector:
  SilverStripe\Assets\Image_Backend:
    properties:
      Quality: 90
```

### Lazy Loading {#lazy-loading}

Most modern browsers support the ability to "lazy load" images by adding a `loading="lazy"` attribute 
to the `<img />` tag. This defers the loading of images not in the viewport to improve the initial 
page load performance.

Silverstripe CMS automatically adds the `loading="lazy"` to images added in an HTML editor field
and to images rendered via a SS template file.

Content authors have the ability to selectively disable lazy loading when inserting images in an
HTML editor field.

Read [Browser-level image lazy-loading for the web](https://web.dev/browser-level-image-lazy-loading/)
on _web.dev_ for more information.

#### Selectively disabling lazy loading in SS templates

Images that are expected to be initially visible on page load, should be _eager_ loaded. This 
provides a small performance gain since the browser doesn't have to render the entire page layout 
before determining if the images need to be loaded. When in doubt, it's usually preferable to lazy 
load the image.

Developers can selectively disable lazy loading for individual image in a SS template by calling 
`LazyLoad(false)` on the image variable (e.g.: `$MyImage.LazyLoad(false)`).

```ss
<!-- Image will be lazy loaded -->
$Logo

<!-- Image will NOT be lazy loaded -->
$Logo.LazyLoad(false)

<!-- We're allowing content authors to choose if the image is eager loaded-->
$Logo.LazyLoad($LogoLoading)
```

Developers can allow content authors to control the loading attribute of a specific image by 
adding a lazy load field next to the [`UploadField`](api:SilverStripe\Assets\UploadField).

```php
<?php
use SilverStripe\AssetAdmin\Forms\UploadField;
use SilverStripe\Assets\Image;
use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Forms\DropdownField;

class Page extends SiteTree
{
    private static $db = [
        'LogoLoading' => 'Boolean'
    ];

    private static $has_one = [
        'Logo' => Image::class
    ];

    private static $defaults = [
        'LogoLoading' => true
    ];


    public function getCMSFields()
    {
        $fields = parent::getCMSFields();

        $loadingSource = [
            true => 'Lazy (Default)',
            false => 'Eager'
        ];
        
        $fields->addFieldsToTab(
            'Root.Main',
            [
                UploadField::create('Logo'),
                DropdownField::create('LogoLoading', 'Loading', $loadingSource)
            ]
        );

        return $fields;
    }
}
```

#### Controlling lazy loading in for &lt;img&gt; tags in SS templates

If you are manually writing `<img>` tags in your SS template, those images will not be automatically
lazy loaded. You will need to add the `loading="lazy"` attribute yourself if you want the image to be
lazy loaded.

Images that don't have dimensions should not be lazy loaded as that might alter the layout of the
page after the initial page load.

```ss
<img src="$Logo.URL" width="$Logo.Width" height="$Logo.Height" loading="lazy" alt="Company Logo" />

<!-- The size of this image is controlled by a CSS class so it can be lazy loaded -->
<img src="$resourceURL('themes/example/images/footer.png')" class="64x64square" loading="lazy" alt="" />

<!-- We don't have dimension for this image, so we eager load it -->
<img src="//example.com/sponsor.webp" alt="A generous sponsor" />
```

#### Disabling lazy loading globally

To opt out of lazy loading globally, notably if you already have a custom lazy loading 
implementation, use the following yml config:

```yml
SilverStripe\Assets\Image:
  lazy_loading_enabled: false
```

## Changing the manipulation driver to Imagick

If you want to change the image manipulation driver to use Imagick instead of GD, you'll need to change your config so
that the `Intervention\Image\ImageManager` is instantiated with the `imagick` driver instead of GD:

```yml
SilverStripe\Core\Injector\Injector:
  Intervention\Image\ImageManager:
    constructor:
      - { driver: imagick }
```

## Storage

Manipulated images are stored as "file variants" in the same folder structure as the original image. The storage mechanism is described in the ["File Storage" guide](file_storage).

## API Documentation

 * [File](api:SilverStripe\Assets\File)
 * [Image](api:SilverStripe\Assets\Image)
 * [DBFile](api:SilverStripe\Assets\Storage\DBFile)
 * [ImageManipulation](api:SilverStripe\Assets\ImageManipulation)

## Related Lessons
* [Working with files and images](https://www.silverstripe.org/learn/lessons/v4/working-with-files-and-images-1)
 
