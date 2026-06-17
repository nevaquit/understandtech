# Testing — PHPUnit & Behat

> Unit tests, integration tests, Behat acceptance tests, data generators, test CLI commands.

## Contents

- When to load this file
- What to test
- PHPUnit
- External API tests
- Data generators
- Behat
- Upgrade and release checks

## When To Load This File

Load this first when the task mentions any of:

- PHPUnit
- Behat
- tests failing
- test coverage
- release checks
- regression
- upgrade verification
- acceptance test

## What To Test

Prioritize tests around behavior changes:

- capability enforcement
- data writes and reads
- external API parameter and return handling
- upgrade-sensitive migrations or helpers
- form processing logic when it transforms or saves data
- user-visible workflows for Behat

## PHPUnit

### File Location

```
local/myplugin/
└── tests/
    ├── manager_test.php          # Unit/integration tests
    ├── external/
    │   └── get_items_test.php    # External function tests
    └── generator/
        └── lib.php               # Test data generator
```

### Basic Test Class

```php
<?php
// tests/manager_test.php
namespace local_myplugin;

defined('MOODLE_INTERNAL') || die();

/**
 * Tests for the item manager.
 *
 * @package    local_myplugin
 * @copyright  2026 Your Name
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \local_myplugin\manager
 * @group      local_myplugin
 */
class manager_test extends \advanced_testcase {

    /**
     * Test creating an item.
     */
    public function test_create_item(): void {
        $this->resetAfterTest();

        // Create test data.
        $course = $this->getDataGenerator()->create_course();
        $user = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($user->id, $course->id, 'editingteacher');
        $this->setUser($user);

        // Execute.
        $manager = new manager();
        $item = $manager->create_item($course->id, 'Test Item', 'Description', FORMAT_HTML);

        // Assert.
        $this->assertIsObject($item);
        $this->assertEquals('Test Item', $item->name);
        $this->assertEquals($course->id, $item->courseid);
        $this->assertEquals($user->id, $item->usermodified);
    }

    /**
     * Test get_items returns only items for the given course.
     */
    public function test_get_items_filters_by_course(): void {
        $this->resetAfterTest();

        $course1 = $this->getDataGenerator()->create_course();
        $course2 = $this->getDataGenerator()->create_course();

        /** @var \local_myplugin\testing\generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('local_myplugin');
        $generator->create_item(['courseid' => $course1->id, 'name' => 'Item A']);
        $generator->create_item(['courseid' => $course1->id, 'name' => 'Item B']);
        $generator->create_item(['courseid' => $course2->id, 'name' => 'Item C']);

        $manager = new manager();
        $items = $manager->get_items($course1->id);

        $this->assertCount(2, $items);
    }

    /**
     * Test delete_item throws for non-existent item.
     */
    public function test_delete_nonexistent_item_throws(): void {
        $this->resetAfterTest();

        $manager = new manager();
        $this->expectException(\dml_missing_record_exception::class);
        $manager->delete_item(99999);
    }

    /**
     * Provide invalid names for testing.
     *
     * @return array
     */
    public static function invalid_names_provider(): array {
        return [
            'empty string'   => [''],
            'only spaces'    => ['   '],
            'too long'       => [str_repeat('a', 256)],
        ];
    }

    /**
     * Test create_item rejects invalid names.
     *
     * @dataProvider invalid_names_provider
     * @param string $name The invalid name.
     */
    public function test_create_item_rejects_invalid_name(string $name): void {
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $manager = new manager();

        $this->expectException(\invalid_parameter_exception::class);
        $manager->create_item($course->id, $name, '', FORMAT_PLAIN);
    }
}
```

### Key Patterns

| Pattern | Usage |
|---------|-------|
| `$this->resetAfterTest()` | Required in every test that modifies DB — resets after test |
| `$this->setUser($user)` | Set the current user for the test |
| `$this->setAdminUser()` | Set current user to admin |
| `$this->getDataGenerator()` | Access core/plugin data generators |
| `$this->expectException(...)` | Expect an exception to be thrown |
| `$this->assertDebuggingCalled()` | Assert `debugging()` was called |
| `$this->assertEventLegacyLogData()` | Assert legacy log data |

### Testing External Functions

```php
<?php
// tests/external/get_items_test.php
namespace local_myplugin\external;

use core_external\external_api;

/**
 * Tests for the get_items external function.
 *
 * @package    local_myplugin
 * @covers     \local_myplugin\external\get_items
 * @group      local_myplugin
 */
class get_items_test extends \advanced_testcase {

    public function test_get_items_returns_items(): void {
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $user = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($user->id, $course->id, 'student');

        $generator = $this->getDataGenerator()->get_plugin_generator('local_myplugin');
        $generator->create_item(['courseid' => $course->id, 'name' => 'Test Item']);

        $this->setUser($user);
        $result = get_items::execute($course->id);
        $result = external_api::clean_returnvalue(get_items::execute_returns(), $result);

        $this->assertCount(1, $result['items']);
        $this->assertEquals('Test Item', $result['items'][0]['name']);
    }

    public function test_get_items_requires_capability(): void {
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $user = $this->getDataGenerator()->create_user();
        // No enrolment — should fail capability check.

        $this->setUser($user);
        $this->expectException(\required_capability_exception::class);
        get_items::execute($course->id);
    }
}
```

## Data Generator

