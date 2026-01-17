<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ThemeMenuItem extends Model
{
    use HasFactory;

    protected $table = 'nexopos_themes_menu_items';

    protected $fillable = [
        'menu_id',
        'parent_id',
        'title',
        'url',
        'target',
        'css_classes',
        'order',
        'depth',
    ];

    protected $casts = [
        'order' => 'integer',
        'depth' => 'integer',
    ];

    /**
     * Get the menu this item belongs to.
     */
    public function menu()
    {
        return $this->belongsTo(ThemeMenu::class, 'menu_id');
    }

    /**
     * Get the parent menu item.
     */
    public function parent()
    {
        return $this->belongsTo(ThemeMenuItem::class, 'parent_id');
    }

    /**
     * Get the child menu items.
     */
    public function children()
    {
        return $this->hasMany(ThemeMenuItem::class, 'parent_id')->orderBy('order');
    }
}
