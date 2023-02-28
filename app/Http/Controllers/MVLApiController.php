<?php

/**
 * NexoPOS Controller
 *
 * @since  1.0
 **/

namespace App\Http\Controllers;

use App\Http\Controllers\Dashboard\Json;
use App\Models\Customer;
use App\Services\CustomerService;
use App\Services\OrdersService;
use App\Services\ProductService;
use Illuminate\Http\Request;
use function App\Http\Controllers\Dashboard\collect;

class MVLApiController extends DashboardController
{
    public function __construct(
        protected CustomerService $customerService,
        protected ProductService $productService,
        protected OrdersService $ordersService,
    )
    {
        parent::__construct();
    }

    public function getCustomers()
    {
        ns()->restrict([ 'read.users' ]);

        return Customer::where('group_id', 1)->get([ 'id', 'first_name', 'last_name' ]);
    }

    public function verifyCustomerPin() {

        return false; // return Customer::get
    }

    public function getProducts() {
        return $this->productService->getProducts();
    }

    public function order(int $id, Request $orderRequest) {
        $order = array(
            'customer_id' => 64,
            'products' => [array(
                'product_id' => 1,
                'unit_quantity_id' => 3,
                'quantity' => 5,
                // 'unit_price' => '1.5',
            )],
            'payment_status' => 'unpaid',
            'type' => array('identifier' => 'takeaway'),
        );
        $this->ordersService->create($order);
    }
}