```php
<?php
// tests/generator/lib.php
namespace local_myplugin\testing;

defined('MOODLE_INTERNAL') || die();

/**
 * Data generator for local_myplugin.
 *
 * @package    local_myplugin
 * @copyright  2026 Your Name
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class generator extends \component_generator_base {

    /** @var int Counter for unique names. */
    protected int $itemcount = 0;

    /**
     * Reset the generator counters.
     */
    public function reset(): void {
        $this->itemcount = 0;
        parent::reset();
    }

    /**
     * Create a test item.
     *
     * @param array|stdClass $record Field overrides.
     * @return stdClass The created item record.
     */
    public function create_item($record = []): \stdClass {
        global $DB, $USER;

        $this->itemcount++;
        $record = (array) $record;

        $defaults = [
            'courseid'          => 0,
            'name'              => 'Test item ' . $this->itemcount,
            'description'       => 'Test description ' . $this->itemcount,
            'descriptionformat' => FORMAT_HTML,
            'status'            => 0,
            'usermodified'      => $USER->id,
            'timecreated'       => time(),
            'timemodified'      => time(),
        ];

        $record = array_merge($defaults, $record);
        $record['id'] = $DB->insert_record('local_myplugin_items', (object) $record);

        return (object) $record;
    }
}
```

**Usage in tests:**
```php
/** @var \local_myplugin\testing\generator $generator */
$generator = $this->getDataGenerator()->get_plugin_generator('local_myplugin');
$item = $generator->create_item(['courseid' => $course->id, 'name' => 'Custom Name']);
```

## Behat Acceptance Tests

### Feature File

```gherkin
# tests/behat/manage_items.feature
@local @local_myplugin
Feature: Manage items
  As a teacher
  I want to create and delete items
  So that I can organise course content

  Background:
    Given the following "courses" exist:
      | fullname | shortname |
      | Course 1 | C1        |
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | teacher1 | Teacher   | One      | teacher1@example.com |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |

  @javascript
  Scenario: Teacher creates a new item
    Given I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I navigate to "My Plugin" in current page administration
    When I click on "Add item" "button"
    And I set the following fields to these values:
      | Name        | Test Item   |
      | Description | Test desc   |
    And I click on "Save" "button"
    Then I should see "Test Item" in the "#items-list" "css_element"
    And I should see "Item created successfully"

  Scenario: Teacher deletes an item
    Given the following "local_myplugin > items" exist:
      | course | name      |
      | C1     | Old Item  |
    And I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I navigate to "My Plugin" in current page administration
    When I click on "Delete" "link" in the "Old Item" "table_row"
    And I click on "Yes" "button" in the "Confirm" "dialogue"
    Then I should not see "Old Item"
```

### Custom Behat Step Definitions

```php
<?php
// tests/behat/behat_local_myplugin.php

require_once(__DIR__ . '/../../../../lib/behat/behat_base.php');

use Behat\Mink\Exception\ExpectationException;

/**
 * Behat step definitions for local_myplugin.
 *
 * @package    local_myplugin
 */
class behat_local_myplugin extends behat_base {

    /**
     * Creates items for testing.
     *
     * @Given /^the following "local_myplugin > items" exist:$/
     * @param TableNode $data
     */
    public function the_following_items_exist(\Behat\Gherkin\Node\TableNode $data) {
        global $DB;

        foreach ($data->getHash() as $row) {
            $course = $DB->get_record('course', ['shortname' => $row['course']], '*', MUST_EXIST);
            $generator = behat_util::get_data_generator()->get_plugin_generator('local_myplugin');
            $generator->create_item([
                'courseid' => $course->id,
                'name'     => $row['name'],
            ]);
        }
    }
}
```

## Running Tests

### PHPUnit

```bash
# From Moodle root:

# Initialise PHPUnit (first time or after install.xml changes):
php admin/tool/phpunit/cli/init.php

# Run all plugin tests:
vendor/bin/phpunit --testsuite local_myplugin_testsuite

# Run a specific test file:
vendor/bin/phpunit local/myplugin/tests/manager_test.php

# Run a specific test method:
vendor/bin/phpunit --filter test_create_item local/myplugin/tests/manager_test.php

# Run by group:
vendor/bin/phpunit --group local_myplugin
```

### Behat

```bash
# Initialise Behat (first time):
php admin/tool/behat/cli/init.php

# Run all plugin features:
vendor/bin/behat --config /path/to/moodledata_behat/behatrun/behat/behat.yml \
    --tags @local_myplugin

# Run a specific feature:
vendor/bin/behat --config /path/to/behat.yml \
    local/myplugin/tests/behat/manage_items.feature

# Run a specific scenario:
vendor/bin/behat --config /path/to/behat.yml \
    --name "Teacher creates a new item"
```

## Test Naming Conventions

| Convention | Example |
|-----------|---------|
| Test class | `manager_test` (matches class being tested + `_test`) |
| Test method | `test_create_item` (prefix `test_`, snake_case, descriptive) |
| Negative test | `test_delete_nonexistent_item_throws` |
| Data provider | `invalid_names_provider` (returns `array` of named cases) |
| Feature file | `manage_items.feature` (action-oriented, snake_case) |
| Feature tag | `@local_myplugin` (Frankenstyle) |

## PHPUnit Config

Tests are auto-discovered if placed in `tests/` with correct naming. The test suite is auto-registered by Moodle's PHPUnit integration. Add `@group local_myplugin` to all test classes for selective runs.
