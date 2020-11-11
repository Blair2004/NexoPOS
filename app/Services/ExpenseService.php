<?php
namespace App\Services;

use App\Events\ExpenseHistoryAfterCreatedEvent;
use App\Exceptions\NotAllowedException;
use App\Models\Expense;
use Illuminate\Support\Facades\Auth;
use App\Models\ExpenseCategory;
use App\Exceptions\NotFoundException;
use App\Models\ExpenseHistory;
use App\Models\Role;
use Carbon\Carbon;

class ExpenseService 
{
    /**
     * @var DateService
     */
    protected $dateService;

    public function __construct( DateService $dateService )
    {   
        $this->dateService      =   $dateService;
    }
    
    public function create( $fields )
    {
        $expense    =   new Expense;

        foreach( $fields as $field => $value ) {
            $expense->$field    =   $value;
        }

        $expense->author        =   Auth::id();
        $expense->save();

        return [
            'status'    =>  'success',
            'message'   =>  __( 'The expense has been successfully saved.' ),
            'data'      =>  compact( 'expense' )
        ];
    }

    public function edit( $id, $fields )
    {
        $expense    =   $this->get( $id );

        if ( $expense instanceof Expense ) {
            
            foreach( $fields as $field => $value ) {
                $expense->$field    =   $value;
            }

            $expense->author        =   Auth::id();
            $expense->save();

            return [
                'status'    =>  'success',
                'message'   =>  __( 'The expense has been successfully updated.' ),
                'data'      =>  compact( 'expense' )
            ];
        }

        throw new NotFoundException([
            'status'    =>  'failed',
            'message'   =>  __( 'Unable to find the expense using the provided identifier.' )
        ]);
    }

    /**
     * get a specific expense using
     * the provided id
     * @param int expense id
     * @return Collection|Expense|NotFoundException
     */
    public function get( $id = null ) 
    {
        if ( $id === null ) {
            return Expense::get();
        }

        $expense    =   Expense::find( $id );
        
        if ( ! $expense instanceof Expense ) {
            throw new NotFoundException([
                'status'    =>  'failed',
                'message'   =>  __( 'Unable to find the requested expense using the provided id.' )
            ]);
        }

        return $expense;
    }

    /**
     * Delete an expense using the 
     * provided id
     * @param int expense id
     * @return array
     */
    public function delete( $id )
    {
        $expense        =   $this->get( $id );              
        $expense->delete();

        return [
            'status'    =>  'success',
            'message'   =>  __( 'The expense has been correctly deleted.' )
        ];
    }

    public function getCategories( $id = null )
    {
        if ( $id !== null ) {
            $category   =   ExpenseCategory::find( $id );
            if ( ! $category instanceof ExpenseCategory ) {
                throw new NotFoundException([
                    'status'    =>  'failed',
                    'message'   =>  __( 'Unable to find the requested expense category using the provided id.' )
                ]);
            }

            return $category;
        }

        return ExpenseCategory::get();
    }

    /**
     * Delete a specific category
     * using the provided id, along with the expenses
     * @param int id
     * @param boolean force deleting
     * @return array|NotAllowedException
     */
    public function deleteCategory( $id, $force = false )
    {
        $expenseCategory    =   $this->getCategories( $id );

        if ( $expenseCategory->expenses->count() > 0 && $force === false ) {
            throw new NotAllowedException( __( 'You cannot delete a category which has expenses bound.' ) );
        }

        /**
         * if there is not expense, it 
         * won't be looped
         */
        $expenseCategory->expenses->map( function( $expense ) {
            $expense->delete();
        });

        $expenseCategory->delete();
        
        return [
            'status'    =>  'success',
            'message'   =>  __( 'The expense category has been deleted.' )
        ];
    }

    /**
     * Get a specific expense
     * category using the provided ID
     * @param int expense category id
     * @return void|Collection
     */
    public function getCategory( $id )
    {
        $expenseCategory    =   ExpenseCategory::find( $id );
        
        if ( ! $expenseCategory instanceof ExpenseCategory ) {
            throw new NotFoundException([
                'status'    =>  'failed',
                'message'   =>  __( 'Unable to find the expense category using the provided ID.' )
            ]);
        }

        return $expenseCategory;
    }

    /**
     * Create a category using 
     * the provided details
     * @param array category detail
     * @return array status of the operation
     */
    public function createCategory( $fields )
    {
        $category    =   new ExpenseCategory;

        foreach( $fields as $field => $value ) {
            $category->$field    =   $value;
        }

        $category->author    =   Auth::id();
        $category->save();
        
        return [
            'status'    =>  'success',
            'message'   =>  __( 'The expense category has been saved' ),
            'data'      =>  compact( 'category' )
        ];
    }

    /**
     * Update specified expense
     * category using a provided ID
     * @param int expense category ID
     * @param array of values to update
     * @return array operation status
     */
    public function editCategory( $id, $fields )
    {
        $category    =   $this->getCategory( $id );

        foreach( $fields as $field => $value ) {
            $category->$field    =   $value;
        }

        $category->author        =   Auth::id();
        $category->save();

        return [
            'status'    =>  'success',
            'message'   =>  __( 'The expense category has been updated.' ),
            'data'      =>  compact( 'category' )
        ];
    }

