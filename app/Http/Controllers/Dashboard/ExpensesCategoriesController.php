<?php

/**
 * NexoPOS Controller
 * @since  1.0
**/

namespace App\Http\Controllers\Dashboard;

use App\Crud\ExpenseCategoryCrud;
use App\Http\Controllers\DashboardController;
use App\Models\ExpenseCategory;
use App\Services\CrudService;
use App\Services\Options;
use Illuminate\Support\Facades\View;

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
        return ExpenseCategoryCrud::table();
    }

    /**
     * Show expenses
     * categories
     * @return view
     */
    public function createExpenseCategory()
    {
        return ExpenseCategoryCrud::form();
    }

    public function editExpenseCategory( ExpenseCategory $category )
    {
        return ExpenseCategoryCrud::form( $category );
    }
}

