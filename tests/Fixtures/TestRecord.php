<?php

namespace Unisolutions\Tests\GridField\Fixtures;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataObject;

class TestRecord extends DataObject implements TestOnly
{
    private static $table_name = 'TestRecord';

    private static $db = [
        'Title' => 'Varchar',
    ];
}
