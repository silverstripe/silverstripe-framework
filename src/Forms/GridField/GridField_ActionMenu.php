<?php

namespace SilverStripe\Forms\GridField;

use SilverStripe\Core\Convert;
use SilverStripe\Forms\GridField\GridField_ActionMenuItem;
use SilverStripe\ORM\FieldType\DBString;
use SilverStripe\View\ArrayData;
use SilverStripe\View\SSViewer;

/**
 * Groups exiting actions in the Actions column in to a menu
 */
class GridField_ActionMenu implements GridField_ColumnProvider, GridField_ActionProvider
{
    public function augmentColumns($gridField, &$columns)
    {
        if (!in_array('Actions', $columns)) {
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
        $schema = array_filter(array_map(function (GridField_ActionMenuItem $item) use ($gridField, $record) {
            $type = $item->getType($gridField, $record);
            if (!$type) {
                return null;
            }

            return [
                'type' => $type,
                'title' => $item->getTitle($gridField, $record),
                'url' => $item->getUrl($gridField, $record),
                'group' => $item->getGroup($gridField, $record),
                'data' => $item->getExtraData($gridField, $record),
            ];
        }, $items));
        $itemContents = array_filter(array_map(function ($item) use ($gridField, $record, $columnName) {
            if (!$item instanceof GridField_ColumnProvider) {
                return null;
            }
            $content = $item->getColumnContent($gridField, $record, $columnName);
            if ($content instanceof DBField) {
                return $content->getValue();
            }
            return $content;
        }, $items));

        $templateData = ArrayData::create([
            'Schema' => Convert::raw2json($schema),
            'Items' => implode('', $itemContents),
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

            if (in_array($actionName, $actions)) {
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
