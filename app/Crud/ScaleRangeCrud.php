<?php

namespace App\Crud;

use App\Classes\CrudForm;
use App\Classes\FormInput;
use App\Models\ScaleRange;
use App\Services\CrudEntry;
use App\Services\CrudService;
use Illuminate\Http\Request;

class ScaleRangeCrud extends CrudService
{
    /**
     * Define the autoload status
     */
    const AUTOLOAD = true;

    /**
     * Define the identifier
     */
    const IDENTIFIER = 'ns.scale-ranges';

    /**
     * Define the base table
     */
    protected $table = 'nexopos_scale_ranges';

    /**
     * Define namespace
     */
    protected $namespace = 'ns.scale-ranges';

    /**
     * Model Used
     */
    protected $model = ScaleRange::class;

    /**
     * Define permissions
     */
    protected $permissions = [
        'create' => 'nexopos.create.products',
        'read' => 'nexopos.read.products',
        'update' => 'nexopos.update.products',
        'delete' => 'nexopos.delete.products',
    ];

    /**
     * Define where statement
     */
    protected $listWhere = [];

    /**
     * Define where in statement
     */
    protected $whereIn = [];

    /**
     * Fields which will be filled during post/put
     */
    public $fillable = [ 'name', 'range_start', 'range_end', 'next_scale_plu', 'description' ];

    /**
     * Get Table Configuration
     */
    public function getTable(): array
    {
        return [
            'id' => [
                'label' => __( 'ID' ),
                '$direction' => '',
                '$sort' => false,
                'width' => '80px',
            ],
            'name' => [
                'label' => __( 'Name' ),
                '$direction' => '',
                '$sort' => true,
            ],
            'range_start' => [
                'label' => __( 'Range Start' ),
                '$direction' => '',
                '$sort' => true,
                'width' => '120px',
            ],
            'range_end' => [
                'label' => __( 'Range End' ),
                '$direction' => '',
                '$sort' => true,
                'width' => '120px',
            ],
            'next_scale_plu' => [
                'label' => __( 'Next PLU' ),
                '$direction' => '',
                '$sort' => false,
                'width' => '120px',
            ],
            'capacity' => [
                'label' => __( 'Capacity' ),
                '$direction' => '',
                '$sort' => false,
                'width' => '100px',
            ],
            'used' => [
                'label' => __( 'Used' ),
                '$direction' => '',
                '$sort' => false,
                'width' => '100px',
            ],
            'created_at' => [
                'label' => __( 'Created At' ),
                '$direction' => '',
                '$sort' => true,
                'width' => '150px',
            ],
        ];
    }

    /**
     * Get actions configuration
     */
    public function getActions( CrudEntry $entry ): CrudEntry
    {
        $entry->action(
            label: __( 'Edit' ),
            namespace: 'edit',
            type: 'GOTO',
            url: ns()->url( '/dashboard/products/scale-ranges/edit/' . $entry->id ),
            permissions: 'nexopos.update.products'
        );

        $entry->action(
            label: __( 'Delete' ),
            namespace: 'delete',
            type: 'DELETE',
            url: ns()->url( '/api/crud/ns.scale-ranges/' . $entry->id ),
            permissions: 'nexopos.delete.products',
            confirm: [
                'message' => __( 'Would you like to delete this scale range?' ),
            ]
        );

        return $entry;
    }

    /**
     * Get bulk actions configuration
     */
    public function getBulkActions(): array
    {
        return [];
    }

    /**
     * Get form configuration
     */
    public function getForm( $entry = null ): array
    {
        return CrudForm::form(
            main: FormInput::text(
                label: __( 'Name' ),
                name: 'name',
                value: $entry->name ?? '',
                validation: 'required',
                description: __( 'Provide a name for this PLU range.' )
            ),
            tabs: CrudForm::tabs(
                CrudForm::tab(
                    identifier: 'general',
                    label: __( 'General' ),
                    fields: [
                        FormInput::text(
                            label: __( 'Range Start' ),
                            name: 'range_start',
                            value: $entry->range_start ?? '',
                            validation: 'required|numeric|min:0',
                            description: __( 'The starting PLU code for this range (e.g., 0100).' )
                        ),
                        FormInput::text(
                            label: __( 'Range End' ),
                            name: 'range_end',
                            value: $entry->range_end ?? '',
                            validation: 'required|numeric|min:0',
                            description: __( 'The ending PLU code for this range (e.g., 0999).' )
                        ),
                        FormInput::text(
                            label: __( 'Next PLU' ),
                            name: 'next_scale_plu',
                            value: $entry->next_scale_plu ?? '',
                            validation: 'required|numeric|min:0',
                            description: __( 'The next PLU code to be assigned in this range.' )
                        ),
                        FormInput::textarea(
                            label: __( 'Description' ),
                            name: 'description',
                            value: $entry->description ?? '',
                            description: __( 'Optional description for this PLU range.' )
                        ),
                    ]
                )
            )
        );
    }

