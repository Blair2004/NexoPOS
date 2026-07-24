<?php

namespace App\Crud;

use App\Casts\DateCast;
use App\Casts\ProductAdjustmentCast;
use App\Classes\CrudTable;
use App\Exceptions\NotAllowedException;
use App\Models\ProductAdjustment;
use App\Services\CrudEntry;
use App\Services\CrudService;
use Illuminate\Http\Request;
use TorMorten\Eventy\Facades\Events as Hook;

class ProductAdjustmentCrud extends CrudService
{
    /**
     * Define the autoload status
     */
    const AUTOLOAD = true;

    /**
     * Define the identifier
     */
    const IDENTIFIER = 'ns.products-adjustments';

    /**
     * Define the base table
     */
    protected $table = 'nexopos_products_adjustments';

    /**
     * Define namespace
     */
    protected $namespace = 'ns.products-adjustments';

    /**
     * Model Used
     */
    protected $model = ProductAdjustment::class;

    /**
     * Display options column before data columns
     */
    protected $prependOptions = true;

    /**
     * Adding relation
     */
    public $relations = [
        [ 'nexopos_users as author', 'nexopos_products_adjustments.author_id', '=', 'author.id' ],
    ];

    public $pick = [
        'author' => [ 'username' ],
    ];

    protected $permissions = [
        'create' => 'nexopos.update.products',
        'read' => 'nexopos.update.products',
        'update' => 'nexopos.update.products',
        'delete' => 'nexopos.update.products',
    ];

    protected $casts = [
        'created_at' => DateCast::class,
        'status' => ProductAdjustmentCast::class,
    ];

    /**
     * Return the label used for the crud instance
     */
    public function getLabels()
    {
        return CrudTable::labels(
            list_title: __( 'Adjustment History' ),
            list_description: __( 'Displays all stock adjustment records.' ),
            no_entry: __( 'No adjustments found.' ),
            create_new: __( 'Add a new adjustment' ),
            create_title: __( 'Create a new adjustment' ),
            create_description: __( 'Register a new stock adjustment.' ),
            edit_title: __( 'Edit Adjustment' ),
            edit_description: __( 'Modify the adjustment.' ),
            back_to_list: __( 'Return to Adjustment History' ),
        );
    }

    public function getLinks(): array
    {
        return [
            'list' => ns()->url( '/dashboard/products/adjustment-history' ),
            'post' => ns()->url( '/api/crud/' . self::IDENTIFIER ),
            'put' => ns()->url( '/api/crud/' . self::IDENTIFIER . '/{id}' ),
        ];
    }

    /**
     * Define Columns
     */
    public function getColumns(): array
    {
        return CrudTable::columns(
            CrudTable::column( label: __( '#' ), identifier: 'id', width: '80px' ),
            CrudTable::column( label: __( 'Author' ), identifier: 'author_username', width: '150px' ),
            CrudTable::column( label: __( 'Title' ), identifier: 'title' ),
            CrudTable::column( label: __( 'Status' ), identifier: 'status', width: '100px' ),
            CrudTable::column( label: __( 'Description' ), identifier: 'description' ),
            CrudTable::column( label: __( 'Created At' ), identifier: 'created_at', width: '150px' ),
        );
    }

    /**
     * Define actions per entry
     */
    public function setActions( CrudEntry $entry ): CrudEntry
    {
        $entry->{ '$cssClass' } = match ( $entry->getOriginalValue( 'status' ) ) {
            ProductAdjustment::STATUS_DRAFT => 'info border text-sm',
            ProductAdjustment::STATUS_PERFORMED => 'success border text-sm',
            default => '',
        };

        if ( $entry->getOriginalValue( 'status' ) === ProductAdjustment::STATUS_DRAFT ) {
            $entry->action(
                identifier: 'edit',
                label: '<i class="mr-2 las la-edit"></i> ' . __( 'Edit' ),
                type: 'GOTO',
                url: ns()->url( '/dashboard/products/stock-adjustment/' . $entry->id )
            );

            $entry->action(
                identifier: 'execute',
                label: '<i class="mr-2 las la-play"></i> ' . __( 'Execute' ),
                type: 'GET',
                url: ns()->url( '/api/products/adjustments/' . $entry->id . '/execute' ),
                confirm: [
                    'message' => __( 'Would you like to execute this stock adjustment? This will permanently update inventory quantities.' ),
                ],
            );

            $entry->action(
                identifier: 'delete',
                label: '<i class="mr-2 las la-trash"></i> ' . __( 'Delete' ),
                type: 'DELETE',
                url: ns()->url( '/api/crud/ns.products-adjustments/' . $entry->id ),
                confirm: [
                    'message' => __( 'Would you like to delete this draft adjustment?' ),
                ],
            );
        } elseif ( $entry->getOriginalValue( 'status' ) === ProductAdjustment::STATUS_PERFORMED ) {
            $entry->action(
                identifier: 'view-adjustment-details',
                label: '<i class="mr-2 las la-eye"></i> ' . __( 'View Details' ),
                type: 'POPUP',
                url: '',
            );
        }

        return $entry;
    }

    public function hook( $query ): void
    {
        if ( empty( request()->query( 'direction' ) ) ) {
            $query->orderBy( 'id', 'desc' );
        }
    }

    /**
     * Before Delete
     */
    public function beforeDelete( string $namespace, $id, ProductAdjustment $model )
    {
        if ( $namespace === self::IDENTIFIER ) {
            if ( $model->status === ProductAdjustment::STATUS_PERFORMED ) {
                throw new NotAllowedException( __( 'Performed adjustments cannot be deleted.' ) );
            }
        }
    }

    /**
     * Bulk Delete Action
     */
    public function getBulkActions(): array
    {
        return [
            [
                'label' => __( 'Delete Selected' ),
                'identifier' => 'delete_selected',
                'url' => ns()->route( 'ns.api.crud-bulk-actions', [
                    'namespace' => $this->namespace,
                ] ),
            ],
        ];
    }

    public function bulkAction( Request $request )
    {
        if ( $request->input( 'action' ) === 'delete_selected' ) {
            ns()->restrict( $this->permissions[ 'delete' ] );

            $status = [
                'success' => 0,
                'error' => 0,
            ];

            foreach ( $request->input( 'entries' ) as $id ) {
                $entity = $this->model::find( $id );
                if ( $entity instanceof ProductAdjustment ) {
                    if ( $entity->status === ProductAdjustment::STATUS_PERFORMED ) {
                        $status[ 'error' ]++;
                    } else {
                        $entity->delete();
                        $status[ 'success' ]++;
                    }
                } else {
                    $status[ 'error' ]++;
                }
            }

            return $status;
        }

        return Hook::filter( $this->namespace . '-catch-action', false, $request );
    }
}
