<?php

namespace App\Models;

/**
 * @property int $id
 * @property string $uuid
 * @property int $author
 * @property bool $featured
 * @property \Carbon\Carbon $updated_at
 */
class ProductGallery extends NsModel
{
    protected $table = 'nexopos_products_galleries';

    public $casts = [
        'featured' => 'boolean',
        'product_id' => 'integer',
    ];

    public function product()
    {
        return $this->belongsTo( Product::class );
    }
}
