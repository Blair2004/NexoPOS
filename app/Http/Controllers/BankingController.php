<?php

namespace App\Http\Controllers;

use App\Crud\TransactionsHistoryCrud;

class BankingController extends Controller
{
    public function cashFlowList()
    {
        return TransactionsHistoryCrud::table();
    }

    public function createCashFlow()
    {
        return TransactionsHistoryCrud::form();
    }
}
