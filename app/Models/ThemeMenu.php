<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ThemeMenu extends Model
{
    use HasFactory;

    protected $table = 'nexopos_themes_menus';

    protected $fillable = [
        'name',
        'identifier',
        'theme_namespace',
    ];

    /**
     * Get the menu items for this menu.
     */
    public function items()
    {
        return $this->hasMany(ThemeMenuItem::class, 'menu_id')
            ->whereNull('parent_id')
            ->orderBy('order');
    }

    /**
     * Get all menu items (including children).
     */
    public function allItems()
    {
        return $this->hasMany(ThemeMenuItem::class, 'menu_id');
    }
}
