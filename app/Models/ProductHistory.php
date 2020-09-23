<?php
namespace App\Models;

use App\Models\Unit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ProductHistory extends Model
{
    use HasFactory;
    
    protected $table    =   'nexopos_' . 'products_histories';

    const ACTION_STOCKED       =   'procured';
    const ACTION_DELETED       =   'deleted';
    const ACTION_TRANSFER_OUT  =   'outgoing-transfer';
    const ACTION_TRANSFER_IN   =   'incoming-transfer';
    const ACTION_REMOVED       =   'removed';
    const ACTION_ADDED         =   'added';
    const ACTION_SOLD          =   'sold';
    const ACTION_RETURNED      =   'returned';
    const ACTION_DEFECTIVE     =   'defective';

    public function scopeFindProduct( $query, $id )
    {
        return $query->where( 'product_id', $id );
    }

    public function unit()
    {
        return $this->belongsTo( Unit::class );
    }
}