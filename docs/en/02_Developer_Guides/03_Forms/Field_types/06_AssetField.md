# AssetField

## Introduction

This form field can be used to upload files into SilverStripe's asset store.
It associates a file directly to a `DataObject` through the `[api:DBFile]` database field.
Saving the file association directly in a `DataObject` (as opposed to a relation)
can simplify data management and publication.

In order to create `[api:File]` records to contain uploaded files,
please use the [AssetField](AssetField) instead.

## Usage

The field expects to save into a `DataObject` record with a `DBFile`
property matching the name of the field itself.


```php
	class Team extends DataObject {
	
    	private static $db = array(
        	'BannerImage' => 'DBFile'
    	);
		
		function getCMSFields() {
			$fields = parent::getCMSFields(); 
			
			$fields->addFieldToTab(
				'Root.Upload',	
				$assetField = new AssetField(
					$name = 'BannerImage',
					$title = 'Upload a banner'
				)
			);
			// Restrict validator to include only supported image formats
			$assetField->setAllowedFileCategories('image/supported');

			return $fields;			
		}	
	}
```

## Validation

Although images are uploaded and stored on the filesystem immediately after selection,
the value (or values) of this field will not be written to any related record until the
record is saved and successfully validated. However, any invalid records will still
persist across form submissions until explicitly removed or replaced by the user.

Care should be taken as invalid files may remain within the filesystem until explicitly removed.

## Configuration

### Overview

AssetField can either be configured on an instance level with the various getProperty
and setProperty functions, or globally by overriding the YAML defaults.

See the [Configuration Reference](uploadfield#configuration-reference) section for possible values.

Example: mysite/_config/uploadfield.yml

	:::yaml
	after: framework#uploadfield
	---
	AssetField:
	  defaultConfig:
	    canUpload: false


### Set a custom folder

This example will save all uploads in the `customfolder` in the configured assets store root (normally under 'assets')
If the folder doesn't exist, it will be created. 

	:::php
	$fields->addFieldToTab(
		'Root.Upload',	
		$assetField = new AssetField(
			$name = 'GalleryImage',
			$title = 'Please upload an image'
		)	
	);
	$assetField->setFolderName('customfolder');


### Limit the allowed filetypes

`AllowedExtensions` defaults to the `File.allowed_extensions` configuration setting,
but can be overwritten for each AssetField:


	:::php
	$assetField->setAllowedExtensions(array('jpg', 'jpeg', 'png', 'gif'));
	

Entire groups of file extensions can be specified in order to quickly limit types to known file categories.
This can be done by using file category names, which are defined via the `File.app_categories` config. This
list could be extended with any custom categories.

The built in categories are:

| File category   | Example extensions | 
|-----------------|--------------------|
| archive         | zip, gz, rar       |
| audio           | mp3, wav, ogg      |
| document        | doc, txt, pdf      |
| flash           | fla, swf           |
| image           | jpg, tiff, ps      |
| image/supported | jpg, gif, png      |
| video           | mkv, avi, mp4      |

Note that although all image types are included in the 'image' category, only images that are in the 
'images/supported' list are compatible with the SilverStripe image manipulations API. Other types
can be uploaded, but cannot be resized.

	:::php
	$assetField->setAllowedFileCategories('image/supported');


This will limit files to the the compatible image formats: jpg, jpeg, gif, and png.

`AllowedExtensions` can also be set globally via the
[YAML configuration](/developer_guides/configuration/configuration/#configuration-yaml-syntax-and-rules),
for example you may add the following into your mysite/_config/config.yml:


	:::yaml
	File: 
	  allowed_extensions: 
	    - 7zip 
	    - xzip


### Limit the maximum file size

`AllowedMaxFileSize` is by default set to the lower value of the 2 php.ini configurations:
`upload_max_filesize` and `post_max_size`. The value is set as bytes.

NOTE: this only sets the configuration for your AssetField, this does NOT change your
server upload settings, so if your server is set to only allow 1 MB and you set the
AssetField to 2 MB, uploads will not work.


	:::php
	$sizeMB = 2; // 2 MB
	$size = $sizeMB * 1024 * 1024; // 2 MB in bytes
	$this->getValidator()->setAllowedMaxFileSize($size);


You can also specify a default global maximum file size setting in your config for different file types.
This is overridden when specifying the max allowed file size on the AssetField instance.


	:::yaml
	Upload_Validator: 
	  default_max_file_size: 
	    '[image]': '1m'
	    '[document]': '5m'
	    'jpeg': 2000


### Preview dimensions

Set the dimensions of the image preview. By default the max width is set to 80 and the max height is set to 60.


	:::php
	$assetField->setPreviewMaxWidth(100);
	$assetField->setPreviewMaxHeight(100);



### Disable uploading of new files

Alternatively, you can force the user to only specify already existing files in the file library


	:::php
	$assetField->setCanUpload(false);

	
### Automatic or manual upload

By default, the AssetField will try to automatically upload all selected files. Setting the `autoUpload`
property to false, will present you with a list of selected files that you can then upload manually one by one:


	:::php
	$assetField->setAutoUpload(false);


### Change Detection

The CMS interface will automatically notify the form containing
an AssetField instance of changes, such as a new upload,
or the removal of an existing upload (through a `dirty` event).
The UI can then choose an appropriate response (e.g. highlighting the "save" button).
If the AssetField doesn't save into a relation, there's technically no saveable change
(the upload has already happened), which is why this feature can be disabled on demand.


	:::php
	$assetField->setConfig('changeDetection', false);

## Configuration Reference

 * `setAllowedFileExtensions`: (array) List of file extensions allowed.
 * `setAllowedFileCategories`: (array|string) List of types of files allowed. May be any number of
   categories as defined in `File.app_categories` config.
 * `setAutoUpload`: (boolean) Should the field automatically trigger an upload once a file is selected?
 * `setCanPreviewFolder`: (boolean|string) Can the user preview the folder files will be saved into?
   String values are interpreted as permission codes.
 * `setCanUpload`: (boolean|string) Can the user upload new files, or just select from existing files.
   String values are interpreted as permission codes.
 * `setDownloadTemplateName`: (string) javascript template used to display already uploaded files, see
   javascript/UploadField_downloadtemplate.js.
 * `setPreviewMaxWidth`: (int).
 * `setPreviewMaxHeight`: (int).
 * `setTemplateFileButtons`: (string) Template name to use for the file buttons.
 * `setUploadTemplateName`: (string) javascript template used to display uploading files, see
   javascript/UploadField_uploadtemplate.js.
 * `setCanPreviewFolder`: (boolean|string) Is the upload folder visible to uploading users? String values
   are interpreted as permission codes.
