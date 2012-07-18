# Image

## Introduction

Represents an image object through the `[api:Image]` class, inheriting all base functionality from the `[api:File]` class with extra functionality including resizing.

## Usage

### Managing images through form fields

Images can be uploaded like any other file, through `[api:FileField]`.
More advanced usage is possible through `[api:UploadField]`,
which provides thumbnails, a detail view of the image properties,
and management of relationships to other DataObject instances.
Allows upload of images through limiting file extensions with `setAllowedExtensions()`.

### Inserting images into the WYSIWYG editor

See [Topics: Rich Text Editing](/topics/rich-text-editing).

### Resizing Images in PHP

The following are methods defined on the GD class which you can call on Image Objects. Note to get the following to work
you need to have GD2 support in your PHP installation and because these generate files you must have write access to
your tmp folder. 

	:::php
	// manipulation functions
	$image->resize(width,height); // Basic resize, just skews the image
	$image->resizeRatio(width,height) // Resizes an image with max width and height
	$image->paddedResize(width,height) // Adds padding after resizing to width or height.
	$image->croppedResize(width,height) // Crops the image from the centre, to given values.
	$image->resizeByHeight(height) // Maximum height the image resizes to, keeps proportion
	$image->resizeByWidth(width) // Maximum width the image resizes to, keeps proportion 
	$image->greyscale(r,g,b) // alters image channels ===
	
	// values
	$image->getHeight() // Returns the height of the image.
	$image->getWidth() // Returns the width of the image
	$image->getOrienation() // Returns a class constant: ORIENTATION_SQUARE or ORIENTATION_PORTRAIT or ORIENTATION_LANDSCAPE


You can also create your own functions by extending the image class, for example

	:::php
	class MyImage extends Image {
		public function generateRotateClockwise(GD $gd)	{
			return $gd->rotate(90);
		}
		
		public function generateRotateCounterClockwise(GD $gd)	{
			return $gd->rotate(270);
		}
		
		public function clearResampledImages()	{
			$files = glob(Director::baseFolder().'/'.$this->Parent()->Filename."_resampled/*-$this->Name");
		 	foreach($files as $file) {unlink($file);}
		}
		
		public function Landscape()	{
			return $this->getWidth() > $this->getHeight();
		}
		
		public function Portrait() {
			return $this->getWidth() < $this->getHeight();
		}
		
		public function generatePaddedImageByWidth(GD $gd,$width=600,$color="fff"){
			return $gd->paddedResize($width, round($gd->getHeight()/($gd->getWidth()/$width),0),$color);
		}
		
		public function Exif(){
			//http://www.v-nessa.net/2010/08/02/using-php-to-extract-image-exif-data
			$image = $this->AbsoluteURL;
			$d=new ArrayList();	
			$exif = exif_read_data($image, 0, true);
			foreach ($exif as $key => $section) {
				$a=new ArrayList();	
				foreach ($section as $name => $val)
					$a->push(new ArrayData(array("Title"=>$name,"Content"=>$val)));
				$d->push(new ArrayData(array("Title"=>strtolower($key),"Content"=>$a)));
			}
			return $d;
		}
	}

### Resizing in Templates

You can call certain resize functions directly from the template, to use the inbuilt GD functions as the template parser
supports these, for example SetWidth() or SetHeight().  

For output of an image tag with the image automatically resized to 80px width, you can use:

	:::php
	$Image.SetWidth(80) // returns a image 80px wide, ratio kept the same
	$Image.SetHeight(80) // returns a image 80px tall, ration kept the same
	$Image.SetSize(80,80) // returns a 80x80px padded image
	$Image.SetRatioSize(80,80) // Returns an image scaled proportional, with its greatest diameter scaled to 80px
	$Image.PaddedImage(80, 80) // Returns an 80x80 image. Unused space is padded white. No crop. No stretching
	$Image.Width // returns width of image
	$Image.Height // returns height of image
	$Image.Orientation // returns Orientation
	$Image.Filename // returns filename
	$Image.URL // returns filename


### Form Upload

For usage on a website form, see `[api:FileField]`.
If you want to upload images within the CMS, see `[api:UploadField]`.

### Clearing Thumbnail Cache

Images are (like all other Files) synchronized with the SilverStripe database.
This syncing happens whenever you load the "Files & Images" interface,
and whenever you upload or modify an Image through SilverStripe.

If you encounter problems with images not appearing, or have mysteriously disappeared, you can try manually flushing the
image cache.

	http://www.mysite.com/images/flush

## API Documentation
`[api:Image]`
