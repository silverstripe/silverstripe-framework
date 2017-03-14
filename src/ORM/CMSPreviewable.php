<?php

namespace SilverStripe\ORM;

/**
 * Interface to provide enough information about a record to make it previewable
 * through the CMS. It uses the record database ID, its "frontend" and "backend"
 * links to link up the edit form with its preview.
 *
 * Also used by {@link SilverStripeNavigator} to generate links -  both within
 * the CMS preview, and as a frontend utility for logged-in CMS authors in
 * custom themes (with the $SilverStripeNavigator template marker).
 */
interface CMSPreviewable
{

    /**
     * Determine the preview link, if available, for this object.
     * If no preview is available for this record, it may return null.
     *
     * @param string $action
     * @return string Link to the end-user view for this record.
     * Example: http://mysite.com/my-record
     */
    public function PreviewLink($action = null);

    /**
     * To determine preview mechanism (e.g. embedded / iframe)
     *
     * @return string
     */
    public function getMimeType();

    /**
     * @return string Link to the CMS-author view. Should point to a
     * controller subclassing {@link LeftAndMain}. Example:
     * http://mysite.com/admin/edit/6
     */
    public function CMSEditLink();
}
