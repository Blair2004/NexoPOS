<?php

namespace App\Console\Commands;

use App\Models\Customer;
use Illuminate\Console\Command;

/**
 * Liên kết mã người dùng Zalo (user_id) với một khách hàng.
 *
 * Mã Zalo user ID có được khi khách theo dõi OA: Zalo gửi sự kiện `follow`
 * về webhook và ghi mã này vào log (xem docs/zalo-oa.md). Sau khi liên kết,
 * khách sẽ nhận được xác nhận đơn, trạng thái món, hóa đơn và khuyến mãi
 * qua Zalo OA.
 *
 * Ví dụ:
 *  php artisan ns:zalo-link-customer 42 123456789012345
 */
class ZaloLinkCustomerCommand extends Command
{
    protected $signature = 'ns:zalo-link-customer
        {customer_id : ID của khách hàng}
        {zalo_user_id : Mã người dùng Zalo (user_id) của khách}';

    protected $description = 'Link a Zalo user ID to a customer so they can receive Zalo OA notifications.';

    public function handle(): int
    {
        $customer = Customer::find( $this->argument( 'customer_id' ) );

        if ( ! $customer ) {
            $this->error( __( 'Customer not found.' ) );

            return self::FAILURE;
        }

        $customer->zalo_user_id = (string) $this->argument( 'zalo_user_id' );
        $customer->save();

        $this->info( __( 'Zalo user ID %s has been linked to customer %s.', [
            $customer->zalo_user_id,
            $customer->id,
        ] ) );

        return self::SUCCESS;
    }
}
