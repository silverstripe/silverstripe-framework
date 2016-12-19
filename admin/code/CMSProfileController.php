<?php

namespace SilverStripe\Admin;

use SilverStripe\Control\HTTPResponse;
use SilverStripe\Forms\HiddenField;
use SilverStripe\Forms\FormAction;
use SilverStripe\ORM\ArrayList;
use SilverStripe\Security\Member;
use SilverStripe\Security\Permission;

class CMSProfileController extends LeftAndMain
{

    private static $url_segment = 'myprofile';

    private static $menu_title = 'My Profile';

    private static $required_permission_codes = false;

    private static $tree_class = 'SilverStripe\\Security\\Member';

    public function getEditForm($id = null, $fields = null)
    {
        $this->setCurrentPageID(Member::currentUserID());

        $form = parent::getEditForm($id, $fields);

        if ($form instanceof HTTPResponse) {
            return $form;
        }

        $form->Fields()->removeByName('LastVisited');
        $form->Fields()->push(new HiddenField('ID', null, Member::currentUserID()));
        $form->Actions()->push(
            FormAction::create('save', _t('CMSMain.SAVE', 'Save'))
                ->addExtraClass('ss-ui-button ss-ui-action-constructive')
                ->setAttribute('data-icon', 'accept')
                ->setUseButtonTag(true)
        );

        $form->Actions()->removeByName('action_delete');

        if ($member = Member::currentUser()) {
            $form->setValidator($member->getValidator());
        } else {
            $form->setValidator(Member::singleton()->getValidator());
        }

        if ($form->Fields()->hasTabSet()) {
            $form->Fields()->findOrMakeTab('Root')->setTemplate('SilverStripe\\Forms\\CMSTabSet');
        }

        $form->addExtraClass('member-profile-form root-form cms-edit-form center fill-height');

        return $form;
    }

    public function canView($member = null)
    {
        if (!$member && $member !== false) {
            $member = Member::currentUser();
        }

        // cms menus only for logged-in members
        if (!$member) {
            return false;
        }

        // Check they can access the CMS and that they are trying to edit themselves
        if (Permission::checkMember($member, "CMS_ACCESS")
            && $member->ID === Member::currentUserID()
        ) {
            return true;
        }

        return false;
    }

    public function save($data, $form)
    {
        $member = Member::get()->byID($data['ID']);
        if (!$member) {
            return $this->httpError(404);
        }
        $origLocale = $member->Locale;

        if (!$member->canEdit()) {
            $form->sessionMessage(_t('Member.CANTEDIT', 'You don\'t have permission to do that'), 'bad');
            return $this->redirectBack();
        }

        $response = parent::save($data, $form);

        if ($origLocale != $data['Locale']) {
            $response->addHeader('X-Reload', true);
            $response->addHeader('X-ControllerURL', $this->Link());
        }

        return $response;
    }

    /**
     * Only show first element, as the profile form is limited to editing
     * the current member it doesn't make much sense to show the member name
     * in the breadcrumbs.
     *
     * @param bool $unlinked
     * @return ArrayList
     */
    public function Breadcrumbs($unlinked = false)
    {
        $items = parent::Breadcrumbs($unlinked);
        return new ArrayList(array($items[0]));
    }
}
