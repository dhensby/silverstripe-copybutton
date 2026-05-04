<?php

namespace Unisolutions\GridField;

use SilverStripe\Forms\GridField\GridField_ActionMenuItem;
use SilverStripe\Forms\GridField\GridField_ColumnProvider;
use SilverStripe\Forms\GridField\GridField_ActionProvider;
use SilverStripe\Forms\GridField\GridField_FormAction;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\ValidationException;

/**
 * This component provides a button for copying record.
 * First of all it dublicates record and then opens opens edit form {@link GridFieldDetailForm}.
 *
 * @package    framework
 * @subpackage gridfield
 * @author     Elvinas Liutkevičius <elvinas@unisolutions.eu>
 * @license    BSD http://silverstripe.org/BSD-license
 */
class CopyButton implements GridField_ColumnProvider, GridField_ActionProvider, GridField_ActionMenuItem
{
    private $useAsColumn;

    public function __construct(bool $useAsColumn = false)
    {
        $this->useAsColumn = $useAsColumn;
    }

    public function augmentColumns($gridField, &$columns)
    {
        if (!in_array('Actions', $columns)) {
            $columns[] = 'Actions';
        }
    }

    public function getColumnAttributes($gridField, $record, $columnName)
    {
        return ['class' => 'grid-field__col-compact'];
    }

    public function getColumnMetadata($gridField, $columnName)
    {
        if ($columnName == 'Actions') {
            return ['title' => ''];
        }
    }

    public function getColumnsHandled($gridField)
    {
        return ['Actions'];
    }

    public function getActions($gridField)
    {
        return ['copyrecord'];
    }

    public function getColumnContent($gridField, $record, $columnName)
    {
        if ($this->useAsColumn === false) {
            return;
        }

        if (!$record->canCreate()) {
            return;
        }

        $field = $this->getCopyAction($gridField, $record, $columnName);

        return $field->Field();
    }

    private function getCopyAction($gridField, $record, $columnName)
    {
        $title = _t('GridAction.Copy', 'Copy');
        $field = GridField_FormAction::create(
            $gridField,
            'CopyRecord' . $record->ID,
            false,
            "copyrecord",
            ['RecordID' => $record->ID]
        )
            ->addExtraClass('gridfield-button-copy')
            ->setAttribute('classNames', 'font-icon-plus')
            ->setAttribute('title', $title)
            ->setDescription(_t('GridAction.COPY_DESCRIPTION', 'Copy'))
            ->setAttribute('aria-label', $title);

        return $field;
    }

    public function handleAction(GridField $gridField, $actionName, $arguments, $data)
    {
        if ($actionName == 'copyrecord') {
            /** @var \SilverStripe\ORM\DataObject $item */
            $item = $gridField->getList()->byID($arguments['RecordID']);
            if (!$item) {
                return;
            }

            if (!$item->canCreate()) {
                throw new ValidationException(
                    _t('GridFieldAction_Copy.CreatePermissionsFailure', "No create permissions"),
                    0
                );
            }

            $clone = $item->duplicate();
            if (!$clone || $clone->ID < 1) {
                user_error("Error Duplicating!", E_USER_ERROR);
            }
        }
    }

    public function getTitle($gridField, $record, $columnName)
    {
        if ($this->useAsColumn) {
            return;
        }

        return _t('GridAction.Copy', "Copy");
    }

    public function getExtraData($gridField, $record, $columnName)
    {
        if ($this->useAsColumn) {
            return;
        }

        $field = $this->getCopyAction($gridField, $record, $columnName);

        if ($field) {
            return $field->getAttributes();
        }

        return null;
    }

    public function getGroup($gridField, $record, $columnName)
    {
        if ($this->useAsColumn) {
            return;
        }

        return GridField_ActionMenuItem::DEFAULT_GROUP;
    }
}
