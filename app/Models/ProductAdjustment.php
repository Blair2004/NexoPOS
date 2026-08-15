<?php

namespace App\Models;

use Carbon\Carbon;

/**
 * @property int         $id
 * @property int         $author_id
 * @property string|null $title
 * @property string      $status
 * @property string|null $description
 * @property Carbon      $created_at
 * @property Carbon      $updated_at
 */
class ProductAdjustment extends NsModel
{
    protected $table = 'nexopos_' . 'products_adjustments';

    const STATUS_DRAFT = 'draft';

    const STATUS_PERFORMED = 'performed';

    protected $fillable = [
        'author_id',
        'title',
        'status',
        'description',
    ];

    public function items()
    {
        return $this->hasMany( ProductAdjustmentItem::class, 'adjustment_id' );
    }

    public function author()
    {
        return $this->belongsTo( User::class, 'author_id' );
    }
}
