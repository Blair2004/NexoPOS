<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int            $id
 * @property string         $uuid
 * @property int            $author
 * @property bool           $displays_on_pos
 * @property string         $description
 * @property \Carbon\Carbon $updated_at
 */
class ProductCategory extends NsModel
{
    use HasFactory;

    protected $guarded = [];

    protected $table = 'nexopos_' . 'products_categories';

    protected $isDependencyFor = [
        Product::class => [
            'local_index' => 'id',
            'local_name' => 'name',
            'foreign_index' => 'category_id',
            'foreign_name' => 'name',
        ],
        self::class => [
            'local_index' => 'id',
            'local_name' => 'name',
            'foreign_index' => 'parent_id',
            'foreign_name' => 'name',
        ],
    ];

    public function scopeDisplayOnPOS( $query, $attribute = true )
    {
        return $query->where( 'displays_on_pos', $attribute );
    }

    public function products()
    {
        return $this->hasMany( Product::class, 'category_id' );
    }

    /**
     * create relation to the
     * user model
     */
    public function author()
    {
        return $this->belongsTo( User::class, 'author' );
    }

    /**
     * get category sub categories
     */
    public function subCategories()
    {
        return $this->hasMany( self::class, 'parent_id' );
    }
}
