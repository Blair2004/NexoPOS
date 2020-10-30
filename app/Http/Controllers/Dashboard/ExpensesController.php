<?php

/**
 * NexoPOS Controller
 * @since  1.0
**/

namespace App\Http\Controllers\Dashboard;

use App\Crud\ExpenseHistoryCrud;
use App\Http\Controllers\DashboardController;
use App\Models\Expense;
use Illuminate\Http\Request;
use App\Services\ExpenseService;
use App\Services\Options;

class ExpensesController extends DashboardController
{
    public function __construct( ExpenseService $expense, Options $options )
    {
        parent::__construct();
        $this->optionsService   =   $options;
        $this->expenseService   =   $expense;
    }

    public function get( $id = null )
    {
        return $this->expenseService->get( $id );
    }

    public function listExpenses()
    {
        return $this->view( 'pages.dashboard.crud.table', [
            'src'           =>  url( '/api/nexopos/v4/crud/ns.expenses' ),
            'title'         =>  __( 'Expenses' ),
            'description'   =>  __( 'List all created expenses' ),
            'createUrl'    =>  url( '/dashboard/expenses/create' )
        ]);
    }

    public function createExpense()
    {
        return $this->view( 'pages.dashboard.crud.form', [
            'src'           =>  url( '/api/nexopos/v4/crud/ns.expenses/form-config' ),
            'title'         =>  __( 'Create Expense' ),
            'submitUrl'     =>  url( '/api/nexopos/v4/crud/ns.expenses' ),
            'description'   =>  __( 'add new expense on the system' ),
            'returnUrl'    =>  url( '/dashboard/expenses' )
        ]);
    }

    public function editExpense( Expense $expense )
    {
        return $this->view( 'pages.dashboard.crud.form', [
            'src'           =>  url( '/api/nexopos/v4/crud/ns.expenses' ),
            'title'         =>  __( 'Edit Expense' ),
            'submitUrl'     =>  url( '/api/nexopos/v4/crud/ns.expenses' ),
            'submitMethod'  =>  'PUT',
            'description'   =>  __( 'edit an existing expense' ),
            'returnUrl'    =>  url( '/dashboard/expenses' )
        ]);
    }

    /**
     * Implement an expense registration
     * @param Request
     * @return json
     */
    public function post( Request $request ) // <= need to add a validation
    {
        $fields     =   $request->only([ 'name', 'category_id', 'description', 'media_id', 'value' ]);
        return $this->expenseService->create( $fields );
    }

    public function putExpenseCategory( Request $request, $id )
    {
        $fields     =   $request->only([ 'name', 'category_id', 'description', 'media_id', 'value' ]);
        return $this->expenseService->editCategory( $id, $fields );
    }

    /**
     * Implement saving an expense
     * should check the recursive hierarchy
     * @param Request
     * @param int expense id
     * @return json
     */
    public function put( Request $request, $id )
    {
        $fields     =   $request->only([ 'name', 'category_id', 'description', 'media_id', 'value' ]);
        return $this->expenseService->edit( $id, $fields );
    }

    public function delete( $id )
    {
        return $this->expenseService->delete( $id );
    }

    /**
     * get an expense category
     * @param int|null category id
     * @return json
     */
    public function getExpensesCategories( $id = null )
    {
        return $this->expenseService->getCategories( $id );
    }

    /**
     * delete a specific category
     * @param int category id
     * @return json
     */
    public function deleteCategory( $id )
    {
        return $this->expenseService->deleteCategory( $id );
    }

    /**
     * Create an expense category
     * @param Request
     * @return json
     */
    public function postExpenseCategory( Request $request )
    {
        $fields             =   $request->only([ 'name', 'description' ]);
        return $this->expenseService->createCategory( $fields );
    }

    /**
     * Edit an expense cateogry
     * @param Request
     * @param int expense category id
     * @return json
     */
    public function putExpenseCateogry( Request $request, $id )
    {
        $fields         =   $request->only([ 'name', 'description' ]);
        return $this->expenseService->editCategory( $id, $fields );
    }

    /**
     * Get expenses entries under a specific 
     * expense category
     * @param int Expense Category ID
     * @return array
     */
    public function getCategoryExpenses( $id )
    {
        return $this->expenseService->getCategories( $id )->expenses;
    }

    public function expensesHistory()
    {
        return ExpenseHistoryCrud::table();
    }
}

