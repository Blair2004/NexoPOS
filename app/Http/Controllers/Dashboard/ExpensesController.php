<?php

/**
 * NexoPOS Controller
 * @since  1.0
**/

namespace App\Http\Controllers\Dashboard;

use App\Crud\ExpenseCrud;
use App\Crud\CashFlowHistoryCrud;
use App\Http\Controllers\DashboardController;
use App\Models\Expense;
use Illuminate\Http\Request;
use App\Services\ExpenseService;
use App\Services\Options;

class ExpensesController extends DashboardController
{
    /**
     * @var ExpenseService
     */
    private $expenseService;

    /**
     * @var Options
     */
    private $optionsService;

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
            'src'           =>  ns()->url( '/api/nexopos/v4/crud/ns.expenses' ),
            'title'         =>  __( 'Expenses' ),
            'description'   =>  __( 'List all created expenses' ),
            'createUrl'     =>  ns()->url( '/dashboard/expenses/create' )
        ]);
    }

    public function createExpense()
    {
        return ExpenseCrud::form();
    }

    public function editExpense( Expense $expense )
    {
        return ExpenseCrud::form( $expense );
    }

    /**
     * Implement an expense registration
     * @param Request
     * @return json
     */
    public function post( Request $request ) // <= need to add a validation
    {
        $fields     =   $request->only([ 'name', 'active', 'category_id', 'description', 'media_id', 'value' ]);
        return $this->expenseService->create( $fields );
    }

    public function putExpenseCategory( Request $request, $id )
    {
        $fields     =   $request->only([ 'name', 'category_id', 'active', 'description', 'media_id', 'value' ]);
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
        $fields             =   $request->only([ 'name', 'description', 'account', 'operation' ]);
        return $this->expenseService->createAccount( $fields );
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

    public function cashFlowHistory()
    {
        return CashFlowHistoryCrud::table();
    }

    public function createCashFlowHistory()
    {
        return CashFlowHistoryCrud::form();
    }
}

