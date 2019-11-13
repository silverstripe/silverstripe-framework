---
title: UploadField
summary: Use a highly configurable form field to upload files
icon: upload
---

# UploadField

## Introduction
The UploadField will let you upload one or multiple files of all types, including images. But that's not all it does - it will also link the uploaded file(s) to an existing relation and let you edit the linked files as well. That makes it flexible enough to sometimes even replace the GridField, like for instance in creating and managing a simple gallery.
 
## Usage
The field can be used in three ways: To upload a single file into a `has_one` relationship,or allow multiple files into a `has_many` or `many_many` relationship, or to act as a stand
alone uploader into a folder with no underlying relation.

## Validation
Although images are uploaded and stored on the filesystem immediately after selection,the value (or values) of this field will not be written to any related record until the record is saved and successfully validated. However, any invalid records will still persist across form submissions until explicitly removed or replaced by the user.

Care should be taken as invalid files may remain within the filesystem until explicitly removed.

### Single fileupload

The following example adds an UploadField to a page for single fileupload, based on a has_one relation: 

```

The UploadField will auto-detect the relation based on it's `name` property, and save it into the GalleyPages' `SingleImageID` field. Setting the `setAllowedMaxFileNumber` to 1 will make sure that only one image can ever be uploaded and linked to the relation.	

### Multiple fileupload
Enable multiple fileuploads by using a many_many (or has_many) relation. Again, the `UploadField` will detect the relation based on its $name property value:

```

```

```
[notice]
In order to link both ends of the relationship together it's usually advisable to extend Image with the necessary $has_one, $belongs_to, $has_many or $belongs_many_many. In particular, a DataObject with $has_many Images will not work without this specified explicitly.
[/notice]

## Configuration
### Overview
The field can either be configured on an instance level with the various getProperty and setProperty functions, or globally by overriding the YAML defaults.

