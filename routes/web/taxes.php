<?php

use App\Http\Controllers\Dashboard\TaxesController;
use Illuminate\Support\Facades\Route;

Route::get( '/taxes', [ TaxesController::class, 'listTaxes' ] )->name( ns()->routeName( 'ns.dashboard.taxes' ) );
Route::get( '/taxes/create', [ TaxesController::class, 'createTax' ] )->name( ns()->routeName( 'ns.dashboard.taxes.create' ) );
Route::get( '/taxes/edit/{tax}', [ TaxesController::class, 'editTax' ] )->name( ns()->routeName( 'ns.dashboard.taxes.edit' ) );
Route::get( '/taxes/groups', [ TaxesController::class, 'taxesGroups' ] )->name( ns()->routeName( 'ns.dashboard.taxes.groups' ) );
Route::get( '/taxes/groups/create', [ TaxesController::class, 'createTaxGroups' ] )->name( ns()->routeName( 'ns.dashboard.taxes.groups.create' ) );
Route::get( '/taxes/groups/edit/{group}', [ TaxesController::class, 'editTaxGroup' ] )->name( ns()->routeName( 'ns.dashboard.taxes.groups.edit' ) );
