<?php

namespace App\Http\Controllers;

use App\Crud\TransactionsHistoryCrud;

/**
 * @deprecated
 */
class BankingController extends Controller
{
    public function transactionsList()
    {
        return TransactionsHistoryCrud::table();
    }

    public function createTransactions()
    {
        return TransactionsHistoryCrud::form();
    }
}
