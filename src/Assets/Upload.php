<?php

namespace SilverStripe\Assets;

use SilverStripe\Assets\Storage\AssetContainer;
use SilverStripe\Assets\Storage\AssetNameGenerator;
use SilverStripe\Assets\Storage\AssetStore;
use SilverStripe\Control\Controller;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Core\Object;
use SilverStripe\ORM\DataObject;
use SilverStripe\Security\Member;
use InvalidArgumentException;
use Exception;

/**
 * Manages uploads via HTML forms processed by PHP,
 * uploads to Silverstripe's default upload directory,
 * and either creates a new or uses an existing File-object
 * for syncing with the database.
 *
 * <b>Validation</b>
 *
 * By default, a user can upload files without extension limitations,
 * which can be a security risk if the webserver is not properly secured.
 * Use {@link setAllowedExtensions()} to limit this list,
 * and ensure the "assets/" directory does not execute scripts
 * (see http://doc.silverstripe.org/secure-development#filesystem).
 * {@link File::$allowed_extensions} provides a good start for a list of "safe" extensions.
 *
 * @todo Allow for non-database uploads
 */
class Upload extends Controller
{

    private static $allowed_actions = array(
        'index',
        'load'
    );

    /**
     * A dataobject (typically {@see File}) which implements {@see AssetContainer}
     *
     * @var AssetContainer
     */
    protected $file;

    /**
     * Validator for this upload field
     *
     * @var Upload_Validator
     */
    protected $validator;

    /**
     * Information about the temporary file produced
     * by the PHP-runtime.
     *
     * @var array
     */
    protected $tmpFile;

    /**
     * Replace an existing file rather than renaming the new one.
     *
     * @var boolean
     */
    protected $replaceFile = false;

    /**
     * Processing errors that can be evaluated,
     * e.g. by Form-validation.
     *
     * @var array
     */
    protected $errors = array();

    /**
     * Default visibility to assign uploaded files
     *
     * @var string
     */
    protected $defaultVisibility = AssetStore::VISIBILITY_PROTECTED;

    /**
     * A foldername relative to /assets,
     * where all uploaded files are stored by default.
     *
     * @config
     * @var string
     */
    private static $uploads_folder = "Uploads";

    /**
     * A prefix for the version number added to an uploaded file
     * when a file with the same name already exists.
     * Example using no prefix: IMG001.jpg becomes IMG2.jpg
     * Example using '-v' prefix: IMG001.jpg becomes IMG001-v2.jpg
     *
     * @config
     * @var string
     */
    private static $version_prefix = '-v';

    public function __construct()
    {
        parent::__construct();
        $this->validator = Injector::inst()->create('SilverStripe\\Assets\\Upload_Validator');
        $this->replaceFile = self::config()->replaceFile;
    }

    public function index()
    {
        return $this->httpError(404); // no-op
    }

    /**
     * Get current validator
     *
     * @return Upload_Validator $validator
     */
    public function getValidator()
    {
        return $this->validator;
    }

    /**
     * Set a different instance than {@link Upload_Validator}
     * for this upload session.
     *
     * @param object $validator
     */
    public function setValidator($validator)
    {
        $this->validator = $validator;
    }


    /**
     * Get an asset renamer for the given filename.
     *
     * @param string $filename Path name
     * @return AssetNameGenerator
     */
    protected function getNameGenerator($filename)
    {
        return Injector::inst()->createWithArgs('AssetNameGenerator', array($filename));
    }

    /**
     *
     * @return AssetStore
     */
    protected function getAssetStore()
    {
        return Injector::inst()->get('AssetStore');
    }

    /**
     * Save an file passed from a form post into the AssetStore directly
     *
     * @param array $tmpFile Indexed array that PHP generated for every file it uploads.
     * @param string|bool $folderPath Folder path relative to /assets
     * @return array|false Either the tuple array, or false if the file could not be saved
     */
    public function load($tmpFile, $folderPath = false)
    {
        // Validate filename
        $filename = $this->getValidFilename($tmpFile, $folderPath);
        if (!$filename) {
            return false;
        }

        // Save file into backend
        $result = $this->storeTempFile($tmpFile, $filename, $this->getAssetStore());

        //to allow extensions to e.g. create a version after an upload
        $this->extend('onAfterLoad', $result, $tmpFile);
        return $result;
    }

    /**
     * Save an file passed from a form post into this object.
     * File names are filtered through {@link FileNameFilter}, see class documentation
     * on how to influence this behaviour.
     *
     * @param array $tmpFile
     * @param AssetContainer $file
     * @param string|bool $folderPath
     * @return bool True if the file was successfully saved into this record
     * @throws Exception
     */
    public function loadIntoFile($tmpFile, $file = null, $folderPath = false)
    {
        $this->file = $file;

        // Validate filename
        $filename = $this->getValidFilename($tmpFile, $folderPath);
        if (!$filename) {
            return false;
        }
        $filename = $this->resolveExistingFile($filename);

        // Save changes to underlying record (if it's a DataObject)
        $this->storeTempFile($tmpFile, $filename, $this->file);
        if ($this->file instanceof DataObject) {
            $this->file->write();
        }

        //to allow extensions to e.g. create a version after an upload
        $this->file->extend('onAfterUpload');
        $this->extend('onAfterLoadIntoFile', $this->file);
        return true;
    }

