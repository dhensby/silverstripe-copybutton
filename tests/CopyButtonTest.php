<?php

namespace Unisolutions\Tests\GridField;

use SilverStripe\Control\Controller;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\Session;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\Form;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridFieldConfig;
use SilverStripe\Forms\GridField\GridField_ActionMenuItem;
use SilverStripe\ORM\ValidationException;
use Unisolutions\GridField\CopyButton;
use Unisolutions\Tests\GridField\Fixtures\TestController;
use Unisolutions\Tests\GridField\Fixtures\TestRecord;

class CopyButtonTest extends SapphireTest
{
    protected static $fixture_file = 'tests/CopyButtonTest.yml';

    protected static $extra_dataobjects = [
        TestRecord::class,
    ];

    /** @var Controller|null Controller pushed by makeGridFieldWithForm, to be popped in tearDown */
    private ?Controller $pushedController = null;

    private function makeGridField(): GridField
    {
        return new GridField('TestRecords', 'Test Records', TestRecord::get(), GridFieldConfig::create());
    }

    /**
     * Creates a GridField attached to a Form so that GridField_FormAction can resolve Link().
     */
    private function makeGridFieldWithForm(): GridField
    {
        $gridField = $this->makeGridField();
        $controller = new TestController();
        $request = new HTTPRequest('GET', '/');
        $request->setSession(new Session([]));
        $controller->setRequest($request);
        $controller->pushCurrent();
        $this->pushedController = $controller;
        new Form($controller, 'TestForm', FieldList::create($gridField), FieldList::create());
        return $gridField;
    }

    protected function tearDown(): void
    {
        if ($this->pushedController !== null) {
            $this->pushedController->popCurrent();
            $this->pushedController = null;
        }
        parent::tearDown();
    }

    // augmentColumns

    public function testAugmentColumnsAddsActionsWhenMissing(): void
    {
        $button = new CopyButton();
        $columns = ['Title'];
        $button->augmentColumns($this->makeGridField(), $columns);
        $this->assertContains('Actions', $columns);
    }

    public function testAugmentColumnsDoesNotDuplicateActions(): void
    {
        $button = new CopyButton();
        $columns = ['Title', 'Actions'];
        $button->augmentColumns($this->makeGridField(), $columns);
        $this->assertCount(2, $columns);
    }

    // getColumnAttributes

    public function testGetColumnAttributesReturnsCompactClass(): void
    {
        $button = new CopyButton();
        $record = $this->objFromFixture(TestRecord::class, 'record1');
        $attrs = $button->getColumnAttributes($this->makeGridField(), $record, 'Actions');
        $this->assertSame(['class' => 'grid-field__col-compact'], $attrs);
    }

    // getColumnMetadata

    public function testGetColumnMetadataReturnsEmptyTitleForActionsColumn(): void
    {
        $button = new CopyButton();
        $meta = $button->getColumnMetadata($this->makeGridField(), 'Actions');
        $this->assertSame(['title' => ''], $meta);
    }

    // getColumnsHandled

    public function testGetColumnsHandledReturnsActions(): void
    {
        $button = new CopyButton();
        $this->assertSame(['Actions'], $button->getColumnsHandled($this->makeGridField()));
    }

    // getActions

    public function testGetActionsReturnsCopyrecord(): void
    {
        $button = new CopyButton();
        $this->assertSame(['copyrecord'], $button->getActions($this->makeGridField()));
    }

    // getColumnContent

    public function testGetColumnContentReturnsNullWhenNotUseAsColumn(): void
    {
        $button = new CopyButton(false);
        $record = $this->objFromFixture(TestRecord::class, 'record1');
        $this->logInWithPermission('ADMIN');
        $result = $button->getColumnContent($this->makeGridField(), $record, 'Actions');
        $this->assertNull($result);
    }

    public function testGetColumnContentReturnsNullWhenNoCreatePermission(): void
    {
        $button = new CopyButton(true);
        $record = $this->objFromFixture(TestRecord::class, 'record1');
        // No logged-in member — canCreate() returns false
        $this->logOut();
        $result = $button->getColumnContent($this->makeGridField(), $record, 'Actions');
        $this->assertNull($result);
    }

