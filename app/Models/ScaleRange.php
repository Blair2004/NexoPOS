<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * @property int    $id
 * @property string $name
 * @property string $range_start
 * @property string $range_end
 * @property string $next_scale_plu
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
        if ( (int) $this->next_scale_plu > (int) $this->range_end ) {
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
        $length = strlen( $this->range_start );

        // Return zero-padded PLU
        return str_pad( $this->next_scale_plu, $length, '0', STR_PAD_LEFT );
    }

    /**
     * Increment the next_scale_plu counter
     *
     * @return void
     */
    public function incrementNextPLU(): void
    {
        $nextPlu = (int) $this->next_scale_plu + 1;
        $this->next_scale_plu = (string) $nextPlu;
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
        $rangeStart = (int) $this->range_start;
        $rangeEnd = (int) $this->range_end;

        // Check if PLU is within range
        if ( $pluInt < $rangeStart || $pluInt > $rangeEnd ) {
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
        $rangeStart = (int) $this->range_start;
        $rangeEnd = (int) $this->range_end;

        return $pluInt >= $rangeStart && $pluInt <= $rangeEnd;
    }

    /**
     * Get the total capacity of this range
     *
     * @return int
     */
    public function getCapacity(): int
    {
        return (int) $this->range_end - (int) $this->range_start + 1;
    }

    /**
     * Get the number of used PLUs in this range
     *
     * @return int
     */
    public function getUsedCount(): int
    {
        $rangeStart = str_pad( $this->range_start, strlen( $this->range_start ), '0', STR_PAD_LEFT );
        $rangeEnd = str_pad( $this->range_end, strlen( $this->range_end ), '0', STR_PAD_LEFT );

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
