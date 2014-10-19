title: UploadField
summary: How to use the UploadField class for uploading assets.

# UploadField

## Introduction

The UploadField will let you upload one or multiple files of all types, 
including images. But that's not all it does - it will also link the 
uploaded file(s) to an existing relation and let you edit the linked files  
as well. That makes it flexible enough to sometimes even replace the Gridfield,  
like for instance in creating and managing a simple gallery.
 
## Usage

The field can be used in three ways: To upload a single file into a `has_one` relationship,
or allow multiple files into a `has_many` or `many_many` relationship, or to act as a stand
alone uploader into a folder with no underlying relation.

## Validation

Although images are uploaded and stored on the filesystem immediately after selection,
the value (or values) of this field will not be written to any related record until
the record is saved and successfully validated. However, any invalid records will still
persist across form submissions until explicitly removed or replaced by the user.

Care should be taken as invalid files may remain within the filesystem until explicitly
removed.

### Single fileupload

The following example adds an UploadField to a page for single fileupload, 
based on a has_one relation: 

	:::php
	class GalleryPage extends Page {
	
    	private static $has_one = array(
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
`setAllowedMaxFileNumber` to 1 will make sure that only one image can ever be 
uploaded and linked to the relation.	

### Multiple fileupload

Enable multiple fileuploads by using a many_many (or has_many) relation. Again,
the `UploadField` will detect the relation based on its $name property value:

	:::php
	class GalleryPage extends Page {
	
		private static $many_many = array(
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
			$uploadField->setAllowedMaxFileNumber(10);
			
			return $fields;			
		}	
	}
	class GalleryPage_Controller extends Page_Controller {
	}

	class GalleryImageExtension extends DataExtension {
		private static $belongs_many_many = array('Galleries' => 'GalleryPage);
	}

	Image::add_extension('GalleryImageExtension');

<div class="notice" markdown='1'>
In order to link both ends of the relationship together it's usually advisable to 
extend Image with the necessary $has_one, $belongs_to, $has_many or $belongs_many_many.
In particular, a DataObject with $has_many Images will not work without this specified explicitly.
</div>

## Configuration

### Overview

The field can either be configured on an instance level with the various
getProperty and setProperty functions, or globally by overriding the YAML defaults.
See the [Configuration Reference](uploadfield#configuration-reference) section for possible values.

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

### Limit the allowed filetypes

`AllowedExtensions` defaults to the `File.allowed_extensions` configuration setting, 
but can be overwritten for each UploadField:

	:::php
	$uploadField->setAllowedExtensions(array('jpg', 'jpeg', 'png', 'gif'));

Entire groups of file extensions can be specified in order to quickly limit types
to known file categories.

	:::php
	// This will limit files to the following extensions:
	// "bmp" ,"gif" ,"jpg" ,"jpeg" ,"pcx" ,"tif" ,"png" ,"alpha","als" ,"cel" ,"icon" ,"ico" ,"ps"
	// 'doc','docx','txt','rtf','xls','xlsx','pages', 'ppt','pptx','pps','csv', 'html','htm','xhtml', 'xml','pdf'
	$uploadField->setAllowedFileCategories('image', 'doc');

`AllowedExtensions` can also be set globally via the [YAML configuration](/topics/configuration#setting-configuration-via-yaml-files), for example you may add the following into your mysite/_config/config.yml:

	:::yaml
	File: 
	  allowed_extensions: 
	    - 7zip 
	    - xzip

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
	$uploadField->setPreviewMaxWidth(100);
	$uploadField->setPreviewMaxHeight(100);

### Disable attachment of existing files

This can force the user to upload a new file, rather than link to the already
existing file librarry

	:::php
	$uploadField->setCanAttachExisting(false);

### Disable uploading of new files

Alternatively, you can force the user to only specify already existing files
in the file library

	:::php
	$uploadField->setCanUpload(false);
	
### Automatic or manual upload

By default, the UploadField will try to automatically upload all selected files. 
Setting the `autoUpload` property to false, will present you with a list of 
selected files that you can then upload manually one by one:

	:::php
	$uploadField->setAutoUpload(false);

### Change Detection

The CMS interface will automatically notify the form containing
an UploadField instance of changes, such as a new upload,
or the removal of an existing upload (through a `dirty` event).
The UI can then choose an appropriate response (e.g. highlighting the "save" button). 
If the UploadField doesn't save into a relation, there's
technically no saveable change (the upload has already happened),
which is why this feature can be disabled on demand.

	:::php
	$uploadField->setConfig('changeDetection', false);

### Build a simple gallery

A gallery most times needs more then simple images. You might want to add a 
description, or maybe some settings to define a transition effect for each slide. 
First create a 
[DataExtension](http://doc.silverstripe.org/framework/en/reference/dataextension) 
like this:

	:::php
	class GalleryImage extends DataExtension {

		private static $db = array(
			'Description' => 'Text'
		);
		
		private static $belongs_many_many = array(
			'GalleryPage' => 'GalleryPage'
		);
	}

Now register the DataExtension for the Image class in your _config.php:

	:::php
	Image::add_extension('GalleryImage');

<div class="notice" markdown='1'>
Note: Although you can subclass the Image class instead of using a DataExtension,
this is not advisable. For instance: when using a subclass, the 'From files'
button will only return files that were uploaded for that subclass, it won't
recognize any other images!
</div>

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
	$uploadField->setFileEditFields('getCustomFields');

In a similar fashion you can use 'setFileEditActions' to set the actions for the 
editform, or 'fileEditValidator' to determine the validator (eg RequiredFields). 

### Configuration Reference

 - `setAllowedMaxFileNumber`: (int) php validation of allowedMaxFileNumber 
   only works when a db relation is available, set to null to allow
   unlimited if record has a has_one and allowedMaxFileNumber is null, it will be set to 1
 - `setAllowedFileExtensions`: (array) List of file extensions allowed
 - `setAllowedFileCategories`: (array|string) List of types of files allowed.
   May be any of 'image', 'audio', 'mov', 'zip', 'flash', or 'doc'
 - `setAutoUpload`: (boolean) Should the field automatically trigger an upload once
   a file is selected?
 - `setCanAttachExisting`: (boolean|string) Can the user attach existing files from the library.
   String values are interpreted as permission codes.
 - `setCanPreviewFolder`: (boolean|string) Can the user preview the folder files will be saved into?
   String values are interpreted as permission codes.
 - `setCanUpload`: (boolean|string) Can the user upload new files, or just select from existing files.
   String values are interpreted as permission codes.
 - `setDownloadTemplateName`: (string) javascript template used to display already 
   uploaded files, see javascript/UploadField_downloadtemplate.js
 - `setFileEditFields`: (FieldList|string) FieldList $fields or string $name 
   (of a method on File to provide a fields) for the EditForm (Example: 'getCMSFields')
 - `setFileEditActions`: (FieldList|string) FieldList $actions or string $name 
   (of a method on File to provide a actions) for the EditForm (Example: 'getCMSActions')
 - `setFileEditValidator`: (string) Validator (eg RequiredFields) or string $name 
   (of a method on File to provide a Validator) for the EditForm (Example: 'getCMSValidator')
 - `setOverwriteWarning`: (boolean) Show a warning when overwriting a file.
 - `setPreviewMaxWidth`: (int)
 - `setPreviewMaxHeight`: (int)
 - `setTemplateFileButtons`: (string) Template name to use for the file buttons
 - `setTemplateFileEdit`: (string) Template name to use for the file edit form
 - `setUploadTemplateName`: (string) javascript template used to display uploading 
   files, see javascript/UploadField_uploadtemplate.js
 - `setCanPreviewFolder`: (boolean|string) Is the upload folder visible to uploading users?
   String values are interpreted as permission codes.

Certain default values for the above can be configured using the YAML config system.

	:::yaml
	UploadField:
	  defaultConfig:
		autoUpload: true
		allowedMaxFileNumber:
		canUpload: true
		canAttachExisting: 'CMS_ACCESS_AssetAdmin'
		canPreviewFolder: true
		previewMaxWidth: 80
		previewMaxHeight: 60
		uploadTemplateName: 'ss-uploadfield-uploadtemplate'
		downloadTemplateName: 'ss-uploadfield-downloadtemplate'
		overwriteWarning: true # Warning before overwriting existing file (only relevant when Upload: replaceFile is true)

The above settings can also be set on a per-instance basis by using `setConfig` with the appropriate key.

You can also configure the underlying `[api:Upload]` class, by using the YAML config system.

	:::yaml
	Upload:
	  # Globally disables automatic renaming of files and displays a warning before overwriting an existing file
	  replaceFile: true
	  uploads_folder: 'Uploads'
  
## Using the UploadField in a frontend form

The UploadField can be used in a frontend form, given that sufficient attention is given
to the permissions granted to non-authorised users.

By default Image::canDelete and Image::canEdit do not require admin privileges, so 
make sure you override the methods in your Image extension class.

For instance, to generate an upload form suitable for saving images into a user-defined
gallery the below code could be used:

	:::php

	// In GalleryPage.php
	class GalleryPage extends Page {}
	class GalleryPage_Controller extends Page_Controller {
		private static $allowed_actions = array('Form');
		public function Form() {
			$fields = new FieldList(
				new TextField('Title', 'Title', null, 255),
				$field = new UploadField('Images', 'Upload Images')
			); 
			$field->setCanAttachExisting(false); // Block access to Silverstripe assets library
			$field->setCanPreviewFolder(false); // Don't show target filesystem folder on upload field
			$field->relationAutoSetting = false; // Prevents the form thinking the GalleryPage is the underlying object
			$actions = new FieldList(new FormAction('submit', 'Save Images'));
			return new Form($this, 'Form', $fields, $actions, null);
		}

		public function submit($data, Form $form) {
			$gallery = new Gallery();
			$form->saveInto($gallery);
			$gallery->write();
			return $this;
		}
	}

	// In Gallery.php
	class Gallery extends DataObject {	
		private static $db = array(
			'Title' => 'Varchar(255)'
		);

		private static $many_many = array(
			'Images' => 'Image'
		);
	}

	// In ImageExtension.php
	class ImageExtension extends DataExtension {

		private static $belongs_many_many = array(
			'Gallery' => 'Gallery'
		);

		function canEdit($member) {
			// This part is important!
			return Permission::check('ADMIN');
		}
	}
	Image::add_extension('ImageExtension');
