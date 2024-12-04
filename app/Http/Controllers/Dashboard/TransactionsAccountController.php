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

    public function listTransactionsRules()
    {
        return View::make( 'pages.dashboard.transactions.rules', [
            'title' => __( 'Rules' ),
            'description' => __( 'Manage transactions rules' ),
        ] );
    }
}
