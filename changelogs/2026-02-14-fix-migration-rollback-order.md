# Fix Migration Rollback Execution Order

**Date:** 2026-02-14
**Type:** Bug Fix
**Component:** Module System

## Summary

Fixed a critical bug where module migrations were being rolled back in chronological order (oldest first) instead of reverse chronological order (newest first), causing foreign key constraint violations and database errors during module removal or migration revert operations.

## Problem

When rolling back module migrations, the system was executing them in the same order as forward migrations (alphabetically/chronologically), which is incorrect because:

1. **Forward migrations** should run oldest → newest (to build dependencies first)
2. **Rollback migrations** should run newest → oldest (to remove dependencies first)

This caused failures when:
- A newer migration adds a foreign key constraint to a table created by an older migration
- Rolling back would try to drop the older table first, failing due to the constraint from the newer migration

## Changes Made

### Modified Files

**`app/Services/ModulesService.php`:**

1. **`revertMigrations()` method (line ~1312):**
   - Added `rsort( $migrationFiles )` to sort migrations in reverse order before executing rollback
   - Added detailed comment explaining why reverse order is necessary

2. **`dropAllMigrations()` method (line ~1857):**
   - Added `rsort( $migrations )` to sort migrations in reverse order before dropping
   - Added detailed comment explaining the constraint handling logic

## Technical Details

### Before Fix
```php
// Migrations executed in order: A, B, C (oldest to newest)
foreach ( $migrationFiles as $file ) {
    $this->__runSingleFile( 'down', $module[ 'namespace' ], $file );
}
```

### After Fix
```php
// Migrations sorted in reverse order: C, B, A (newest to oldest)
rsort( $migrationFiles );
foreach ( $migrationFiles as $file ) {
    $this->__runSingleFile( 'down', $module[ 'namespace' ], $file );
}
```

## Impact

### Positive Impact
- Module deletion now properly handles foreign key constraints
- Migration revert operations no longer fail with constraint violations
- Follows Laravel's standard migration rollback behavior
- More reliable module management operations

### Affected Operations
- `php artisan modules:migration {namespace} --forget` (reverting migrations)
- Module deletion via dashboard or API
- `ModulesService::revertMigrations()` method calls
- `ModulesService::dropAllMigrations()` method calls

## Testing Recommendations

Test scenarios:
1. Create a module with multiple migrations where later migrations depend on earlier ones (foreign keys)
2. Enable the module to run migrations
3. Delete or revert the module
4. Verify migrations roll back in correct order without errors

## Migration Required

None - this is a runtime behavior fix that doesn't require database changes.

## Breaking Changes

None - this fixes incorrect behavior to match expected standards.

## Related Issues

This fix resolves constraint violation errors when:
- Deleting modules with complex migration dependencies
- Manually reverting module migrations with `--forget` flag
- Running automated module cleanup operations
