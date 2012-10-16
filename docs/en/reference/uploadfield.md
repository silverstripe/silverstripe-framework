# UploadField

## Introduction

The UploadField will let you upload one or multiple files of all types, 
including images. But that's not all it does - it will also link the 
uploaded file(s) to an existing relation and let you edit the linked files  
as well. That makes it flexible enough to sometimes even replace the Gridfield,  
like for instance in creating and managing a simple gallery.
 
## Usage
The UploadField can be used in two ways:

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

## Set a custom folder

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

	:::php
	$uploadField->allowedExtensions = array('jpg', 'gif', 'png');


## Other configuration settings

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
First create an extended Image class:

	:::php
	class GalleryItem extends Image {

		static $db = array(
			'Description' => 'Varchar(255)'
		);
	}

Now simply change the GalleryPage to use the new class:

	:::php
	class GalleryPage extends Page {
	
		static $many_many = array(
			'GalleryImages' => 'GalleryItem'
		);	
		
### Edit uploaded images

By default the UploadField will let you edit the following fields: *Title, 
Filename, Owner and Folder*. The `fileEditFields` configuration setting allows 
you you alter these settings. One way to go about this is create a 
`getCustomFields` function in your GalleryItem object like this:

	:::php
	class GalleryItem extends Image {
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
  
## TODO: Using the UploadField in a frontend form

*At this moment the UploadField not yet fully supports being used on a frontend 
form.* 
