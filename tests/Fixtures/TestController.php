<?php

namespace Unisolutions\Tests\GridField\Fixtures;

use SilverStripe\Control\Controller;
use SilverStripe\Dev\TestOnly;

class TestController extends Controller implements TestOnly
{
    private static $url_segment = 'test-controller';
}
