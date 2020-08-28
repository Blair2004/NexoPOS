<?php 
namespace App\Services;

use App\Models\ProductCategory;

class ProductCategoryService
{
    /**
     * get speicifc category using
     * the provided id
     * @param int category id
     * @return ProductCategory|false
     */
    public function get( $id )
    {
        $category   =   ProductCategory::find( $id );
        if ( ! $category instanceof ProductCategory ) {
            return false;
        }
        return $category;
    }
}