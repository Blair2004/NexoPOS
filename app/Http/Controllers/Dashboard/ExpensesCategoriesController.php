<?php

/**
 * NexoPOS Controller
 * @since  1.0
**/

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\DashboardController;
use App\Services\Options;
use Illuminate\Support\Facades\View;

// use Tendoo\Core\Services\Page;

class ExpensesCategoriesController extends DashboardController
{
    public function __construct( Options $options )
    {
        parent::__construct();

        $this->options      =   $options;
    }

    /**
     * Index Controller Page
     * @return  view
     * @since  1.0
    **/
    public function index()
    {
        return View::make( 'NexoPOS::index' );
    }

    /**
     * Show expenses
     * categories
     * @return view
     */
    public function listExpensesCategories()
    {
        return $this->view( 'pages.dashboard.crud.table', [
            'src'           =>  url( 'api/nexopos/v4/crud/ns.expenses-categories' ),
            'title'         =>  __( 'Expenses Categories' ),
            'createUrl'    =>  url( '/dashboard/expenses/categories/create' ),
            'description'   =>  __( 'List all created expenses categories' ),
        ]);
    }

    /**
     * Show expenses
     * categories
     * @return view
     */
    public function createExpenseCategory()
    {
        return $this->view( 'pages.dashboard.crud.form', [
            'src'           =>  url( 'api/nexopos/v4/crud/ns.expenses-categories/form-config' ),
            'title'         =>  __( 'Create New Expense Category' ),
            'returnUrl'    =>  url( '/dashboard/expenses/categories' ),
            'submitUrl'     =>  url( '/api/nexopos/v4/crud/ns.expenses-categories' ),
            'description'   =>  __( 'Register a new expense category on the system.' ),
        ]);
    }
}

