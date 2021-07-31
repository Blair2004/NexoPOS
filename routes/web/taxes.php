<?php

use App\Http\Controllers\Dashboard\TaxesController;
use Illuminate\Support\Facades\Route;

Route::get( '/taxes', [ TaxesController::class, 'listTaxes' ]);
Route::get( '/taxes/create', [ TaxesController::class, 'createTax' ]);
Route::get( '/taxes/edit/{tax}', [ TaxesController::class, 'editTax' ]);
Route::get( '/taxes/groups', [ TaxesController::class, 'taxesGroups' ]);
Route::get( '/taxes/groups/create', [ TaxesController::class, 'createTaxGroups' ]);
Route::get( '/taxes/groups/edit/{group}', [ TaxesController::class, 'editTaxGroup' ]);