    public function testGetColumnContentReturnsFieldHtmlWhenPermitted(): void
    {
        $button = new CopyButton(true);
        $record = $this->objFromFixture(TestRecord::class, 'record1');
        $this->logInWithPermission('ADMIN');
        $result = $button->getColumnContent($this->makeGridFieldWithForm(), $record, 'Actions');
        $this->assertNotEmpty((string)$result);
    }

    // handleAction

    public function testHandleActionDuplicatesRecord(): void
    {
        $this->logInWithPermission('ADMIN');
        $button = new CopyButton();
        $record = $this->objFromFixture(TestRecord::class, 'record1');
        $initialCount = TestRecord::get()->count();

        $button->handleAction($this->makeGridField(), 'copyrecord', ['RecordID' => $record->ID], []);

        $this->assertSame($initialCount + 1, TestRecord::get()->count());
    }

    public function testHandleActionDoesNothingForUnknownRecord(): void
    {
        $this->logInWithPermission('ADMIN');
        $button = new CopyButton();
        $initialCount = TestRecord::get()->count();

        $button->handleAction($this->makeGridField(), 'copyrecord', ['RecordID' => 999999], []);

        $this->assertSame($initialCount, TestRecord::get()->count());
    }

    public function testHandleActionThrowsWhenNoCreatePermission(): void
    {
        $button = new CopyButton();
        $record = $this->objFromFixture(TestRecord::class, 'record1');
        $this->logOut();

        $this->expectException(ValidationException::class);

        $button->handleAction($this->makeGridField(), 'copyrecord', ['RecordID' => $record->ID], []);
    }

    public function testHandleActionIgnoresOtherActionNames(): void
    {
        $this->logInWithPermission('ADMIN');
        $button = new CopyButton();
        $initialCount = TestRecord::get()->count();

        $button->handleAction($this->makeGridField(), 'deleterecord', ['RecordID' => 1], []);

        $this->assertSame($initialCount, TestRecord::get()->count());
    }

    // getTitle

    public function testGetTitleReturnsNullWhenUseAsColumn(): void
    {
        $button = new CopyButton(true);
        $record = $this->objFromFixture(TestRecord::class, 'record1');
        $this->assertNull($button->getTitle($this->makeGridField(), $record, 'Actions'));
    }

    public function testGetTitleReturnsCopyStringWhenNotUseAsColumn(): void
    {
        $button = new CopyButton(false);
        $record = $this->objFromFixture(TestRecord::class, 'record1');
        $title = $button->getTitle($this->makeGridField(), $record, 'Actions');
        $this->assertNotNull($title);
        $this->assertIsString($title);
    }

    // getExtraData

    public function testGetExtraDataReturnsNullWhenUseAsColumn(): void
    {
        $button = new CopyButton(true);
        $record = $this->objFromFixture(TestRecord::class, 'record1');
        $this->assertNull($button->getExtraData($this->makeGridField(), $record, 'Actions'));
    }

    public function testGetExtraDataReturnsFieldAttributesWhenNotUseAsColumn(): void
    {
        $button = new CopyButton(false);
        $record = $this->objFromFixture(TestRecord::class, 'record1');
        $data = $button->getExtraData($this->makeGridFieldWithForm(), $record, 'Actions');
        $this->assertIsArray($data);
        $this->assertArrayHasKey('title', $data);
        $this->assertArrayHasKey('data-url', $data);
    }

    // getGroup

    public function testGetGroupReturnsNullWhenUseAsColumn(): void
    {
        $button = new CopyButton(true);
        $record = $this->objFromFixture(TestRecord::class, 'record1');
        $this->assertNull($button->getGroup($this->makeGridField(), $record, 'Actions'));
    }

    public function testGetGroupReturnsDefaultGroupWhenNotUseAsColumn(): void
    {
        $button = new CopyButton(false);
        $record = $this->objFromFixture(TestRecord::class, 'record1');
        $group = $button->getGroup($this->makeGridField(), $record, 'Actions');
        $this->assertSame(GridField_ActionMenuItem::DEFAULT_GROUP, $group);
    }
}
