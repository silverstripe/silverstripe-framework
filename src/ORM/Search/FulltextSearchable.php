<?php

namespace SilverStripe\ORM\Search;

use SilverStripe\Assets\File;
use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Core\Config\Config;
use SilverStripe\CMS\Controllers\ContentController;
use SilverStripe\ORM\Connect\MySQLSchemaManager;
use SilverStripe\ORM\DataExtension;
use Exception;

/**
 * Provides a simple search engine for your site based on the MySQL FULLTEXT index.
 * Adds the {@link FulltextSearchable} extension to data classes,
 * as well as the {@link ContentControllerSearchExtension} to {@link ContentController}
 * (if the 'cms' module is available as well).
 * (this means you can use $SearchForm in your template without changing your own implementation).
 *
 * CAUTION: Will make all files in your /assets folder searchable by file name
 * unless "File" is excluded from FulltextSearchable::enable().
 *
 * @template T of SiteTree|File
 * @extends DataExtension<T>
 */
class FulltextSearchable extends DataExtension
{

    /**
     * Comma-separated list of database column names
     * that can be searched on. Used for generation of the database index definitions.
     *
     * @var string
     */
    protected $searchFields;

    /**
     * @var array List of class names
     */
    protected static $searchable_classes;

    /**
     * Enable the default configuration of MySQL full-text searching on the given data classes.
     * It can be used to limit the searched classes, but not to add your own classes.
     * For this purpose, please use {@link Object::add_extension()} directly:
     * <code>
     * MyObject::add_extension("FulltextSearchable('MySearchableField,MyOtherField')");
     * </code>
     *
     * Caution: This is a wrapper method that should only be used in _config.php,
     * and only be called once in your code.
     *
     * @param array $searchableClasses The extension will be applied to all DataObject subclasses
     *  listed here. Default: {@link SiteTree} and {@link File}.
     * @throws Exception
     */
    public static function enable($searchableClasses = [SiteTree::class, File::class])
    {
        $defaultColumns = [
            SiteTree::class => ['Title','MenuTitle','Content','MetaDescription'],
            File::class => ['Name','Title'],
        ];

        if (!is_array($searchableClasses)) {
            $searchableClasses = [$searchableClasses];
        }
        foreach ($searchableClasses as $class) {
            if (!class_exists($class ?? '')) {
                continue;
            }

            if (isset($defaultColumns[$class])) {
                $class::add_extension(sprintf('%s(%s)', static::class, "'" . implode("','", $defaultColumns[$class]) . "''"));
            } else {
                throw new Exception(
                    "FulltextSearchable::enable() I don't know the default search columns for class '$class'"
                );
            }
        }
        FulltextSearchable::$searchable_classes = $searchableClasses;
        if (class_exists("SilverStripe\\CMS\\Controllers\\ContentController")) {
            ContentController::add_extension("SilverStripe\\CMS\\Search\\ContentControllerSearchExtension");
        }
    }

    /**
     * @param array|string $searchFields Comma-separated list (or array) of database column names
     *  that can be searched on. Used for generation of the database index definitions.
     */
    public function __construct($searchFields = [])
    {
        parent::__construct();
        if (is_array($searchFields)) {
            $this->searchFields = $searchFields;
        } else {
            $this->searchFields = explode(',', $searchFields ?? '');
            foreach ($this->searchFields as &$field) {
                $field = trim($field ?? '');
            }
        }
    }

    public static function get_extra_config($class, $extensionClass, $args)
    {
        return [
            'indexes' => [
                'SearchFields' => [
                    'type' => 'fulltext',
                    'name' => 'SearchFields',
                    'columns' => $args,
                ]
            ]
        ];
    }

    /**
     * Shows all classes that had the {@link FulltextSearchable} extension applied through {@link enable()}.
     *
     * @return array
     */
    public static function get_searchable_classes()
    {
        return FulltextSearchable::$searchable_classes;
    }
}
