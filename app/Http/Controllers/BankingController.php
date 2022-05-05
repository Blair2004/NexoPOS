<?php

namespace App\Http\Controllers;

use App\Crud\CashFlowHistoryCrud;
use App\Models\CashFlow;
use Illuminate\Http\Request;

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
