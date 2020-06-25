<?php
namespace App\Services;

use App\Models\Expense;
use Illuminate\Support\Facades\Auth;
use App\Models\ExpenseCategory;
use Tendoo\Core\Exceptions\NotFoundException;

class ExpenseService 
{
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
     * @return array|NotFoundException
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
            throw new NotAllowedException([
                'message'   =>  __( 'You cannot delete a category which has expenses bound.' )
            ]);
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
     * @return array|void
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

    public function getCategoryExpense( $id )
    {
        $expenseCategory    =   $this->getCategory( $id );
        return $expenseCategory->expenses;
    }
}