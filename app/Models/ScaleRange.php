<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * @property int    $id
 * @property string $name
 * @property int    $range_start
 * @property int    $range_end
 * @property int    $next_scale_plu
 * @property string $description
 * @property int    $author
 */
class ScaleRange extends NsModel
{
    use HasFactory;

    protected $table = 'nexopos_scale_ranges';

    protected $fillable = [
        'name',
        'range_start',
        'range_end',
        'next_scale_plu',
        'description',
        'author',
    ];

    protected $casts = [
        'range_start' => 'integer',
        'range_end' => 'integer',
        'next_scale_plu' => 'integer',
    ];

    /**
     * Get categories that use this scale range
     */
    public function categories()
    {
        return $this->hasMany( ProductCategory::class, 'scale_range_id' );
    }

    /**
     * Get the next available PLU code with zero-padding
     *
     * @return string
     * @throws \Exception
     */
    public function getNextPLU(): string
    {
        // Check if we've exhausted the range
        if ( $this->next_scale_plu > $this->range_end ) {
            throw new \Exception(
                sprintf(
                    __( 'PLU range "%s" is exhausted. Current: %s, End: %s' ),
                    $this->name,
                    $this->next_scale_plu,
                    $this->range_end
                )
            );
        }

        // Get the length from the range_start to maintain consistent padding
        $length = strlen( (string) $this->range_start );

        // Return zero-padded PLU
        return str_pad( (string) $this->next_scale_plu, $length, '0', STR_PAD_LEFT );
    }

    /**
     * Increment the next_scale_plu counter
     *
     * @return void
     */
    public function incrementNextPLU(): void
    {
        $this->next_scale_plu = $this->next_scale_plu + 1;
        $this->save();
    }

    /**
     * Check if a PLU code is available within this range
     *
     * @param string $plu
     * @return bool
     */
    public function isPLUAvailable( string $plu ): bool
    {
        // Remove any leading zeros for comparison
        $pluInt = (int) $plu;

        // Check if PLU is within range
        if ( $pluInt < $this->range_start || $pluInt > $this->range_end ) {
            return false;
        }

        // Check if PLU is not already assigned
        $existingPlu = ProductUnitQuantity::where( 'scale_plu', $plu )->first();

        return ! $existingPlu;
    }

    /**
     * Check if a PLU code belongs to this range (regardless of availability)
     *
     * @param string $plu
     * @return bool
     */
    public function containsPLU( string $plu ): bool
    {
        $pluInt = (int) $plu;

        return $pluInt >= $this->range_start && $pluInt <= $this->range_end;
    }

    /**
     * Get the total capacity of this range
     *
     * @return int
     */
    public function getCapacity(): int
    {
        return $this->range_end - $this->range_start + 1;
    }

    /**
     * Get the number of used PLUs in this range
     *
     * @return int
     */
    public function getUsedCount(): int
    {
        // Get the length for zero-padding from range_start
        $length = strlen( (string) $this->range_start );
        
        // Generate zero-padded range boundaries for string comparison
        $rangeStart = str_pad( (string) $this->range_start, $length, '0', STR_PAD_LEFT );
        $rangeEnd = str_pad( (string) $this->range_end, $length, '0', STR_PAD_LEFT );

        return ProductUnitQuantity::whereBetween( 'scale_plu', [ $rangeStart, $rangeEnd ] )
            ->whereNotNull( 'scale_plu' )
            ->count();
    }

    /**
     * Get the number of available PLUs in this range
     *
     * @return int
     */
    public function getAvailableCount(): int
    {
        return $this->getCapacity() - $this->getUsedCount();
    }
}
