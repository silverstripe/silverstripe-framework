title: File storage
summary: Describes the persistence layer of files

# File storage

This section describes how the asset store abstraction layer stores the physical files underlying the ORM,
and explains some of the considerations. 

## Storage via database columns

Asset storage is provided out of the box via a [Flysystem](http://flysystem.thephpleague.com/) backed store.
However, any class that implements the `AssetStore` interface could be substituted to provide storage backends
via other mechanisms.

Internally, files are stored as [DBFile](api:SilverStripe\Assets\Storage\DBFile) records on the rows of parent objects.
These records are composite fields which contain sufficient information useful to the configured asset backend in order
to store, manage, and  publish files. By default this composite field behind this field stores the following details:


| Field name     | Description |
| ----------     | -----------   
| `Hash`         | The sha1 of the file content, useful for versioning (if supported by the backend) |
| `Filename`     | The internal identifier for this file, which may contain a directory path (not including assets). Multiple versions of the same file will have the same filename. |
| `Variant`      | The variant for this file. If a file has multiple derived versions (such as resized files or reformatted documents) then you can point to one of the variants here. |


Note that the `Hash` and `Filename` always point to the original file, if a `Variant` is specified. It is up to the
storage backend to determine how variants are managed.

Note that the storage backend used will not be automatically synchronised with the database. Only files which
are loaded into the backend through the asset API will be available for use within a site.

## File paths and url mapping

The hash, name, and filename are combined in order to build the physical location on disk.

For instance, this is a typical disk content:

```
assets/
    Uploads/
        b63923d8d4/
            BannerHeader.jpg
            BannerHeader__FitWzYwLDYwXQ.jpg
```

This corresponds to a file with the following properties:

- Filename: Uploads/BannerHeader.jpg
- Hash: b63923d8d4089c9da16fbcbcdfef3e1b24806334 (trimmed to first 10 chars)
- Variant: FitWzYwLDYwXQ (corresponds to Fit[60,60])

The URL for this file will match the physical location on disk:
`http://www.mysite.com/assets/Uploads/b63923d8d4/BannerHeader__FitWzYwLDYwXQ.jpg`.

For more information on how protected files are stored see the [file security](/developer_guides/files/file_security)
section.

## Loading content into `DBFile`

A file can be written to the backend from a file which exists on the local filesystem (but not necessarily
within the assets folder).

For example, to load a temporary file into a DataObject you could use the below:

```php
use SilverStripe\ORM\DataObject;

class Banner extends DataObject 
{
    private static $db = [
        'Image' => 'DBFile'
    ];
}

// Image could be assigned in other parts of the code using the below
$banner = new Banner();
$banner->Image->setFromLocalFile($tempfile['path'], 'uploads/banner-file.jpg');
```
