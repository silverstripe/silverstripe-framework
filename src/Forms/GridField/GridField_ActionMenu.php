<?php

namespace SilverStripe\Forms\GridField;

use SilverStripe\View\ArrayData;
use SilverStripe\View\SSViewer;

/**
 * Groups exiting actions in the Actions column in to a menu
 */
class GridField_ActionMenu extends AbstractGridFieldComponent implements GridField_ColumnProvider, GridField_ActionProvider
{
    public function augmentColumns($gridField, &$columns)
    {
        if (!in_array('Actions', $columns ?? [])) {
            $columns[] = 'Actions';
        }
    }

    public function getColumnsHandled($gridField)
    {
        return ['Actions'];
    }

    public function getColumnContent($gridField, $record, $columnName)
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

    public function getColumnAttributes($gridField, $record, $columnName)
    {
        return ['class' => 'grid-field__col-compact action-menu'];
    }

    public function getColumnMetadata($gridField, $columnName)
    {
        return ['title' => null];
    }

    public function getActions($gridField)
    {
        $actions = [];

        foreach ($this->getItems($gridField) as $item) {
            if ($item instanceof GridField_ActionProvider) {
                $actions = array_merge($actions, $item->getActions($gridField));
            }
        }

        return $actions;
    }

    public function handleAction(GridField $gridField, $actionName, $arguments, $data)
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
    public function getItems($gridfield)
    {
        $items = $gridfield->config->getComponentsByType(GridField_ActionMenuItem::class)->items;

        return $items;
    }
}
