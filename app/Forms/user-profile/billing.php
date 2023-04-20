<?php

use App\Models\CustomerAddress;
use App\Services\CustomerService;
use App\Services\Helper;
use Illuminate\Support\Facades\Auth;

/**
 * @var CustomerService
 */
$customerService    =   app()->make( CustomerService::class );

return [
    'label' => __( 'Biling' ),
    'fields' => $customerService->getAddressFields( CustomerAddress::from( Auth::id(), 'billing' )->first() ),
];