    /**
     * Hook for customizing entries
     */
    public function hook( $query ): void
    {
        // No additional query modifications needed
    }

    /**
     * Before creating a new range - validate no overlaps
     */
    public function beforePost( $request )
    {
        $this->allowedTo( 'create' );
        $this->validateRangeOverlap( $request );

        return $request;
    }

    /**
     * Before updating a range - validate no overlaps
     */
    public function beforePut( $namespace, $id, $request )
    {
        $this->allowedTo( 'update' );
        $this->validateRangeOverlap( $request, $id );

        return $request;
    }

    /**
     * Validate that the range doesn't overlap with existing ranges
     */
    protected function validateRangeOverlap( $request, $excludeId = null )
    {
        $rangeStart = (int) $request->input( 'range_start' );
        $rangeEnd = (int) $request->input( 'range_end' );

        // Validate that range_start is less than range_end
        if ( $rangeStart >= $rangeEnd ) {
            throw new \Exception( __( 'The range start must be less than the range end.' ) );
        }

        // Check for overlapping ranges
        $query = ScaleRange::where( function ( $query ) use ( $rangeStart, $rangeEnd ) {
            // Check if new range overlaps with any existing range
            $query->where( function ( $q ) use ( $rangeStart, $rangeEnd ) {
                // New range starts within an existing range
                $q->where( 'range_start', '<=', $rangeStart )
                  ->where( 'range_end', '>=', $rangeStart );
            } )->orWhere( function ( $q ) use ( $rangeStart, $rangeEnd ) {
                // New range ends within an existing range
                $q->where( 'range_start', '<=', $rangeEnd )
                  ->where( 'range_end', '>=', $rangeEnd );
            } )->orWhere( function ( $q ) use ( $rangeStart, $rangeEnd ) {
                // New range completely contains an existing range
                $q->where( 'range_start', '>=', $rangeStart )
                  ->where( 'range_end', '<=', $rangeEnd );
            } );
        } );

        // Exclude current range if updating
        if ( $excludeId ) {
            $query->where( 'id', '!=', $excludeId );
        }

        $overlappingRange = $query->first();

        if ( $overlappingRange ) {
            throw new \Exception( 
                sprintf(
                    __( 'The PLU range %d-%d overlaps with an existing range "%s" (%d-%d).' ),
                    $rangeStart,
                    $rangeEnd,
                    $overlappingRange->name,
                    $overlappingRange->range_start,
                    $overlappingRange->range_end
                )
            );
        }

        // Validate that next_scale_plu is within the range
        $nextPLU = (int) $request->input( 'next_scale_plu' );
        if ( $nextPLU < $rangeStart || $nextPLU > $rangeEnd ) {
            throw new \Exception( 
                sprintf(
                    __( 'The next PLU (%d) must be within the range %d-%d.' ),
                    $nextPLU,
                    $rangeStart,
                    $rangeEnd
                )
            );
        }
    }

    /**
     * Before delete hook
     */
    public function beforeDelete( $namespace, $id, $model )
    {
        // Check if any categories are using this range
        if ( $model->categories()->count() > 0 ) {
            return response()->json( [
                'status' => 'error',
                'message' => __( 'Cannot delete this PLU range because it is being used by one or more categories.' ),
            ], 403 );
        }

        // Check if any product unit quantities are using PLUs from this range
        $usedCount = $model->getUsedCount();
        if ( $usedCount > 0 ) {
            return response()->json( [
                'status' => 'error',
                'message' => sprintf(
                    __( 'Cannot delete this PLU range because %d product(s) are using PLU codes from this range.' ),
                    $usedCount
                ),
            ], 403 );
        }
    }

    /**
     * Define columns for export
     */
    protected $exportColumns = [ 'id', 'name', 'range_start', 'range_end', 'next_scale_plu', 'description', 'created_at' ];

    /**
     * Customize entry data for display
     */
    public function getEntries( $config = [] ): array
    {
        $entries = parent::getEntries( $config );

        // Add capacity and used count to each entry
        foreach ( $entries[ 'data' ] as $entry ) {
            $scaleRange = ScaleRange::find( $entry->id );
            if ( $scaleRange ) {
                $entry->capacity = $scaleRange->getCapacity();
                $entry->used = $scaleRange->getUsedCount();
            }
        }

        return $entries;
    }
}
