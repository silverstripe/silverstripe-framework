<?php

namespace SilverStripe\Security;

use SilverStripe\Forms\GridField\GridFieldDetailForm_ItemRequest;
use SilverStripe\Forms\RequiredFields;

/**
 * Member Validator
 *
 * Custom validation for the Member object can be achieved either through an
 * {@link DataExtension} on the Member_Validator object or, by specifying a subclass of
 * {@link Member_Validator} through the {@link Injector} API.
 * The Validator can also be modified by adding an Extension to Member and implement the
 * <code>updateValidator</code> hook.
 * {@see Member::getValidator()}
 *
 * Additional required fields can also be set via config API, eg.
 * <code>
 * Member_Validator:
 *   customRequired:
 *     - Surname
 * </code>
 */
class Member_Validator extends RequiredFields
{
    /**
     * Fields that are required by this validator
     * @config
     * @var array
     */
    protected $customRequired = [
        'FirstName',
        'Email'
    ];

    /**
     * Determine what member this validator is meant for
     * @var Member
     */
    protected $forMember = null;

    /**
     * Constructor
     */
    public function __construct()
    {
        $required = func_get_args();

        if (isset($required[0]) && is_array($required[0])) {
            $required = $required[0];
        }

        $required = array_merge($required, $this->customRequired);

        // check for config API values and merge them in
        $config = $this->config()->customRequired;
        if (is_array($config)) {
            $required = array_merge($required, $config);
        }

        parent::__construct(array_unique($required ?? []));
    }

    /**
     * Get the member this validator applies to.
     * @return Member
     */
    public function getForMember()
    {
        return $this->forMember;
    }

    /**
     * Set the Member this validator applies to.
     * @param Member $value
     * @return $this
     */
    public function setForMember(Member $value)
    {
        $this->forMember = $value;
        return $this;
    }

    /**
     * Check if the submitted member data is valid (server-side)
     *
     * Check if a member with that email doesn't already exist, or if it does
     * that it is this member.
     *
     * @param array $data Submitted data
     * @return bool Returns TRUE if the submitted data is valid, otherwise
     *              FALSE.
     */
    public function php($data)
    {
        $valid = parent::php($data);

        $identifierField = (string)Member::config()->unique_identifier_field;

        // Only validate identifier field if it's actually set. This could be the case if
        // somebody removes `Email` from the list of required fields.
        $id = isset($data['ID']) ? (int)$data['ID'] : 0;
        if (isset($data[$identifierField])) {
            if (!$id && ($ctrl = $this->form->getController())) {
                // get the record when within GridField (Member editing page in CMS)
                if ($ctrl instanceof GridFieldDetailForm_ItemRequest && $record = $ctrl->getRecord()) {
                    $id = $record->ID;
                }
            }

            // If there's no ID passed via controller or form-data, use the assigned member (if available)
            if (!$id && ($member = $this->getForMember())) {
                $id = $member->exists() ? $member->ID : 0;
            }

            // set the found ID to the data array, so that extensions can also use it
            $data['ID'] = $id;

            $members = Member::get()->filter($identifierField, $data[$identifierField]);
            if ($id) {
                $members = $members->exclude('ID', $id);
            }

            if ($members->count() > 0) {
                $this->validationError(
                    $identifierField,
                    _t(
                        'SilverStripe\\Security\\Member.VALIDATIONMEMBEREXISTS',
                        'A member already exists with the same {identifier}',
                        ['identifier' => Member::singleton()->fieldLabel($identifierField)]
                    ),
                    'required'
                );
                $valid = false;
            }
        }

        $currentUser = Security::getCurrentUser();
        if ($currentUser
            && $id
            && $id === (int)$currentUser->ID
            && Permission::checkMember($currentUser, 'ADMIN')
        ) {
            $stillAdmin = true;

            if (!isset($data['DirectGroups'])) {
                $stillAdmin = false;
            } else {
                $adminGroups = array_intersect(
                    $data['DirectGroups'] ?? [],
                    Permission::get_groups_by_permission('ADMIN')->column()
                );

                if (count($adminGroups ?? []) === 0) {
                    $stillAdmin = false;
                }
            }

            if (!$stillAdmin) {
                $this->validationError(
                    'DirectGroups',
                    _t(
                        'SilverStripe\\Security\\Member.VALIDATIONADMINLOSTACCESS',
                        'Cannot remove all admin groups from your profile'
                    ),
                    'required'
                );
            }
        }

        // Execute the validators on the extensions
        $results = $this->extend('updatePHP', $data, $this->form);
        $results[] = $valid;
        return min($results);
    }
}
