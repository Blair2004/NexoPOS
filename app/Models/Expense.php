<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class Expense extends NsModel
{
    use HasFactory;

    protected $table = 'nexopos_' . 'expenses';

    protected $casts = [
        'recurring' => 'boolean',
        'active' => 'boolean',
    ];

    const OCCURRENCE_START_OF_MONTH = 'month_starts';
    const OCCURRENCE_END_OF_MONTH = 'month_ends';
    const OCCURRENCE_MIDDLE_OF_MONTH = 'month_mid';
    const OCCURRENCE_SPECIFIC_DAY = 'on_specific_day';
    const OCCURRENCE_X_AFTER_MONTH_STARTS = 'x_after_month_starts';
    const OCCURRENCE_X_BEFORE_MONTH_ENDS = 'x_before_month_ends';

    public function category()
    {
        return $this->belongsTo( ExpenseCategory::class, 'category_id' );
    }

    public function scopeRecurring( $query )
    {
        return $query->where( 'recurring', true );
    }

    public function scopeNotRecurring( $query )
    {
        return $query->where( 'recurring', false );
    }

    public function scopeActive( $query )
    {
        return $query->where( 'active', true );
    }
}