    /**
     * Will trigger for not recurring expense
     * @param Expense $expense
     * @return void
     */
    public function triggerExpense( $expense )
    {
        $this->recordExpenseHistory( $expense );

        /**
         * a non recurring expenses
         * once triggered should be disabled to 
         * prevent futher execution on modification.
         */
        $expense->active    =   false;
        $expense->save();
    }

    public function getCategoryExpense( $id )
    {
        $expenseCategory    =   $this->getCategory( $id );
        return $expenseCategory->expenses;
    }

    public function recordExpenseHistory( Expense $expense )
    {
        if ( ! empty( $expense->group_id  ) ) {
            Role::find( $expense->group_id )->users->each( function( $user ) use ( $expense ) {
                $history                            =   new ExpenseHistory;
                $history->value                     =   $expense->value;
                $history->expense_id                =   $expense->id;
                $history->author                    =   $expense->author;
                $history->expense_name              =   str_replace( '{user}', ucwords( $user->username ), $expense->name );
                $history->expense_category_name     =   $expense->category->name;
                $history->save();

                event( new ExpenseHistoryAfterCreatedEvent( $history ) );
            });
        } else {
            $history                            =   new ExpenseHistory;
            $history->value                     =   $expense->value;
            $history->expense_id                =   $expense->id;
            $history->author                    =   $expense->author;
            $history->expense_name              =   $expense->name;
            $history->expense_category_name     =   $expense->category->name;
            $history->save();

            event( new ExpenseHistoryAfterCreatedEvent( $history ) );
        }
    }

    /**
     * Process recorded expenses
     * and check wether they are supposed to be processed 
     * on the current day. 
     * 
     * @return array of process results.
     */
    public function handleRecurringExpenses()
    {
        $processStatus      =   Expense::recurring()
            ->active()
            ->get()
            ->map( function( $expense ) {
                switch( $expense->occurence ) {
                    case 'month_starts':
                        $expenseScheduledDate   =   Carbon::parse( $this->dateService->copy()->startOfMonth() );   
                    break;
                    case 'month_mid':
                        $expenseScheduledDate   =   Carbon::parse( $this->dateService->copy()->startOfMonth()->addDays(14) );
                    break;
                    case 'month_ends':
                        $expenseScheduledDate   =   Carbon::parse( $this->dateService->copy()->endOfMonth() );
                    break;
                    case 'x_before_month_ends':
                        $expenseScheduledDate   =   Carbon::parse( $this->dateService->copy()->endOfMonth()->subDays( $expense->occurence_value ) );
                    break;
                    case 'x_after_month_starts':
                        $expenseScheduledDate   =   Carbon::parse( $this->dateService->copy()->startOfMonth()->addDays( $expense->occurence_value ) );
                    break;
                }

                /**
                 * Checks if the recurring expenses about to be saved has been
                 * already issued on the occuring day.
                 */
                if ( $this->dateService->isSameDay( $expenseScheduledDate ) ) {
                    
                    if ( ! $this->hadExpenseRecordedAlready( $expenseScheduledDate, $expense ) ) {
                        
                        $this->recordExpenseHistory( $expense );
                        
                        return [
                            'status'    =>  'success',
                            'message'   =>  sprintf( __( 'The expense "%s" has been processed.' ), $expense->name ),
                        ];
                    } 

                    return [
                        'status'    =>  'failed',
                        'message'   =>  sprintf( __( 'The expense "%s" has already been processed.' ), $expense->name ),
                    ];
                } 

                return [
                    'status'    =>  'failed',
                    'message'   =>  sprintf( __( 'The expenses "%s" hasn\'t been proceesed it\'s out of date.' ), $expense->name )
                ];

            });

        $successFulProcesses    =   collect( $processStatus )->filter( fn( $process ) => $process[ 'status' ] === 'success' );

        return [
            'status'    =>  'success',
            'data'      =>  $processStatus->toArray(),
            'message'   =>  $successFulProcesses->count() === $processStatus->count() ?
                __( 'The process has been correctly executed and all expenses has been processed.' ) :
                    sprintf( __( 'The process has been executed with some failures. %s/%s process(es) has successed.' ), $successFulProcesses->count(), $processStatus->count() )
        ];
    }

    /**
     * Check if an expense has been executed during a day.
     * To prevent many recurring expenses to trigger multiple times
     * during a day.
     */
    public function hadExpenseRecordedAlready( $date, Expense $expense )
    {
        $history    =   ExpenseHistory::where( 'expense_id', $expense->id )
            ->where( 'created_at', '>=', $date->startOfDay()->toDateTimeString() )
            ->where( 'created_at', '<=', $date->endOfDay()->toDateTimeString() )
            ->get();

        return $history instanceof ExpenseHistory;
    }
}