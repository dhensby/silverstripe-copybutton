silverstripe-copybutton
=======================

Adds a copy button to the GridField.

## Requirements

- SilverStripe ^6
- PHP ^8.3

## Installation

```bash
composer require dhensby/silverstripe-copybutton
```

## Usage

To add the button to the GridField, add `CopyButton` as a component. The most common place to do
this is in a `ModelAdmin` by overriding `getEditForm()`:

```php
use Unisolutions\GridField\CopyButton;

public function getEditForm($id = null, $fields = null)
{
    $form = parent::getEditForm($id, $fields);

    $form
        ->Fields()
        ->fieldByName($this->sanitiseClassName($this->modelClass))
        ->getConfig()
        ->addComponent(new CopyButton(), GridFieldEditButton::class);

    return $form;
}
```

### Display modes

By default the copy button appears in the action menu dropdown for each row. Pass `true` to the
constructor to render it as a standalone column button instead:

```php
->addComponent(new CopyButton(true));
```

### Cascading duplications

The copy action makes an exact duplicate of the record. To also duplicate relations, configure
cascading duplications on your `DataObject`:

See: https://docs.silverstripe.org/en/6/developer_guides/model/relations/#cascading-duplications

### Post-copy hook

To run custom logic after a record is copied (e.g. clearing certain relations), implement
`onAfterDuplicate()` on the `DataObject` or an extension:

```php
class MyDataObjectExtension extends DataExtension
{
    public function onAfterDuplicate(DataObject $original, bool $doWrite): void
    {
        // e.g. clear a relation on the new copy
        $this->owner->Tags()->removeAll();
    }
}
```

## Maintainer

Originally authored by Elvinas Liutkevičius <elvinas@unisolutions.eu>.  
Currently maintained by [dhensby](https://github.com/dhensby).

## License

BSD — see [silverstripe.org/BSD-license](http://silverstripe.org/BSD-license)
