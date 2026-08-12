# Testing NexoPOS modules

Module tests belong to the module, but they run inside the host NexoPOS application. This is the default for modules that depend on NexoPOS services, models, routes, migrations, permissions, options, events, or module discovery.

## Required architecture

1. Store tests under `modules/{Namespace}/Tests`, divided into `Feature` and `Unit` when useful.
2. Write PHPUnit classes and name discoverable files with the `Test.php` suffix.
3. Run tests with the host application runner from the repository root:

```bash
php artisan test --compact modules/{Namespace}/Tests
php artisan test --compact modules/{Namespace}/Tests/Feature/ExampleTest.php
php artisan test --compact modules/{Namespace}/Tests/Feature/ExampleTest.php --filter=test_name
```

Add `--no-coverage` when the active PHPUnit configuration requests coverage and Xdebug or PCOV is unavailable.

Never execute `modules/{Namespace}/vendor/bin/phpunit` or bootstrap `modules/{Namespace}/vendor/autoload.php`. A module `vendor` directory contains module runtime dependencies; it is not the authoritative Laravel or test runtime.

## Host bootstrap invariant

The root `phpunit.xml` must bootstrap `tests/bootstrap.php`. That bootstrap must:

1. Resolve the host root to an absolute path.
2. Set the absolute path in both `$_ENV['APP_BASE_PATH']` and `$_SERVER['APP_BASE_PATH']` before Laravel creates the application.
3. Set the process environment value for consistency.
4. Require the root `vendor/autoload.php`.

This is mandatory with Laravel 12. Its default test case otherwise infers the application root from the first registered Composer loader. A module may load and prepend its own Composer autoloader, causing a later test application refresh to look for `bootstrap/app.php` inside the module.

## Test base classes

Feature tests normally extend the application test case directly:

```php
namespace Modules\ExampleModule\Tests\Feature;

use Tests\TestCase;

final class CreateBookingTest extends TestCase
{
    public function test_a_booking_can_be_created(): void
    {
        // ...
    }
}
```

When a module needs shared setup, use a thin module-local base class:

```php
namespace Modules\ExampleModule\Tests;

use Tests\TestCase as ApplicationTestCase;

abstract class TestCase extends ApplicationTestCase
{
}
```

Do not duplicate `CreatesApplication` merely to locate the host bootstrap. The shared test bootstrap owns application-root selection.

Pure unit tests that do not need Laravel may extend `PHPUnit\Framework\TestCase`. Use `Tests\TestCase` for Eloquent, the database, routes, controllers, the service container, events, permissions, options, module activation, and other NexoPOS integrations.

## Optional module PHPUnit configuration

A module-specific `phpunit.xml` is optional and should exist only for a dedicated CI job, module-only coverage, or module-specific safe database settings. It must still use the host bootstrap:

```xml
<phpunit bootstrap="../../tests/bootstrap.php" colors="true">
  <testsuites>
    <testsuite name="Module">
      <directory suffix="Test.php">Tests</directory>
    </testsuite>
  </testsuites>
  <source>
    <include>
      <directory suffix=".php">.</directory>
    </include>
    <exclude>
      <directory>Public</directory>
      <directory>Tests</directory>
      <directory>vendor</directory>
    </exclude>
  </source>
</phpunit>
```

Run it from the repository root with the root PHPUnit executable:

```bash
vendor/bin/phpunit --configuration modules/{Namespace}/phpunit.xml
```

The Laravel Artisan test command in this application already injects the root PHPUnit configuration, so do not combine `php artisan test` with a second `--configuration` option. Do not copy a module-local `vendor/autoload.php` bootstrap into development or CI configurations.

## Isolation and database safety

- Tests must establish their own activation, migration, permissions, options, factories, and records. Do not depend on another test file running first.
- Prefer `LazilyRefreshDatabase` when it is compatible with the NexoPOS and module migration lifecycle.
- Use factory states and model assertions where available.
- Create model data before enabling broad event or exception fakes.
- Use SQLite only when the relevant application and module migrations support it. Otherwise, configure a dedicated test database.
- Never allow module tests to fall back to a development or production database.
- Cover the happy path, authorization or validation failures, and relevant edge cases.

## Pest and Testbench

Do not migrate module tests to Pest to solve bootstrap problems. Pest runs on PHPUnit and uses the same bootstrap and Composer loaders, while this repository standardizes on PHPUnit classes.

Orchestra Testbench is only appropriate when code is intentionally designed as a reusable Laravel package independent of NexoPOS. Do not recreate NexoPOS services and module discovery in a Testbench fixture for ordinary modules.

## Review checklist

- Tests live under the owning module.
- Test filenames end in `Test.php` unless an explicitly ordered legacy suite still requires migration.
- The host runner and shared bootstrap are used.
- No module `vendor/bin` or module `vendor/autoload.php` is used for testing.
- The test database is explicitly safe.
- Tests do not rely on ordered state.
- Focused module tests pass before handoff.
