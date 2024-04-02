<?php

/**
 * NexoPOS Controller
 *
 * @since  1.0
 **/

namespace App\Http\Controllers\Dashboard;

use App\Crud\TransactionAccountCrud;
use App\Http\Controllers\DashboardController;
use App\Models\TransactionAccount;
use Illuminate\Support\Facades\View;

class TransactionsAccountController extends DashboardController
{
    /**
     * Index Controller Page
     *
     * @return view
     *
     * @since  1.0
     **/
    public function index()
    {
        return View::make( 'NexoPOS::index' );
    }

    /**
     * List transactions accounts
     *
     * @return view
     */
    public function listTransactionsAccounts()
    {
        return TransactionAccountCrud::table();
    }

    /**
     *  Show transactions account form.
     *
     * @return view
     */
    public function createTransactionsAccounts()
    {
        return TransactionAccountCrud::form();
    }

    public function editTransactionsAccounts( TransactionAccount $account )
    {
        return TransactionAccountCrud::form( $account );
    }
}