See the [Configuration Reference](uploadfield#configuration-reference) section for possible values.

Example: mysite/_config/uploadfield.yml

```

### Set a custom folder
This example will save all uploads in the `/assets/customfolder/` folder. If the folder doesn't exist, it will be created. 

```
### Limit the allowed filetypes
`AllowedExtensions` defaults to the `File.allowed_extensions` configuration setting, but can be overwritten for each UploadField:

```

Entire groups of file extensions can be specified in order to quickly limit types to known file categories.

```
This will limit files to the following extensions: bmp gif jpg jpeg pcx tif png alpha als cel icon ico ps doc docx txt rtf xls xlsx pages ppt pptx pps csv html htm xhtml xml pdf.

`AllowedExtensions` can also be set globally via the [YAML configuration](/developer_guides/configuration/configuration/#configuration-yaml-syntax-and-rules), for example you may add the following into your mysite/_config/config.yml:

```

### Limit the maximum file size
`AllowedMaxFileSize` is by default set to the lower value of the 2 php.ini configurations: `upload_max_filesize` and `post_max_size`. The value is set as bytes.

NOTE: this only sets the configuration for your UploadField, this does NOT change your server upload settings, so if your server is set to only allow 1 MB and you set the UploadField to 2 MB, uploads will not work.

```

You can also specify a default global maximum file size setting in your config for different file types. This is overridden when specifying the max allowed file size on the UploadField instance.

```

### Preview dimensions
Set the dimensions of the image preview. By default the max width is set to 80 and the max height is set to 60.

```

### Disable attachment of existing files
This can force the user to upload a new file, rather than link to the already existing file library

```

### Disable uploading of new files
Alternatively, you can force the user to only specify already existing files in the file library

```
	

### Automatic or manual upload
By default, the UploadField will try to automatically upload all selected files. Setting the `autoUpload` property to false, will present you with a list of selected files that you can then upload manually one by one:

```

### Change Detection
The CMS interface will automatically notify the form containing
an UploadField instance of changes, such as a new upload,
or the removal of an existing upload (through a `dirty` event).
The UI can then choose an appropriate response (e.g. highlighting the "save" button). If the UploadField doesn't save into a relation, there's technically no saveable change (the upload has already happened), which is why this feature can be disabled on demand.

```

### Build a simple gallery
A gallery most times needs more then simple images. You might want to add a description, or maybe some settings to define a transition effect for each slide.
 
First create a [DataExtension](/developer_guides/extending/extensions) like this:

```

Now register the DataExtension for the Image class in your mysite/_config/config.yml:

```

[notice]
Note: Although you can subclass the Image class instead of using a DataExtension, this is not advisable. For instance: when using a subclass, the 'From files' button will only return files that were uploaded for that subclass, it won't recognize any other images!
[/notice]

### Edit uploaded images
By default the UploadField will let you edit the following fields: *Title, Filename, Owner and Folder*. The fileEditFields` configuration setting allows you you alter these settings. One way to go about this is create a `getCustomFields` function in your GalleryImage object like this:

``` 
	
Then, in your GalleryPage, tell the UploadField to use this function:

```

In a similar fashion you can use 'setFileEditActions' to set the actions for the editform, or 'fileEditValidator' to determine the validator (e.g. RequiredFields). 

### Configuration Reference
 - `setAllowedMaxFileNumber`: (int) php validation of allowedMaxFileNumber only works when a db relation is available, set to null to allow unlimited if record has a has_one and allowedMaxFileNumber is null, it will be set to 1.

 - `setAllowedFileExtensions`: (array) List of file extensions allowed.

 - `setAllowedFileCategories`: (array|string) List of types of files allowed. May be any of 'image', 'audio', 'mov', 'zip', 'flash', or 'doc'.

 - `setAutoUpload`: (boolean) Should the field automatically trigger an upload once a file is selected?

 - `setCanAttachExisting`: (boolean|string) Can the user attach existing files from the library. String values are interpreted as permission codes.

 - `setCanPreviewFolder`: (boolean|string) Can the user preview the folder files will be saved into? String values are interpreted as permission codes.

 - `setCanUpload`: (boolean|string) Can the user upload new files, or just select from existing files. String values are interpreted as permission codes.

 - `setDownloadTemplateName`: (string) javascript template used to display already uploaded files, see javascript/UploadField_downloadtemplate.js.

 - `setFileEditFields`: (FieldList|string) FieldList $fields or string $name (of a method on File to provide a fields) for the EditForm (Example: 'getCMSFields').

 - `setFileEditActions`: (FieldList|string) FieldList $actions or string $name (of a method on File to provide a actions) for the EditForm (Example: 'getCMSActions').

 - `setFileEditValidator`: (string) Validator (eg RequiredFields) or string $name (of a method on File to provide a Validator) for the EditForm (Example: 'getCMSValidator').

 - `setOverwriteWarning`: (boolean) Show a warning when overwriting a file.

 - `setPreviewMaxWidth`: (int).

 - `setPreviewMaxHeight`: (int).

 - `setTemplateFileButtons`: (string) Template name to use for the file buttons.

 - `setTemplateFileEdit`: (string) Template name to use for the file edit form.

 - `setUploadTemplateName`: (string) javascript template used to display uploading files, see javascript/UploadField_uploadtemplate.js.

 - `setCanPreviewFolder`: (boolean|string) Is the upload folder visible to uploading users? String values are interpreted as permission codes.

Certain default values for the above can be configured using the YAML config system.

```

The above settings can also be set on a per-instance basis by using `setConfig` with the appropriate key.

The `Upload_Validator` class has configuration options for setting the `default_max_file_size`.

```

You can specify the file extension or the app category (as specified in the `File` class) in square brackets. It supports setting the file size in bytes or using the syntax supported by `File::ini2bytes()`.


You can also configure the underlying [api:Upload] class, by using the YAML config system.

```
  
## Using the UploadField in a frontend form
The UploadField can be used in a frontend form, given that sufficient attention is given to the permissions granted to non-authorised users.

By default Image::canDelete and Image::canEdit do not require admin privileges, so make sure you override the methods in your Image extension class.

For instance, to generate an upload form suitable for saving images into a user-defined gallery the below code could be used:

*In GalleryPage.php:*

```

*Gallery.php:*

```

*ImageExtension.php:*
	
```

*mysite/_config/config.yml*

```
