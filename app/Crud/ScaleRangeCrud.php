<?php

namespace App\Crud;

use App\Classes\CrudForm;
use App\Classes\CrudTable;
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
    const IDENTIFIER = 'ns.scale-range';

    /**
     * Define the base table
     */
    protected $table = 'nexopos_scale_ranges';

    /**
     * Define namespace
     */
    protected $namespace = 'ns.scale-range';

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
    public $fillable = [ 'name', 'range_start', 'range_end', 'next_scale_plu', 'description', 'author' ];

    /**
     * Define columns and how it is structured.
     */
    public function getColumns(): array
    {
        return CrudTable::columns(
            CrudTable::column(
                identifier: 'name',
                label: __( 'Name' ),
            ),
            CrudTable::column(
                identifier: 'range_start',
                label: __( 'Range Start' ),
            ),
            CrudTable::column(
                identifier: 'range_end',
                label: __( 'Range End' ),
            ),
            CrudTable::column(
                identifier: 'next_scale_plu',
                label: __( 'Next PLU' ),
            ),
            CrudTable::column(
                label: __( 'Created At' ),
                identifier: 'created_at'
            )
        );
    }

    /**
     * Get actions configuration
     */
    public function setActions( CrudEntry $entry ): CrudEntry
    {
        $entry->action(
            label: __( 'Edit' ),
            identifier: 'edit',
            type: 'GOTO',
            url: ns()->url( '/dashboard/products/scale-range/edit/' . $entry->id ),
            permissions: [ 'nexopos.update.products' ]
        );

        $entry->action(
            label: __( 'Delete' ),
            identifier: 'delete',
            type: 'DELETE',
            url: ns()->url( '/api/crud/ns.scale-range/' . $entry->id ),
            permissions: [ 'nexopos.delete.products' ],
            confirm: [
                'message' => __( 'Would you like to delete this scale range?' ),
            ]
        );

        return $entry;
    }

    /**
     * Return the label used for the crud
     * instance
     *
     * @return array
     **/
    public function getLabels()
    {
        return [
            'list_title' => __( 'Scale Ranges List' ),
            'list_description' => __( 'Display all scale ranges.' ),
            'no_entry' => __( 'No scale ranges have been registered' ),
            'create_new' => __( 'Add a new scale range' ),
            'create_title' => __( 'Create a new scale range' ),
            'create_description' => __( 'Register a new scale range and save it.' ),
            'edit_title' => __( 'Edit scale range' ),
            'edit_description' => __( 'Modify  Scale Range.' ),
            'back_to_list' => __( 'Return to Scale Ranges' ),
        ];
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
                        FormInput::number(
                            label: __( 'Range Start' ),
                            name: 'range_start',
                            value: $entry->range_start ?? '',
                            validation: 'required|integer|min:0',
                            description: __( 'The starting PLU code for this range (e.g., 100 for 0100).' )
                        ),
                        FormInput::number(
                            label: __( 'Range End' ),
                            name: 'range_end',
                            value: $entry->range_end ?? '',
                            validation: 'required|integer|min:0',
                            description: __( 'The ending PLU code for this range (e.g., 999 for 0999).' )
                        ),
                        FormInput::number(
                            label: __( 'Next PLU' ),
                            name: 'next_scale_plu',
                            value: $entry->next_scale_plu ?? '',
                            validation: 'required|integer|min:0',
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
    public function beforePost( $inputs )
    {
        $this->allowedTo( 'create' );
        $this->validateRangeOverlap( $inputs );

        return $inputs;
    }
    
    /**
     * Before updating a range - validate no overlaps
     */
    public function beforePut( $inputs, ScaleRange | null $entry = null)
    {
        $this->allowedTo( 'update' );
        $this->validateRangeOverlap( $inputs, $entry );
        return $inputs;
    }

    /**
     * Validate that the range doesn't overlap with existing ranges
     */
    protected function validateRangeOverlap( $inputs, ScaleRange | null $scaleRange = null )
    {
        $rangeStart = $inputs[ 'range_start' ];
        $rangeEnd = $inputs[ 'range_end' ];

        // Validate that range_start is less than range_end
        if ( $rangeStart >= $rangeEnd ) {
            throw new \Exception( __( 'The range start must be less than the range end.' ) );
        }

        // Check for overlapping ranges - using integer comparison
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
        if ( $scaleRange ) {
            $query->where( 'id', '!=', $scaleRange->id );
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
        $nextPLU = $inputs[ 'next_scale_plu' ];

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
     * get Links
     *
     * @return array of links
     */
    public function getLinks(): array
    {
        return [
            'list' => ns()->url( 'dashboard/' . 'products/scale-range' ),
            'create' => ns()->url( 'dashboard/' . 'products/scale-range/create' ),
            'edit' => ns()->url( 'dashboard/' . 'products/scale-range/edit/{id}' ),
            'post' => ns()->url( 'api/crud/' . 'ns.scale-range' ),
            'put' => ns()->url( 'api/crud/' . 'ns.scale-range/{id}' . '' ),
        ];
    }
}
