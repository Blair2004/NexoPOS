<?php 
namespace App\Services;

use App\Events\ProductCategoryAfterCreatedEvent;
use App\Models\ProductCategory;
use Illuminate\Support\Facades\Auth;

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

    /**
     * Get a specific category using
     * a defined name
     * @param string $name
     * @return ProductCategory|null
     */
    public function getUsingName( $name )
    {
        return ProductCategory::where( 'name', $name )->first();
    }

    /**
     * create a single category and store
     * it on the database
     * @param array details
     * @return array
     */
    public function create( $data, ProductCategory $productCategory = null )
    {
        $category                   =   $productCategory === null ? new ProductCategory : $productCategory;
        $category->author           =   Auth::id();
        $category->description      =   $data[ 'description' ] ?? '';
        $category->preview_url      =   $data[ 'preview_url' ] ?? '';
        $category->name             =   $data[ 'name' ];
        $category->parent_id        =   $data[ 'parent_id' ] ?? null;
        $category->displays_on_pos  =   $data[ 'displays_on_pos' ] ?? true;
        $category->save();

        ProductCategoryAfterCreatedEvent::dispatch( $category );

        return [
            'status'    =>  'success',
            'message'   =>  __( 'The category has been created' ),
            'data'      =>  compact( 'category' )
        ];
    }

    public function computeProducts( ProductCategory $category )
    {
        $category->total_items  =   $category->products()->count();
        $category->save();
    }
}