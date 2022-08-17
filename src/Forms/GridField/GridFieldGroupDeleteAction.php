<?php

namespace SilverStripe\Forms\GridField;

use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\ValidationException;
use SilverStripe\Security\Member;
use SilverStripe\Security\Permission;
use SilverStripe\Security\Security;

/**
 * Adds a delete action for the gridfield to remove a relationship from group.
 * This is a special case where it captures whether the current user is the record being removed and
 * prevents removal from happening.
 */
class GridFieldGroupDeleteAction extends GridFieldDeleteAction
{
    /**
     * @var int
     */
    protected $groupID;

    public function __construct(int $groupID): void
    {
        $this->groupID = $groupID;
        parent::__construct(true);
    }

    /**
     *
     * @param GridField $gridField
     * @param DataObject $record
     * @param string $columnName
     * @return string the HTML for the column
     */
    public function getColumnContent(SilverStripe\Forms\GridField\GridField $gridField, SilverStripe\Security\Member $record, string $columnName): null|SilverStripe\ORM\FieldType\DBHTMLText
    {
        if ($this->canUnlink($record)) {
            return parent::getColumnContent($gridField, $record, $columnName);
        }
        return null;
    }

    /**
     * Get the ActionMenu group (not related to Member group)
     * @param GridField $gridField
     * @param DataObject $record
     * @param $columnName
     * @return null|string
     */
    public function getGroup(SilverStripe\Forms\GridField\GridField $gridField, SilverStripe\Security\Member $record, string $columnName): null|string
    {
        if (!$this->canUnlink($record)) {
            return null;
        }

        return parent::getGroup($gridField, $record, $columnName);
    }

    /**
     * Handle the actions and apply any changes to the GridField
     *
     * @param GridField $gridField
     * @param string $actionName
     * @param array $arguments
     * @param array $data Form data
     * @throws ValidationException
     */
    public function handleAction(GridField $gridField, string $actionName, array $arguments, array $data): void
    {
        $record = $gridField->getList()->find('ID', $arguments['RecordID']);

        if (!$record || !$actionName == 'unlinkrelation' || $this->canUnlink($record)) {
            parent::handleAction($gridField, $actionName, $arguments, $data);
            return;
        }

        throw new ValidationException(
            _t(__CLASS__ . '.UnlinkSelfFailure', 'Cannot remove yourself from this group, you will lose admin rights')
        );
    }

    /**
     * @param $record - the record of the User to unlink with
     * @return bool
     */
    protected function canUnlink(SilverStripe\Security\Member $record): bool
    {
        $currentUser = Security::getCurrentUser();
        if ($currentUser
            && $record instanceof Member
            && (int)$record->ID === (int)$currentUser->ID
            && Permission::checkMember($record, 'ADMIN')
        ) {
            $adminGroups = array_intersect(
                $record->Groups()->column() ?? [],
                Permission::get_groups_by_permission('ADMIN')->column()
            );

            if (count($adminGroups ?? []) === 1 && array_search($this->groupID, $adminGroups ?? []) !== false) {
                return false;
            }
        }
        return true;
    }
}
