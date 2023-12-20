<?php

namespace SilverStripe\Forms\GridField;

use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridField_ColumnProvider;
use SilverStripe\ORM\DataObject;
use SilverStripe\Versioned\Versioned;
use SilverStripe\Core\Convert;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Versioned\RecursiveStagesInterface;
use SilverStripe\Versioned\VersionedGridFieldState\VersionedGridFieldState;
use SilverStripe\View\HTML;

class GridFieldVersionTag extends AbstractGridFieldComponent implements GridField_ColumnProvider
{
    protected ?string $column = null;

    protected array $versionedLabelFields = [];

    public function __construct($versionedLabelFields = ['Name', 'Title'])
    {
        $this->setVersionedLabelFields($versionedLabelFields);
    }

    public function getColumn(): ?string
    {
        return $this->column;
    }

    public function setColumn(string $column): static
    {
        $this->column = $column;
        return $this;
    }

    public function getVersionedLabelFields(): array
    {
        return $this->versionedLabelFields;
    }

    public function setVersionedLabelFields(array $versionedLabelFields): static
    {
        $this->versionedLabelFields = $versionedLabelFields;
        return $this;
    }

    /**
     * Modify the list of columns displayed in the table.
     *
     * @see {@link GridFieldDataColumns->getDisplayFields()}
     * @see {@link GridFieldDataColumns}.
     *
     * @param GridField $gridField
     * @param array $columns List reference of all column names.
     */
    public function augmentColumns($gridField, &$columns): void
    {
        // Skip if not versioned, or column already set
        if ($this->getColumn()) {
            return;
        }

        $matchedVersionedFields = array_intersect(
            $columns ?? [],
            $this->versionedLabelFields
        );

        if (count($matchedVersionedFields ?? []) > 0) {
            // Get first matched column
            $this->setColumn(reset($matchedVersionedFields));
        } elseif ($columns) {
            // Use first column if none of preferred matches
            $this->setColumn(reset($columns));
        }
    }

    /**
     * Names of all columns which are affected by this component.
     *
     * @param GridField $gridField
     * @return array
     */
    public function getColumnsHandled($gridField): array
    {
        return $this->getColumn() ? [$this->getColumn()] : [];
    }

    /**
     * HTML for the column, content of the <td> element.
     *
     * @param GridField $gridField
     * @param DataObject $record Record displayed in this row
     * @param string $columnName
     * @return string HTML for the column.
     */
    public function getColumnContent($gridField, $record, $columnName): string
    {
        $flagContent = '';
        $flags = $this->getStatusFlags($record);
        foreach ($flags as $class => $data) {
            $flagAttributes = [
                'class' => "ss-gridfield-badge badge status-{$class}",
            ];
            if (isset($data['title'])) {
                $flagAttributes['title'] = $data['title'];
            }
            $flagContent .= ' ' . HTML::createTag('span', $flagAttributes, Convert::raw2xml($data['text']));
        }
        return $flagContent;
    }

    /**
     * Attributes for the column
     */
    public function getColumnAttributes($gridField, $record, $columnName): array
    {
        return [];
    }

    /**
     * Metadata for the column
     */
    public function getColumnMetadata($gridField, $columnName): array
    {
        return [];
    }

    /**
     * Get status flags for a given record
     */
    private function getStatusFlags(DataObject $record): array
    {
        if ($record->hasExtension(Versioned::class)) {
            return [];
        }

        if ($this->stagesDifferRecursive($record)) {
            return [
                'modified' => [
                    'text' => _t(__CLASS__ . '.MODIFIEDONDRAFTSHORT', 'Modified'),
                    'title' => _t(__CLASS__ . '.MODIFIEDONDRAFTHELP', 'Item has unpublished changes'),
                ]
            ];
        }

        return [];
    }

    /**
     * Check if stages differ for a given record and all its relations
     */
    private function stagesDifferRecursive(DataObject $record): bool
    {
        /** @var RecursiveStagesInterface $service */
        $service = Injector::inst()->get(RecursiveStagesInterface::class);

        return $service->stagesDifferRecursive($record);
    }
}
