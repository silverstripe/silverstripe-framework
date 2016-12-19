<?php

namespace SilverStripe\Admin;

use SilverStripe\Control\Controller;
use SilverStripe\Core\Convert;
use SilverStripe\Core\Object;
use SilverStripe\ORM\SS_List;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\Versioning\Versioned;

/**
 * A class representing back actions.
 * See CMSMain.BatchActions.js on how to add custom javascript
 * functionality.
 *
 * <code>
 * CMSMain::register_batch_action('publishitems', new CMSBatchAction('doPublish',
 *  _t('CMSBatchActions.PUBLISHED_PAGES', 'published %d pages')));
 * </code>
 */
abstract class CMSBatchAction extends Object
{

    protected $managedClass = 'SilverStripe\\CMS\\Model\\SiteTree';

    /**
     * The the text to show in the dropdown for this action
     */
    abstract public function getActionTitle();

    /**
     * Run this action for the given set of pages.
     * Return a set of status-updated JavaScript to return to the CMS.
     *
     * @param SS_List $objs
     * @return string
     */
    abstract public function run(SS_List $objs);

    /**
     * Helper method for responding to a back action request
     * @param string $successMessage The message to return as a notification.
     * Can have up to two %d's in it. The first will be replaced by the number of successful
     * changes, the second by the number of failures
     * @param array $status A status array like batchactions builds. Should be
     * key => value pairs, the key can be any string: "error" indicates errors, anything
     * else indicates a type of success. The value is an array. We don't care what's in it,
     * we just use count($value) to find the number of items that succeeded or failed
     * @return string
     */
    public function response($successMessage, $status)
    {
        $count = 0;
        $errors = 0;

        foreach ($status as $k => $v) {
            switch ($k) {
                case 'error':
                    $errors += count($v);
                    break;
                case 'success':
                    $count += count($v);
                    break;
            }
        }

        $response = Controller::curr()->getResponse();

        if ($response) {
            $response->setStatusCode(
                200,
                sprintf($successMessage, $count, $errors)
            );
        }

        return Convert::raw2json($status);
    }

    /**
     * Helper method for processing batch actions.
     * Returns a set of status-updating JavaScript to return to the CMS.
     *
     * @param SS_List $objs The SS_List of objects to perform this batch action
     * on.
     * @param string $helperMethod The method to call on each of those objects.
     * @param string $successMessage
     * @param array $arguments
     * @return string JSON encoded map in the following format:
     *  {
     *     'modified': {
     *       3: {'TreeTitle': 'Page3'},
     *       5: {'TreeTitle': 'Page5'}
     *     },
     *     'deleted': {
     *       // all deleted pages
     *     }
     *  }
     */
    public function batchaction(SS_List $objs, $helperMethod, $successMessage, $arguments = array())
    {
        $status = array('modified' => array(), 'error' => array(), 'deleted' => array(), 'success' => array());

        foreach ($objs as $obj) {
            // Perform the action
            $id = $obj->ID;
            if (!call_user_func_array(array($obj, $helperMethod), $arguments)) {
                $status['error'][$id] = $id;
            } else {
                $status['success'][$id] = $id;
            }

            // Now make sure the tree title is appropriately updated
            $publishedRecord = DataObject::get_by_id($this->managedClass, $id);
            if ($publishedRecord) {
                $status['modified'][$id] = array(
                    'TreeTitle' => $publishedRecord->TreeTitle,
                );
            } else {
                $status['deleted'][$id] = $id;
            }
            $obj->destroy();
            unset($obj);
        }

        return $this->response($successMessage, $status);
    }



    /**
     * Helper method for applicablePages() methods.  Acts as a skeleton implementation.
     *
     * @param array $ids The IDs passed to applicablePages
     * @param string $methodName The canXXX() method to call on each page to check if the action is applicable
     * @param bool $checkStagePages Set to true if you want to check stage pages
     * @param bool $checkLivePages Set to true if you want to check live pages (e.g, for deleted-from-draft)
     * @return array
     */
    public function applicablePagesHelper($ids, $methodName, $checkStagePages = true, $checkLivePages = true)
    {
        if (!is_array($ids)) {
            user_error("Bad \$ids passed to applicablePagesHelper()", E_USER_WARNING);
        }
        if (!is_string($methodName)) {
            user_error("Bad \$methodName passed to applicablePagesHelper()", E_USER_WARNING);
        }

        $applicableIDs = array();

        $managedClass = $this->managedClass;
        $draftPages = DataObject::get($managedClass)->byIDs($ids);

        // Filter out the live-only ids
        $onlyOnLive = array_fill_keys($ids, true);
        if ($checkStagePages) {
            foreach ($draftPages as $obj) {
                unset($onlyOnLive[$obj->ID]);
                if ($obj->$methodName()) {
                    $applicableIDs[] = $obj->ID;
                }
            }
        }
        $onlyOnLive = array_keys($onlyOnLive);

        if ($checkLivePages && $onlyOnLive && Object::has_extension($managedClass, 'SilverStripe\\ORM\\Versioning\\Versioned')) {
            // Get the pages that only exist on live (deleted from stage)
            $livePages = Versioned::get_by_stage($managedClass, "Live")->byIDs($onlyOnLive);
            foreach ($livePages as $obj) {
                if ($obj->$methodName()) {
                    $applicableIDs[] = $obj->ID;
                }
            }
        }

        return $applicableIDs;
    }


    // if your batchaction has parameters, return a FieldList here
    public function getParameterFields()
    {
        return false;
    }

    /**
     * If you wish to restrict the batch action to some users, overload this function.
     */
    public function canView()
    {
        return true;
    }

    /**
     * Given a list of object IDs, filter out which items can have this batch action applied
     * to them.
     *
     * @param array $ids List of object ids
     * @return array Filtered list of $ids
     */
    public function applicablePages($ids)
    {
        return $ids;
    }
}
