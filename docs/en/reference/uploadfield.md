# UploadField

## Introduction

The UploadField will let you upload one or multiple files of all types, 
including images. But that's not all it does - it will also link the 
uploaded file(s) to an existing relation and let you edit the linked files  
as well. That makes it flexible enough to sometimes even replace the Gridfield,  
like for instance in creating and managing a simple gallery.
 
## Usage

The field can be used in two ways: To upload a single file into a `has_one` relationship,
or allow multiple files into a fixed folder (or relationship).

### Single fileupload

The following example adds an UploadField to a page for single fileupload, 
based on a has_one relation: 

	:::php
	class GalleryPage extends Page {
	
    	static $has_one = array(
        	'SingleImage' => 'Image'
    	);
		
		function getCMSFields() {
			
			$fields = parent::getCMSFields(); 
			
			$fields->addFieldToTab(
				'Root.Upload',	
				$uploadField = new UploadField(
					$name = 'SingleImage',
					$title = 'Upload a single image'
				)	
			);
			return $fields;			
		}	
	}

The UploadField will autodetect the relation based on it's `name` property, and 
save it into the GalleyPages' `SingleImageID` field. Setting the 
`allowedMaxFileNumber` to 1 will make sure that only one image can ever be 
uploaded and linked to the relation.	

### Multiple fileupload

Enable multiple fileuploads by using a many_many relation. Again, the 
UploadField will detect the relation based on its $name property value:

	:::php
	class GalleryPage extends Page {
	
		static $many_many = array(
			'GalleryImages' => 'Image'
		);
		
		function getCMSFields() {
			
			$fields = parent::getCMSFields(); 
			
			$fields->addFieldToTab(
				'Root.Upload',	
				$uploadField = new UploadField(
					$name = 'GalleryImages',
					$title = 'Upload one or more images (max 10 in total)'
				)	
			);
			$uploadField->setConfig('allowedMaxFileNumber', 10);
			
			return $fields;			
		}	
	}
	class GalleryPage_Controller extends Page_Controller {
	}
	
WARNING: Currently the UploadField doesn't fully support has_many relations, so use a many_many relation instead! 	

## Configuration

### Overview

The field can either be configured on an instance level through `setConfig(<key>, <value>)`,
or globally by overriding the YAML defaults. See the [Configuration Reference](uploadfield#configuration-reference) section for possible values.

Example: mysite/_config/uploadfield.yml

	after: framework#uploadfield
	---
	UploadField:
	  defaultConfig:
	    canUpload: false


### Set a custom folder

This example will save all uploads in the `/assets/customfolder/` folder. If 
the folder doesn't exist, it will be created. 

	:::php
	$fields->addFieldToTab(
		'Root.Upload',	
		$uploadField = new UploadField(
			$name = 'GalleryImages',
			$title = 'Please upload one or more images'		)	
	);
	$uploadField->setFolderName('customfolder');

## Limit the allowed filetypes

`AllowedExtensions` is by default `File::$allowed_extensions` but can be overwritten for each UploadField:

	:::php
	$uploadField->getValidator()->setAllowedExtensions(array('jpg', 'jpeg', 'png', 'gif'));


### Limit the maximum file size

`AllowedMaxFileSize` is by default set to the lower value of the 2 php.ini configurations: `upload_max_filesize` and `post_max_size`
The value is set as bytes.

NOTE: this only sets the configuration for your UploadField, this does NOT change your server upload settings, so if your server is set to only allow 1 MB and you set the UploadFIeld to 2 MB, uploads will not work.

	:::php
	$sizeMB = 2; // 2 MB
	$size = $sizeMB * 1024 * 1024; // 2 MB in bytes
	$this->getValidator()->setAllowedMaxFileSize($size);

### Preview dimensions

Set the dimensions of the image preview. By default the max width is set to 80 
and the max height is set to 60.

	:::php
	$uploadField->setConfig('previewMaxWidth', 100);
	$uploadField->setConfig('previewMaxHeight', 100);
	
### Automatic or manual upload

By default, the UploadField will try to automatically upload all selected files. 
Setting the `autoUpload` property to false, will present you with a list of 
selected files that you can then upload manually one by one:

	:::php
	$uploadField->setConfig('autoUpload', false);
	
### Build a simple gallery

A gallery most times needs more then simple images. You might want to add a 
description, or maybe some settings to define a transition effect for each slide. 
First create a 
[DataExtension](http://doc.silverstripe.org/framework/en/reference/dataextension) 
like this:

	:::php
	class GalleryImage extends DataExtension {

		static $db = array(
			'Description' => 'Text'
		);
		
		public static $belongs_many_many = array(
			'GalleryPage' => 'GalleryPage'
		);
	}

Now register the DataExtension for the Image class in your _config.php:

	:::php
	Object::add_extension('Image', 'GalleryImage');
	
NOTE: although you can subclass the Image class instead of using a DataExtension, this is not advisable. For instance: when using a subclass, the 'From files' button will only return files that were uploaded for that subclass, it won't recognize any other images!			
### Edit uploaded images

By default the UploadField will let you edit the following fields: *Title, 
Filename, Owner and Folder*. The `fileEditFields` configuration setting allows 
you you alter these settings. One way to go about this is create a 
`getCustomFields` function in your GalleryImage object like this:

	:::php
	class GalleryImage extends DataExtension {
		...
		
		function getCustomFields() {
			$fields = new FieldList();
			$fields->push(new TextField('Title', 'Title'));
			$fields->push(new TextareaField('Description', 'Description'));
			return $fields;
		}
	} 
	
Then, in your GalleryPage, tell the UploadField to use this function:

	:::php
	$uploadField->setConfig('fileEditFields', 'getCustomFields');

In a similar fashion you can use 'fileEditActions' to set the actions for the 
editform, or 'fileEditValidator' to determine the validator (eg RequiredFields). 

### Configuration Reference

 - `autoUpload`: (boolean)
 - `allowedMaxFileNumber`: (int) php validation of allowedMaxFileNumber 
   only works when a db relation is available, set to null to allow
   unlimited if record has a has_one and allowedMaxFileNumber is null, it will be set to 1
 - `canUpload`: (boolean) Can the user upload new files, or just select from existing files.
   String values are interpreted as permission codes.
 - `previewMaxWidth`: (int)
 - `previewMaxHeight`: (int)
 - `uploadTemplateName`: (string) javascript template used to display uploading 
   files, see javascript/UploadField_uploadtemplate.js
 - `downloadTemplateName`: (string) javascript template used to display already 
   uploaded files, see javascript/UploadField_downloadtemplate.js
 - `fileEditFields`: (FieldList|string) FieldList $fields or string $name 
   (of a method on File to provide a fields) for the EditForm (Example: 'getCMSFields')
 - `fileEditActions`: (FieldList|string) FieldList $actions or string $name 
   (of a method on File to provide a actions) for the EditForm (Example: 'getCMSActions')
 - `fileEditValidator`: (string) Validator (eg RequiredFields) or string $name 
   (of a method on File to provide a Validator) for the EditForm (Example: 'getCMSValidator')
  
## TODO: Using the UploadField in a frontend form

*At this moment the UploadField not yet fully supports being used on a frontend 
form.* 
