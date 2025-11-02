---
applyTo: '**'
---

# NexoPOS Module Migration Guide

This document provides comprehensive guidelines for creating database migrations in NexoPOS modules, following Laravel 11+ conventions while adhering to NexoPOS best practices.

## Migration File Naming Convention

Migration files **SHOULD** follow Laravel 11+ timestamped naming convention:

```
YYYY_MM_DD_HHMMSS_descriptive_migration_name.php
```

**Note:** NexoPOS supports both old-style named migrations (`CreatePagesTable.php`) and new timestamped migrations (`2024_10_31_120000_create_pages_table.php`). However, **timestamped migrations are strongly recommended** for better organization and to avoid naming conflicts.

### Examples:
```
2024_10_31_120000_create_nspagebuilder_pages_table.php
2024_10_31_120001_add_status_to_nspagebuilder_pages_table.php
2024_10_31_120002_create_module_settings_table.php
```

### Legacy Support:
```
CreatePagesTable.php  (old-style, still supported but not recommended)
UpdatePagesTable.php  (old-style, still supported but not recommended)
```

### Naming Guidelines:

**For table creation:**
```
YYYY_MM_DD_HHMMSS_create_{table_name}_table.php
```

**For table modifications:**
```
YYYY_MM_DD_HHMMSS_add_{column_name}_to_{table_name}_table.php
YYYY_MM_DD_HHMMSS_update_{table_name}_add_{description}.php
YYYY_MM_DD_HHMMSS_modify_{column_name}_on_{table_name}_table.php
```

**For data migrations:**
```
YYYY_MM_DD_HHMMSS_migrate_{description}_data.php
```

## Migration File Structure

### Required Structure

**Every migration file MUST consist of a class (preferably anonymous) that extends `Migration` and implements two methods:**

1. **`up()`** - Executes when the migration is run
2. **`down()`** - Executes when the migration is rolled back

### Recommended: Anonymous Class (Laravel 11+ Style)

All migrations **SHOULD** return an instance of an anonymous class that extends `Migration`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Migration logic here
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Rollback logic here
    }
};
```

**Critical Requirements:**
- ✅ **MUST** return a class instance (anonymous or named)
- ✅ **MUST** extend `Illuminate\Database\Migrations\Migration`
- ✅ **MUST** implement `up()` method
- ✅ **MUST** implement `down()` method for proper rollback support
- ✅ **SHOULD** use `return new class extends Migration` pattern
- ✅ Both methods should have proper type hints (`: void`)

### How NexoPOS Discovers Migrations

NexoPOS automatically discovers and runs module migrations using this process:

1. **File Discovery**: Scans the `Migrations/` directory in your module
2. **Anonymous Class Detection**: Requires the migration file to check if it returns an object
3. **Fallback to Named Class**: If no object is returned, attempts to locate a named class
4. **Execution**: Calls the `up()` or `down()` method on the migration instance

**Priority Order:**
1. ✅ **First Priority**: File returns anonymous class instance (Laravel 11+ style)
2. ✅ **Second Priority**: File contains named class matching expected pattern
3. ❌ **Error**: File neither returns object nor contains discoverable class

### Legacy Named Class Support

For backward compatibility, NexoPOS still supports old-style named migration classes:

```php
<?php

namespace Modules\YourModule\Migrations;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePagesTable extends Migration
{
    public function up(): void
    {
        // Migration logic
    }

    public function down(): void
    {
        // Rollback logic
    }
}
```

**Important Notes:**
- ❌ This approach is **deprecated** - use anonymous classes instead
- ✅ Still requires both `up()` and `down()` methods
- ✅ Must extend `Migration` class
- ✅ Must use proper namespace matching file location

**Always prefer returning anonymous class instances for new migrations.**

## Best Practices

### 1. Always Check for Existence

Before creating tables or adding columns, **ALWAYS** check if they already exist to avoid conflicts:

#### Checking Tables:
```php
public function up(): void
{
    if (!Schema::hasTable('module_table_name')) {
        Schema::create('module_table_name', function (Blueprint $table) {
            // Table definition
        });
    }
}
```

#### Checking Columns:
```php
public function up(): void
{
    if (!Schema::hasColumn('module_table_name', 'new_column')) {
        Schema::table('module_table_name', function (Blueprint $table) {
            $table->string('new_column')->nullable();
        });
    }
}
```

#### Checking Multiple Columns:
```php
public function up(): void
{
    Schema::table('module_table_name', function (Blueprint $table) {
        if (!Schema::hasColumn('module_table_name', 'column_one')) {
            $table->string('column_one')->nullable();
        }
        
        if (!Schema::hasColumn('module_table_name', 'column_two')) {
            $table->integer('column_two')->default(0);
        }
    });
}
```

### 2. Avoid onDelete('cascade')

**DO NOT** use `onDelete('cascade')` in foreign key constraints. Let the module handle data deletion logic:

#### ❌ Bad Practice:
```php
$table->foreign('user_id')
    ->references('id')
    ->on('nexopos_users')
    ->onDelete('cascade'); // AVOID THIS
