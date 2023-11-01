<?php

namespace SilverStripe\Dev;

use SilverStripe\Core\Injector\Injectable;
use SilverStripe\ORM\ArrayList;
use SilverStripe\ORM\DataObject;
use SilverStripe\View\ArrayData;

/**
 * Encapsulates the result of a {@link BulkLoader} import
 * (usually through the {@link BulkLoader->processAll()} method).
 *
 * @author Ingo Schommer, Silverstripe Ltd. (<firstname>@silverstripe.com)
 */
class BulkLoader_Result implements \Countable
{
    use Injectable;

    /**
     * Stores a map of ID and ClassNames
     * which can be reconstructed to DataObjects.
     * As imports can get large we just store enough
     * information to reconstruct the objects on demand.
     * Optionally includes a status message specific to
     * the import of this object. This information is stored
     * in a custom object property "_BulkLoaderMessage".
     *
     * Example:
     * <code>
     * [['ID'=>1, 'ClassName'=>'Member', 'Message'=>'Updated existing record based on ParentID relation']]
     * </code>
     *
     * @var array
     */
    protected $created = [];

    /**
     * see {@link $created}
     *
     * @var array
     */
    protected $updated = [];

    /**
     * @var array (see {@link $created})
     */
    protected $deleted = [];

    /**
     * Stores the last change.
     * It is in the same format as {@link $created} but with an additional key, "ChangeType", which will be set to
     * one of 3 strings: "created", "updated", or "deleted"
     */
    protected $lastChange = [];

    /**
     * Returns the count of all objects which were
     * created or updated.
     */
    public function Count(): int
    {
        return count($this->created ?? []) + count($this->updated ?? []);
    }

    /**
     * @return int
     */
    public function CreatedCount()
    {
        return count($this->created ?? []);
    }

    /**
     * @return int
     */
    public function UpdatedCount()
    {
        return count($this->updated ?? []);
    }

    /**
     * @return int
     */
    public function DeletedCount()
    {
        return count($this->deleted ?? []);
    }

    /**
     * Returns all created objects. Each object might
     * contain specific importer feedback in the "_BulkLoaderMessage" property.
     *
     * @return ArrayList
     */
    public function Created()
    {
        return $this->mapToArrayList($this->created);
    }

    /**
     * @return ArrayList
     */
    public function Updated()
    {
        return $this->mapToArrayList($this->updated);
    }

    /**
     * @return ArrayList
     */
    public function Deleted()
    {
        $set = new ArrayList();
        foreach ($this->deleted as $arrItem) {
            $set->push(ArrayData::create($arrItem));
        }
        return $set;
    }

    /**
     * Returns the last change.
     * It is in the same format as {@link $created} but with an additional key, "ChangeType", which will be set to
     * one of 3 strings: "created", "updated", or "deleted"
     */
    public function LastChange()
    {
        return $this->lastChange;
    }

    /**
     * @param $obj DataObject
     * @param $message string
     */
    public function addCreated($obj, $message = null)
    {
        $this->created[] = $this->lastChange = [
            'ID' => $obj->ID,
            'ClassName' => get_class($obj),
            'Message' => $message
        ];
        $this->lastChange['ChangeType'] = 'created';
    }

    /**
     * @param $obj DataObject
     * @param $message string
     */
    public function addUpdated($obj, $message = null)
    {
        $this->updated[] = $this->lastChange = [
            'ID' => $obj->ID,
            'ClassName' => get_class($obj),
            'Message' => $message
        ];
        $this->lastChange['ChangeType'] = 'updated';
    }

    /**
     * @param $obj DataObject
     * @param $message string
     */
    public function addDeleted($obj, $message = null)
    {
        $data = $obj->toMap();
        $data['_BulkLoaderMessage'] = $message;
        $this->deleted[] = $this->lastChange = $data;
        $this->lastChange['ChangeType'] = 'deleted';
    }

    /**
     * @param array $arr containing ID and ClassName maps
     * @return ArrayList
     */
    protected function mapToArrayList($arr)
    {
        $set = new ArrayList();
        foreach ($arr as $arrItem) {
            $obj = DataObject::get_by_id($arrItem['ClassName'], $arrItem['ID']);
            $obj->_BulkLoaderMessage = $arrItem['Message'];
            if ($obj) {
                $set->push($obj);
            }
        }

        return $set;
    }

    /**
     * Merges another BulkLoader_Result into this one.
     *
     * @param BulkLoader_Result $other
     */
    public function merge(BulkLoader_Result $other)
    {
        $this->created = array_merge($this->created, $other->created);
        $this->updated = array_merge($this->updated, $other->updated);
        $this->deleted = array_merge($this->deleted, $other->deleted);
    }
}
