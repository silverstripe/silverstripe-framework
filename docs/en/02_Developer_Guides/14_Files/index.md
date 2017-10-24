title: Files
summary: Upload, manage and manipulate files and images.
introduction: Upload, manage and manipulate files and images.

# Files

## Introduction

File management and abstraction is provided by the [silverstripe/assets](https://github.com/silverstripe/silverstripe-assets).
This provides the basis for the storage of all non-static files and resources usable by a SilverStripe web application.

By default the [api:SilverStripe\Assets\File] has these characteristics:

 - A default permission model based on folder hierarchy.
 - Versioning of files, including the ability to draft modifications to files and subsequently publish them.
 - Physical protection of both unpublished and secured files, allowing restricted access as needed.
 - An abstract storage based on the [flysystem](https://flysystem.thephpleague.com/) library, which can be
   substituted for any other backend.
 - Can be embedded into any HTML field via shortcodes. 

# Read more

[CHILDREN]

## API Documentation

* [File](api:SilverStripe\Assets\File)
* [Image](api:SilverStripe\Assets\Image)
* [DBFile](api:SilverStripe\Assets\Storage\DBFile)
* [Folder](api:SilverStripe\Assets\Folder)
