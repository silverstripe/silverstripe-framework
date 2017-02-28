<?php


namespace SilverStripe\Admin;

use SilverStripe\Forms\FormField;
use SilverStripe\ORM\Versioning\ChangeSet;

/**
 * Warning: Volatile API as placeholder for standard "GridField"
 */
class CampaignAdminList extends FormField
{
    protected $schemaDataType = FormField::SCHEMA_DATA_TYPE_CUSTOM;

    protected $schemaComponent = 'GridField';

    public function getSchemaDataDefaults()
    {
        $data = parent::getSchemaDataDefaults();

        // Get endpoints from admin
        $admin = CampaignAdmin::singleton();
        $data['data']['recordType'] = $admin->config()->uninherited('tree_class');
        $oneSetAction = $admin->Link("set") . "/:id";
        $setsAction = $admin->Link("sets");
        $schemaEndpoint = $admin->Link("schema") . "/DetailEditForm";

        // Merge custom endpoints
        $data['data']['collectionReadEndpoint'] = [
            "url" => $setsAction,
            "method" => "GET",
        ];
        $data['data']['itemReadEndpoint'] = [
            "url" => $oneSetAction,
            "method" => "GET",
        ];
        $data['data']['itemUpdateEndpoint'] = [
            "url" => $oneSetAction,
            "method" => "PUT"
        ];
        $data['data']['itemCreateEndpoint'] = [
            "url" => $oneSetAction,
            "method" => "POST"
        ];
        $data['data']["itemDeleteEndpoint"] = [
            "url" => $oneSetAction,
            "method" => "DELETE"
        ];
        $data['data']['editFormSchemaEndpoint'] =  $schemaEndpoint;

        // Set summary columns
        $columns = [];
        foreach (ChangeSet::singleton()->summaryFields() as $field => $label) {
            $columns[] = [
                'field' => $field,
                'name' => $label,
            ];
        }
        $data['data']['columns'] = $columns;

        // Return
        return $data;
    }
}