    /**
     * Assign this temporary file into the given destination
     *
     * @param array $tmpFile
     * @param string $filename
     * @param AssetContainer|AssetStore $container
     * @return array
     */
    protected function storeTempFile($tmpFile, $filename, $container)
    {
        // Save file into backend
        $conflictResolution = $this->replaceFile
            ? AssetStore::CONFLICT_OVERWRITE
            : AssetStore::CONFLICT_RENAME;
        $config = array(
            'conflict' => $conflictResolution,
            'visibility' => $this->getDefaultVisibility()
        );
        return $container->setFromLocalFile($tmpFile['tmp_name'], $filename, null, null, $config);
    }

    /**
     * Given a temporary file and upload path, validate the file and determine the
     * value of the 'Filename' tuple that should be used to store this asset.
     *
     * @param array $tmpFile
     * @param string $folderPath
     * @return string|false Value of filename tuple, or false if invalid
     */
    protected function getValidFilename($tmpFile, $folderPath = null)
    {
        if (!is_array($tmpFile)) {
            throw new InvalidArgumentException(
                "Upload::load() Not passed an array.  Most likely, the form hasn't got the right enctype"
            );
        }

        // Validate
        $this->clearErrors();
        $valid = $this->validate($tmpFile);
        if (!$valid) {
            return false;
        }

        // Clean filename
        if (!$folderPath) {
            $folderPath = $this->config()->uploads_folder;
        }
        $nameFilter = FileNameFilter::create();
        $file = $nameFilter->filter($tmpFile['name']);
        $filename = basename($file);
        if ($folderPath) {
            $filename = File::join_paths($folderPath, $filename);
        }
        return $filename;
    }

    /**
     * Given a file and filename, ensure that file renaming / replacing rules are satisfied
     *
     * If replacing, this method may replace $this->file with an existing record to overwrite.
     * If renaming, a new value for $filename may be returned
     *
     * @param string $filename
     * @return string $filename A filename safe to write to
     * @throws Exception
     */
    protected function resolveExistingFile($filename)
    {
        // Create a new file record (or try to retrieve an existing one)
        if (!$this->file) {
            $fileClass = File::get_class_for_file_extension(
                File::get_file_extension($filename)
            );
            $this->file = Object::create($fileClass);
        }

        // Skip this step if not writing File dataobjects
        if (! ($this->file instanceof File)) {
            return $filename;
        }

        // Check there is if existing file
        $existing = File::find($filename);

        // If replacing (or no file exists) confirm this filename is safe
        if ($this->replaceFile || !$existing) {
            // If replacing files, make sure to update the OwnerID
            if (!$this->file->ID && $this->replaceFile && $existing) {
                $this->file = $existing;
                $this->file->OwnerID = Member::currentUserID();
            }
            // Filename won't change if replacing
            return $filename;
        }

        // if filename already exists, version the filename (e.g. test.gif to test-v2.gif, test-v2.gif to test-v3.gif)
        $renamer = $this->getNameGenerator($filename);
        foreach ($renamer as $newName) {
            if (!File::find($newName)) {
                return $newName;
            }
        }

        // Fail
        $tries = $renamer->getMaxTries();
        throw new Exception("Could not rename {$filename} with {$tries} tries");
    }

    /**
     * @param bool $replace
     */
    public function setReplaceFile($replace)
    {
        $this->replaceFile = $replace;
    }

    /**
     * @return bool
     */
    public function getReplaceFile()
    {
        return $this->replaceFile;
    }

    /**
     * Container for all validation on the file
     * (e.g. size and extension restrictions).
     * Is NOT connected to the {Validator} classes,
     * please have a look at {FileField->validate()}
     * for an example implementation of external validation.
     *
     * @param array $tmpFile
     * @return boolean
     */
    public function validate($tmpFile)
    {
        $validator = $this->validator;
        $validator->setTmpFile($tmpFile);
        $isValid = $validator->validate();
        if ($validator->getErrors()) {
            $this->errors = array_merge($this->errors, $validator->getErrors());
        }
        return $isValid;
    }

    /**
     * Get file-object, either generated from {load()},
     * or manually set.
     *
     * @return AssetContainer
     */
    public function getFile()
    {
        return $this->file;
    }

    /**
     * Set a file-object (similiar to {loadIntoFile()})
     *
     * @param AssetContainer $file
     */
    public function setFile(AssetContainer $file)
    {
        $this->file = $file;
    }

    /**
     * Clear out all errors (mostly set by {loadUploaded()})
     * including the validator's errors
     */
    public function clearErrors()
    {
        $this->errors = array();
        $this->validator->clearErrors();
    }

    /**
     * Determines wether previous operations caused an error.
     *
     * @return boolean
     */
    public function isError()
    {
        return (count($this->errors));
    }

    /**
     * Return all errors that occurred while processing so far
     * (mostly set by {loadUploaded()})
     *
     * @return array
     */
    public function getErrors()
    {
        return $this->errors;
    }

    /**
     * Get default visibility for uploaded files. {@see AssetStore}
     * One of the values of AssetStore::VISIBILITY_* constants
     *
     * @return string
     */
    public function getDefaultVisibility()
    {
        return $this->defaultVisibility;
    }

    /**
     * Assign default visibility for uploaded files. {@see AssetStore}
     * One of the values of AssetStore::VISIBILITY_* constants
     *
     * @param string $visibility
     * @return $this
     */
    public function setDefaultVisibility($visibility)
    {
        $this->defaultVisibility = $visibility;
        return $this;
    }
}
