<?php

namespace SilverStripe\Forms\GridField;

use SilverStripe\View\ArrayData;
use SilverStripe\View\SSViewer;

/**
 * Groups exiting actions in the Actions column in to a menu
 */
class GridField_ActionMenu extends AbstractGridFieldComponent implements GridField_ColumnProvider, GridField_ActionProvider
{
    public function augmentColumns(SilverStripe\Forms\GridField\GridField $gridField, &$columns): void
    {
        if (!in_array('Actions', $columns ?? [])) {
            $columns[] = 'Actions';
        }
    }

    public function getColumnsHandled(SilverStripe\Forms\GridField\GridField $gridField): array
    {
        return ['Actions'];
    }

    public function getColumnContent(SilverStripe\Forms\GridField\GridField $gridField, SilverStripe\Security\Member $record, string $columnName): SilverStripe\ORM\FieldType\DBHTMLText
    {
        $items = $this->getItems($gridField);

        if (!$items) {
            return null;
        }

        $schema = [];
        /* @var GridField_ActionMenuItem $item */
        foreach ($items as $item) {
            $group = $item->getGroup($gridField, $record, $columnName);
            if (!$group) {
                continue;
            }
            $schema[] = [
                'type' => $item instanceof GridField_ActionMenuLink ? 'link' : 'submit',
                'title' => $item->getTitle($gridField, $record, $columnName),
                'url' => $item instanceof GridField_ActionMenuLink ? $item->getUrl($gridField, $record, $columnName) : null,
                'group' => $group,
                'data' => $item->getExtraData($gridField, $record, $columnName),
            ];
        }

        $templateData = ArrayData::create([
            'Schema' => json_encode($schema),
        ]);

        $template = SSViewer::get_templates_by_class($this, '', static::class);

        return $templateData->renderWith($template);
    }

    public function getColumnAttributes(SilverStripe\Forms\GridField\GridField $gridField, SilverStripe\Security\Member $record, string $columnName): array
    {
        return ['class' => 'grid-field__col-compact action-menu'];
    }

    public function getColumnMetadata(SilverStripe\Forms\GridField\GridField $gridField, string $columnName): array
    {
        return ['title' => null];
    }

    public function getActions(SilverStripe\Forms\GridField\GridField $gridField): array
    {
        $actions = [];

        foreach ($this->getItems($gridField) as $item) {
            if ($item instanceof GridField_ActionProvider) {
                $actions = array_merge($actions, $item->getActions($gridField));
            }
        }

        return $actions;
    }

    public function handleAction(GridField $gridField, string $actionName, array $arguments, array $data): void
    {
        foreach ($this->getItems($gridField) as $item) {
            $actions = [];
            if ($item instanceof GridField_ActionProvider) {
                $actions = $item->getActions($gridField);
            }

            if (in_array($actionName, $actions ?? [])) {
                $item->handleAction($gridField, $actionName, $arguments, $data);
            }
        }
    }

    /**
     * Gets the list of items setup
     *
     * @return array
     */
    public function getItems(SilverStripe\Forms\GridField\GridField $gridfield): array
    {
        $items = $gridfield->config->getComponentsByType(GridField_ActionMenuItem::class)->items;

        return $items;
    }
}
