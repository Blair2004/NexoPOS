<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Tendoo\Core\Models\User;

use App\Models\Product;

class ProductCategory extends Model
{
    protected $table    =   'nexopos_' . 'products_categories';

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