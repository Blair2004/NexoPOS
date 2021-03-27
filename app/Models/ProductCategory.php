<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ProductCategory extends NsModel
{
    use HasFactory;

    protected $guarded  =   [];
    protected $table    =   'nexopos_' . 'products_categories';

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