```

#### ✅ Good Practice:
```php
$table->foreign('user_id')
    ->references('id')
    ->on('nexopos_users');

// Handle deletion in model observer or service layer
```

### 3. Use Enum for Limited Values

For columns with known, limited values, use `enum()` instead of `string()`:

#### ✅ Correct:
```php
$table->enum('status', ['pending', 'completed', 'failed', 'cancelled'])
    ->default('pending');

$table->enum('type', ['daily', 'weekly', 'monthly', 'yearly'])
    ->default('daily');

$table->enum('priority', ['low', 'medium', 'high', 'urgent'])
    ->default('medium');
```

#### ❌ Incorrect:
```php
$table->string('status')->default('pending'); // Should use enum
```

### 4. Table Naming Convention

Module tables should be prefixed with the module namespace or identifier:

```php
// Good examples
'nspagebuilder_pages'
'nspagebuilder_blocks'
'mymodule_settings'
'mymodule_configurations'
```

### 5. Proper Indexing

Add indexes for frequently queried columns:

```php
Schema::create('module_table', function (Blueprint $table) {
    $table->id();
    $table->string('slug')->unique();
    $table->string('status');
    $table->unsignedBigInteger('user_id');
    $table->timestamps();
    
    // Add indexes
    $table->index('status');
    $table->index('user_id');
    $table->index('created_at');
});
```

### 6. Nullable vs Default Values

Be explicit about nullable columns and provide sensible defaults:

```php
// Nullable when value might not exist
$table->string('optional_field')->nullable();

// Default when there's a sensible fallback
$table->integer('count')->default(0);
$table->boolean('is_active')->default(true);
$table->enum('status', ['draft', 'published'])->default('draft');

// Required field
$table->string('required_field');
```

## Complete Migration Examples

### Example 1: Creating a New Table

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Check if table doesn't exist
        if (!Schema::hasTable('nspagebuilder_pages')) {
            Schema::create('nspagebuilder_pages', function (Blueprint $table) {
                $table->id();
                $table->string('title');
                $table->string('slug')->unique();
                $table->json('content')->nullable();
                $table->json('metadata')->nullable();
                $table->enum('status', ['draft', 'published', 'archived'])
                    ->default('draft');
                $table->unsignedBigInteger('author_id');
                $table->timestamp('published_at')->nullable();
                $table->timestamps();

                // Foreign key without cascade
                $table->foreign('author_id')
                    ->references('id')
                    ->on('nexopos_users');

                // Indexes
                $table->index('slug');
                $table->index('status');
                $table->index('author_id');
                $table->index('created_at');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('nspagebuilder_pages');
    }
};
```

