<?php

use App\Classes\Schema;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!defined('NEXO_CREATE_PERMISSIONS')) {
            define('NEXO_CREATE_PERMISSIONS', true);
        }

        // Include theme permissions
        include_once dirname(__FILE__) . '/../../permissions/themes.php';

        // Add permissions to admin role
        $admin = Role::namespace('admin');
        if ($admin) {
            $admin->addPermissions([
                'manage.themes',
                'manage.theme.menus',
                'manage.theme.pages',
                'manage.theme.settings',
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove permissions
        Permission::whereIn('namespace', [
            'manage.themes',
            'manage.theme.menus',
            'manage.theme.pages',
            'manage.theme.settings',
        ])->delete();
    }
};
