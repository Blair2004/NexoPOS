<?php

namespace App\Http\Controllers;

use App\Crud\CashFlowHistoryCrud;

class BankingController extends Controller
{
    public function cashFlowList()
    {
        return CashFlowHistoryCrud::table();
    }

    public function createCashFlow()
    {
        return CashFlowHistoryCrud::form();
    }
}
