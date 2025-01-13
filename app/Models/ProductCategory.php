<?php

namespace App\Models;

use App\Classes\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

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

    public function setDependencies()
    {
        return [
            Product::class => Model::dependant(
                local_name: 'name',
                local_index: 'id',
                foreign_name: 'name',
                foreign_index: 'category_id',
            ),
            self::class => Model::dependant(
                local_name: 'name',
                local_index: 'id',
                foreign_name: 'name',
                foreign_index: 'parent_id',
            ),
        ];
    }

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