### Example 2: Adding Columns to Existing Table

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('nspagebuilder_pages')) {
            Schema::table('nspagebuilder_pages', function (Blueprint $table) {
                // Check each column individually
                if (!Schema::hasColumn('nspagebuilder_pages', 'views_count')) {
                    $table->integer('views_count')->default(0);
                }
                
                if (!Schema::hasColumn('nspagebuilder_pages', 'template')) {
                    $table->string('template')->nullable();
                }
                
                if (!Schema::hasColumn('nspagebuilder_pages', 'is_featured')) {
                    $table->boolean('is_featured')->default(false);
                }
                
                if (!Schema::hasColumn('nspagebuilder_pages', 'priority')) {
                    $table->enum('priority', ['low', 'medium', 'high'])
                        ->default('medium');
                }
            });
            
            // Add indexes separately
            Schema::table('nspagebuilder_pages', function (Blueprint $table) {
                if (!Schema::hasColumn('nspagebuilder_pages', 'views_count')) {
                    $table->index('views_count');
                }
                
                if (!Schema::hasColumn('nspagebuilder_pages', 'is_featured')) {
                    $table->index('is_featured');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('nspagebuilder_pages')) {
            Schema::table('nspagebuilder_pages', function (Blueprint $table) {
                if (Schema::hasColumn('nspagebuilder_pages', 'views_count')) {
                    $table->dropColumn('views_count');
                }
                
                if (Schema::hasColumn('nspagebuilder_pages', 'template')) {
                    $table->dropColumn('template');
                }
                
                if (Schema::hasColumn('nspagebuilder_pages', 'is_featured')) {
                    $table->dropColumn('is_featured');
                }
                
                if (Schema::hasColumn('nspagebuilder_pages', 'priority')) {
                    $table->dropColumn('priority');
                }
            });
        }
    }
};
```

### Example 3: Modifying Existing Columns

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('nspagebuilder_pages')) {
            Schema::table('nspagebuilder_pages', function (Blueprint $table) {
                // Modify column only if it exists
                if (Schema::hasColumn('nspagebuilder_pages', 'title')) {
                    $table->string('title', 500)->change();
                }
                
                // Make column nullable
                if (Schema::hasColumn('nspagebuilder_pages', 'slug')) {
                    $table->string('slug')->nullable()->change();
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('nspagebuilder_pages')) {
            Schema::table('nspagebuilder_pages', function (Blueprint $table) {
                if (Schema::hasColumn('nspagebuilder_pages', 'title')) {
                    $table->string('title', 255)->change();
                }
                
                if (Schema::hasColumn('nspagebuilder_pages', 'slug')) {
                    $table->string('slug')->nullable(false)->change();
                }
            });
        }
    }
};
```

### Example 4: Data Migration

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Migrate data from old structure to new
        DB::table('old_pages')
            ->whereNotNull('content')
            ->chunk(100, function ($pages) {
                foreach ($pages as $page) {
                    DB::table('nspagebuilder_pages')->insert([
                        'title' => $page->name,
                        'slug' => \Illuminate\Support\Str::slug($page->name),
                        'content' => json_encode(['legacy' => $page->content]),
                        'status' => $this->mapStatus($page->active),
                        'author_id' => $page->user_id,
                        'created_at' => $page->created_at,
                        'updated_at' => $page->updated_at,
                    ]);
                }
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Optionally restore old data
        DB::table('nspagebuilder_pages')
            ->where('content', 'like', '%legacy%')
            ->delete();
    }

    /**
     * Map old status to new enum values
     */
    private function mapStatus($active): string
    {
        return $active ? 'published' : 'draft';
    }
};
```

### Example 5: Creating Pivot Table

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('nspagebuilder_page_tag')) {
            Schema::create('nspagebuilder_page_tag', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('page_id');
                $table->unsignedBigInteger('tag_id');
                $table->timestamps();

                // Foreign keys without cascade
                $table->foreign('page_id')
                    ->references('id')
                    ->on('nspagebuilder_pages');
                    
                $table->foreign('tag_id')
                    ->references('id')
                    ->on('nspagebuilder_tags');

                // Unique constraint
                $table->unique(['page_id', 'tag_id']);
                
                // Indexes
                $table->index('page_id');
                $table->index('tag_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('nspagebuilder_page_tag');
    }
};
```

## Common Column Types

### Strings and Text
```php
$table->string('name');                    // VARCHAR(255)
$table->string('name', 100);               // VARCHAR(100)
$table->text('description');               // TEXT
$table->longText('content');               // LONGTEXT
$table->char('code', 10);                  // CHAR(10)
```

### Numbers
```php
$table->integer('count');                  // INTEGER
$table->bigInteger('big_count');           // BIGINT
$table->unsignedBigInteger('user_id');     // UNSIGNED BIGINT
$table->decimal('price', 10, 2);           // DECIMAL(10,2)
$table->float('rating', 8, 2);             // FLOAT(8,2)
$table->double('precise_value', 15, 8);    // DOUBLE(15,8)
```

### Booleans
```php
$table->boolean('is_active')->default(true);
$table->boolean('is_featured')->default(false);
```

### Dates and Times
```php
$table->date('birth_date');                // DATE
$table->time('start_time');                // TIME
$table->dateTime('scheduled_at');          // DATETIME
$table->timestamp('published_at');         // TIMESTAMP
$table->timestamps();                      // created_at, updated_at
$table->softDeletes();                     // deleted_at
```

### JSON
```php
$table->json('metadata');                  // JSON
$table->jsonb('data');                     // JSONB (PostgreSQL)
```

### Enums
```php
$table->enum('status', ['draft', 'published', 'archived']);
$table->enum('type', ['basic', 'premium', 'enterprise']);
```

## Module-Specific Guidelines

### 1. Module Isolation

Keep module migrations independent:

```php
// ✅ Good: Module-specific table
Schema::create('mymodule_settings', function (Blueprint $table) {
    // ...
});

// ❌ Bad: Modifying core tables directly
Schema::table('nexopos_users', function (Blueprint $table) {
    // Avoid modifying core tables when possible
});
```

### 2. Backward Compatibility

Ensure migrations can run on existing installations:

```php
public function up(): void
{
    // Always check before creating
    if (!Schema::hasTable('module_table')) {
        Schema::create('module_table', function (Blueprint $table) {
            // ...
        });
    }
    
    // Check before adding columns
    if (Schema::hasTable('module_table')) {
        if (!Schema::hasColumn('module_table', 'new_field')) {
            Schema::table('module_table', function (Blueprint $table) {
                $table->string('new_field')->nullable();
            });
        }
    }
}
```

### 3. Rollback Safety

Implement safe rollback logic:

```php
public function down(): void
{
    // Only drop if table exists
    if (Schema::hasTable('module_table')) {
        Schema::dropIfExists('module_table');
    }
    
    // Only drop column if it exists
    if (Schema::hasColumn('module_table', 'field')) {
        Schema::table('module_table', function (Blueprint $table) {
            $table->dropColumn('field');
        });
    }
}
```

## Testing Migrations

### Running Migrations
```bash
# Run all pending migrations
php artisan migrate

# Run module-specific migrations
php artisan migrate --path=modules/ModuleName/Migrations

# Force run in production
php artisan migrate --force

# Run specific migration file
php artisan migrate --path=modules/ModuleName/Migrations/2024_10_31_120000_create_table.php
```

### Rolling Back
```bash
# Rollback last batch
php artisan migrate:rollback

# Rollback specific steps
php artisan migrate:rollback --step=2

# Rollback all migrations
php artisan migrate:reset

# Fresh migration (drop all + migrate)
php artisan migrate:fresh
```

### Migration Status
```bash
# Check migration status
php artisan migrate:status
```

## Common Pitfalls to Avoid

### ❌ Don't: Create migrations without timestamps
```php
// Bad filename
CreateUsersTable.php
```

### ✅ Do: Use timestamped filenames
```php
// Good filename
2024_10_31_120000_create_users_table.php
```

### ❌ Don't: Forget existence checks
```php
Schema::create('table', function (Blueprint $table) {
    // Will fail if table exists
});
```

### ✅ Do: Always check existence
```php
if (!Schema::hasTable('table')) {
    Schema::create('table', function (Blueprint $table) {
        // Safe to create
    });
}
```

### ❌ Don't: Use cascade deletions
```php
$table->foreign('user_id')->onDelete('cascade');
```

### ✅ Do: Handle in application logic
```php
$table->foreign('user_id');
// Handle deletion in Observer or Service
```

### ❌ Don't: Use string for limited values
```php
$table->string('status');
```

### ✅ Do: Use enum for limited values
```php
$table->enum('status', ['pending', 'completed', 'failed']);
```

## Summary Checklist

When creating a migration, ensure you:

- ✅ Use timestamped filename (YYYY_MM_DD_HHMMSS_description.php)
- ✅ Return anonymous class instance
- ✅ Check table/column existence before creating
- ✅ Avoid onDelete('cascade')
- ✅ Use enum for limited values
- ✅ Add proper indexes
- ✅ Provide sensible defaults
- ✅ Implement safe rollback in down()
- ✅ Test both up() and down() migrations
- ✅ Use module-prefixed table names
- ✅ Document complex migrations with comments

Following these guidelines ensures your migrations are safe, maintainable, and compatible with NexoPOS architecture.
