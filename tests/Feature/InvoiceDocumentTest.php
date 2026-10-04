<?php

namespace Tests\Feature;

use App\Crud\OrderCrud;
use App\Models\Customer;
use App\Models\CustomerGroup;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductUnitQuantity;
use App\Models\Role;
use App\Models\TaxGroup;
use App\Models\Unit;
use App\Models\UnitGroup;
use App\Services\CrudEntry;
use App\Services\OrdersService;
use App\Services\TestService;
use App\Settings\InvoiceSettings;
use App\Settings\PosSettings;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;
use Tests\Traits\WithAuthentication;

class InvoiceDocumentTest extends TestCase
{
    use WithAuthentication;

    /**
     * The test database ships empty (no units, tax groups, customers
     * or products), while the order creation helper relies on all of
     * them. They are seeded idempotently so this file can run
     * standalone as part of the suite.
     */
    protected function setUp(): void
    {
        parent::setUp();

        /**
         * Authenticate as an admin for every test: building the POS
         * settings form may resolve PaymentTypeCrud::getFormConfig()
         * (when cash registers are enabled), which performs a
         * permission check that guests always fail.
         */
        $this->attemptAuthenticate();

        /**
         * The test database is shared between runs: reset every option
         * this suite mutates so a mid-test failure can never cascade
         * into the following tests.
         */
        collect( [
            'ns_invoice_show_store_details',
            'ns_invoice_show_billing_details',
            'ns_invoice_show_shipping_details',
            'ns_invoice_show_unit_price',
            'ns_invoice_show_discount',
            'ns_invoice_show_tax_column',
            'ns_invoice_hide_na_values',
            'ns_invoice_show_subtotal',
            'ns_invoice_merge_similar_products',
            'ns_invoice_logo',
            'ns_invoice_footer',
            'ns_invoice_column_a',
            'ns_invoice_column_b',
            'ns_invoice_font_scale',
            'ns_invoice_receipt_font_scale',
            'ns_invoice_receipt_footer',
            'ns_pos_printing_document',
            'ns_store_address',
            'ns_store_email',
        ] )->each( fn( $key ) => ns()->option->delete( $key ) );

        if ( UnitGroup::count() === 0 ) {
            UnitGroup::factory()->create();
        }

        if ( Unit::count() === 0 ) {
            Unit::factory()->create( [
                'group_id' => UnitGroup::first()->id,
                'identifier' => 'unit-test',
            ] );
        }

        if ( TaxGroup::count() === 0 ) {
            TaxGroup::factory()->create();
        }

        if ( CustomerGroup::count() === 0 ) {
            CustomerGroup::factory()->create();
        }

        if ( Customer::count() === 0 ) {
            Customer::factory()->create();
        }

        $hasProduct = Product::where( 'tax_group_id', '>', 0 )
            ->whereRelation( 'unit_quantities', 'quantity', '>', 100 )
            ->exists();

        if ( ! $hasProduct ) {
            $product = Product::factory()->create( [
                'stock_management' => 'disabled',
            ] );

            ProductUnitQuantity::factory()->create( [
                'product_id' => $product->id,
                'unit_id' => Unit::first()->id,
                'quantity' => 500,
            ] );
        }
    }

    /**
     * Creates an order to render the invoice against. Follows the
     * same strategy as Tests\Traits\WithCombinedProductTest: an order
     * containing the same product twice so the merge behavior can
     * be asserted.
     */
    protected function createOrder(): Order
    {
        $testService = new TestService;
        $orderDetails = $testService->prepareOrder(
            date: ns()->date->now(),
            orderDetails: [],
            productDetails: [],
            config: [
                'products' => function () {
                    $product = Product::where( 'tax_group_id', '>', 0 )
                        ->whereRelation( 'unit_quantities', 'quantity', '>', 100 )
                        ->with( 'unit_quantities', fn( $query ) => $query->where( 'quantity', '>', 100 ) )
                        ->first();

                    return collect( [ $product, $product ] );
                },
                'allow_quick_products' => false,
            ]
        );

        Sanctum::actingAs(
            Role::namespace( 'admin' )->users->first(),
            ['*']
        );

        $response = $this->withSession( $this->app[ 'session' ]->all() )
            ->json( 'POST', 'api/orders', $orderDetails );

        $response->assertStatus( 200 );

        $json = json_decode( $response->getContent() );

        return Order::find( $json->data->order->id );
    }

    /**
     * The dashboard invoice must honor the invoice branding options
     * (logo, footer, column A/B with tag mapping).
     */
    public function test_invoice_renders_with_branding_options()
    {
        ns()->option->set( 'ns_invoice_logo', 'https://example.com/invoice-logo.png' );
        ns()->option->set( 'ns_invoice_footer', 'Invoice footer marker' );
        ns()->option->set( 'ns_invoice_column_a', 'Order: {order_code}' );

        $order = $this->createOrder();

        $response = $this->get( '/dashboard/orders/invoice/' . $order->id );

        $response->assertStatus( 200 );
        $response->assertSee( 'https://example.com/invoice-logo.png', false );
        $response->assertSee( 'Invoice footer marker', false );
        $response->assertSee( 'Order: ' . $order->code, false );
        $response->assertSee( 'Billing Details', false );

        // Invoice meta panel (Invoice Ninja style).
        $response->assertSee( 'Invoice Number', false );
        $response->assertSee( 'Invoice Date', false );
        $response->assertSee( 'Invoice Total', false );

        // Fully paid orders must show a highlighted Balance Due of 0.00.
        $response->assertSee( 'Balance Due', false );

        ns()->option->delete( 'ns_invoice_logo' );
        ns()->option->delete( 'ns_invoice_footer' );
        ns()->option->delete( 'ns_invoice_column_a' );
    }

    /**
     * The naked invoice (print target) extends the base layout and
     * triggers window.print() only when autoprint is requested.
     */
    public function test_naked_invoice_supports_autoprint()
    {
        $order = $this->createOrder();

        $response = $this->get( '/dashboard/orders/invoice/' . $order->id . '?dash-visibility=disabled' );

        $response->assertStatus( 200 );
        $response->assertDontSee( 'dashboard-content' );
        $response->assertDontSee( 'window.print' );

        $response = $this->get( '/dashboard/orders/invoice/' . $order->id . '?dash-visibility=disabled&autoprint=true' );

        $response->assertStatus( 200 );
        $response->assertDontSee( 'dashboard-content' );
        $response->assertSee( 'window.print', false );
    }

    /**
     * The invoice visibility options must gate their respective
     * blocks and columns.
     */
    public function test_invoice_honors_visibility_toggles()
    {
        $order = $this->createOrder();

        ns()->option->set( 'ns_invoice_show_billing_details', 'no' );
        ns()->option->set( 'ns_invoice_show_unit_price', 'no' );

        $response = $this->get( '/dashboard/orders/invoice/' . $order->id );

        $response->assertStatus( 200 );
        $response->assertDontSee( 'Billing Details', false );
        $response->assertDontSee( 'Unit Price', false );
        $response->assertSee( 'Shipping Details', false );

        ns()->option->set( 'ns_invoice_show_billing_details', 'yes' );
        ns()->option->set( 'ns_invoice_show_unit_price', 'yes' );

        $response = $this->get( '/dashboard/orders/invoice/' . $order->id );

        $response->assertStatus( 200 );
        $response->assertSee( 'Billing Details', false );
        $response->assertSee( 'Unit Price', false );
    }

    /**
     * With "Merge Products On Receipt/Invoice" enabled, the invoice
     * renders combined products (one row per similar product group).
     */
    public function test_invoice_merges_similar_products()
    {
        ns()->option->set( 'ns_invoice_merge_similar_products', 'yes' );

        $order = $this->createOrder();

        $this->assertEquals(
            1,
            $order->combinedProducts->count(),
            __( 'The product weren\'t combined.' )
        );

        $response = $this->get( '/dashboard/orders/invoice/' . $order->id );

        $response->assertStatus( 200 );

        $productName = $order->products->first()->name;

        $this->assertEquals(
            $order->combinedProducts->count(),
            substr_count( $response->getContent(), $productName ),
            __( 'The invoice doesn\'t display the combined products.' )
        );

        ns()->option->set( 'ns_invoice_merge_similar_products', 'no' );
    }

    /**
     * The sale printing URL must follow the "Print Selection"
     * (ns_pos_printing_document) setting, while the explicit
     * receipt/invoice variants remain stable.
     */
    public function test_printing_urls_follow_selected_document()
    {
        $service = app()->make( OrdersService::class );

        // Unset option falls back to the receipt.
        ns()->option->delete( 'ns_pos_printing_document' );

        $urls = $service->getPrintingUrls();

        $this->assertStringContainsString( '/dashboard/orders/receipt/{reference_id}', $urls[ 'sale_printing_url' ] );
        $this->assertStringContainsString( '/dashboard/orders/receipt/{reference_id}', $urls[ 'sale_receipt_printing_url' ] );
        $this->assertStringContainsString( '/dashboard/orders/invoice/{reference_id}', $urls[ 'sale_invoice_printing_url' ] );

        // Regression: the payment URL must point at the payment receipt
        // (it previously pointed at the order receipt on the orders list).
        $this->assertStringContainsString( '/dashboard/orders/payment-receipt/{reference_id}', $urls[ 'payment_printing_url' ] );
        $this->assertStringContainsString( '/dashboard/orders/refund-receipt/{reference_id}', $urls[ 'refund_printing_url' ] );
        $this->assertStringContainsString( '/dashboard/cash-registers/z-report/{reference_id}', $urls[ 'z_report_printing_url' ] );

        foreach ( $urls as $url ) {
            $this->assertStringContainsString( '{reference_id}', $url );
        }

        ns()->option->set( 'ns_pos_printing_document', 'invoice' );

        $urls = $service->getPrintingUrls();

        $this->assertStringContainsString( '/dashboard/orders/invoice/{reference_id}', $urls[ 'sale_printing_url' ] );

        ns()->option->set( 'ns_pos_printing_document', 'receipt' );

        $urls = $service->getPrintingUrls();

        $this->assertStringContainsString( '/dashboard/orders/receipt/{reference_id}', $urls[ 'sale_printing_url' ] );

        ns()->option->delete( 'ns_pos_printing_document' );
    }

    /**
     * The invoice settings page exposes the new "Display"/"Invoices"
     * tabs and the POS printing tab exposes the post-sale choice
     * toggle (defaulting to "no").
     */
    public function test_print_choice_and_invoice_settings_exist()
    {
        $invoiceTabs = ( new InvoiceSettings )->getForm()[ 'tabs' ];

        $this->assertArrayHasKey( 'display', $invoiceTabs );
        $this->assertArrayHasKey( 'receipts', $invoiceTabs );
        $this->assertArrayHasKey( 'invoices', $invoiceTabs );

        $invoiceFieldNames = collect( $invoiceTabs[ 'invoices' ][ 'fields' ] )->pluck( 'name' );

        foreach ( [
            'ns_invoice_template',
            'ns_invoice_logo',
            'ns_invoice_footer',
            'ns_invoice_column_a',
            'ns_invoice_column_b',
            'ns_invoice_show_store_details',
            'ns_invoice_show_billing_details',
            'ns_invoice_show_shipping_details',
            'ns_invoice_show_unit_price',
            'ns_invoice_show_discount',
            'ns_invoice_show_tax_column',
        ] as $field ) {
            $this->assertTrue( $invoiceFieldNames->contains( $field ), sprintf( 'The field "%s" is missing.', $field ) );
        }

        $displayFieldNames = collect( $invoiceTabs[ 'display' ][ 'fields' ] )->pluck( 'name' );

        // The shared toggles moved from the Receipts tab.
        foreach ( [
            'ns_invoice_merge_similar_products',
            'ns_invoice_display_tax_breakdown',
            'ns_invoice_show_product_unit',
            'ns_invoice_show_subtotal',
            'ns_invoice_show_payment_rows',
            'ns_invoice_show_change_due',
        ] as $field ) {
            $this->assertTrue( $displayFieldNames->contains( $field ), sprintf( 'The field "%s" is missing.', $field ) );
        }

        $receiptFieldNames = collect( $invoiceTabs[ 'receipts' ][ 'fields' ] )->pluck( 'name' );

        $this->assertTrue( $receiptFieldNames->contains( 'ns_invoice_receipt_font_scale' ) );
        $this->assertTrue( $invoiceFieldNames->contains( 'ns_invoice_font_scale' ) );

        $printingFields = collect( ( new PosSettings )->getForm()[ 'tabs' ][ 'printing' ][ 'fields' ] )->pluck( 'name' );

        $this->assertTrue( $printingFields->contains( 'ns_pos_printing_document_choice' ) );
        $this->assertEquals( 'no', ns()->option->get( 'ns_pos_printing_document_choice', 'no' ) );
    }

    /**
     * The POS page must expose the post-sale choice option to the
     * frontend (it's part of a curated options list, which is easy
     * to break silently).
     */
    public function test_pos_page_exposes_print_choice_option()
    {
        Sanctum::actingAs(
            Role::namespace( 'admin' )->users->first(),
            ['*']
        );

        $response = $this->withSession( $this->app[ 'session' ]->all() )
            ->get( '/dashboard/pos' );

        $response->assertStatus( 200 );
        $response->assertSee( 'ns_pos_printing_document_choice', false );
        $response->assertSee( 'sale_invoice_printing_url', false );
    }

    /**
     * The order list "Options -> Invoice" row action must open the
     * customizable invoice route (GOTO to /dashboard/orders/invoice/{id}).
     */
    public function test_order_list_invoice_action_points_to_invoice_route()
    {
        $order = $this->createOrder();

        $entry = new CrudEntry( [ 'id' => $order->id ] );
        $entry->__raw = (object) [ 'payment_status' => Order::PAYMENT_PAID ];

        $entry = ( new OrderCrud )->setActions( $entry );
        $actions = $entry->toArray()[ '$actions' ];

        $this->assertArrayHasKey( 'invoice', $actions );
        $this->assertEquals( 'GOTO', $actions[ 'invoice' ][ 'type' ] );
        $this->assertStringContainsString(
            '/dashboard/orders/invoice/' . $order->id,
            $actions[ 'invoice' ][ 'url' ]
        );
    }

    /**
     * With "Hide N/A Fields" enabled, empty fields are removed and a
     * block made only of empty fields is hidden entirely.
     */
    public function test_invoice_hides_na_values_when_enabled()
    {
        $order = $this->createOrder();

        // Empty the addresses so every billing/shipping field is N/A.
        foreach ( [ 'billing_address', 'shipping_address' ] as $relation ) {
            $address = $order->{ $relation };

            if ( $address ) {
                foreach ( [ 'first_name', 'last_name', 'country' ] as $column ) {
                    $address->{ $column } = null;
                }

                $address->save();
            }
        }

        ns()->option->set( 'ns_invoice_hide_na_values', 'yes' );

        $response = $this->get( '/dashboard/orders/invoice/' . $order->id );

        $response->assertStatus( 200 );
        $response->assertDontSee( '>N/A</span>', false );
        $response->assertDontSee( 'Billing Details', false );
        $response->assertDontSee( 'Shipping Details', false );

        ns()->option->set( 'ns_invoice_hide_na_values', 'no' );

        $response = $this->get( '/dashboard/orders/invoice/' . $order->id );

        $response->assertStatus( 200 );
        $response->assertSee( 'Billing Details', false );
        $response->assertSee( '>N/A</span>', false );

        ns()->option->delete( 'ns_invoice_hide_na_values' );
    }

    /**
     * The invoice header always renders the store information next to
     * the logo (alongside custom Column A/B content), and the store
     * address/city/pobox tags resolve through the template mapping.
     */
    public function test_invoice_shows_default_store_information()
    {
        $originalAddress = ns()->option->get( 'ns_store_address' );
        $originalEmail = ns()->option->get( 'ns_store_email' );

        ns()->option->set( 'ns_store_address', '12 Test Street' );
        ns()->option->set( 'ns_store_email', 'store@example.com' );
        ns()->option->delete( 'ns_invoice_column_a' );
        ns()->option->delete( 'ns_invoice_column_b' );

        $order = $this->createOrder();

        $response = $this->get( '/dashboard/orders/invoice/' . $order->id );

        $response->assertStatus( 200 );
        $response->assertSee( '12 Test Street', false );
        $response->assertSee( 'store@example.com', false );

        // Custom columns coexist with the store information, and the
        // new {store_address} tag resolves through the mapping.
        ns()->option->set( 'ns_invoice_column_a', 'Addr: {store_address}' );

        $response = $this->get( '/dashboard/orders/invoice/' . $order->id );

        $response->assertStatus( 200 );
        $response->assertSee( '>12 Test Street</p>', false );
        $response->assertSee( 'Addr: 12 Test Street', false );

        // Restore the original store information.
        ns()->option->delete( 'ns_invoice_column_a' );

        if ( $originalAddress === null ) {
            ns()->option->delete( 'ns_store_address' );
        } else {
            ns()->option->set( 'ns_store_address', $originalAddress );
        }

        if ( $originalEmail === null ) {
            ns()->option->delete( 'ns_store_email' );
        } else {
            ns()->option->set( 'ns_store_email', $originalEmail );
        }
    }

    /**
     * The invoice work and the Display settings tab must not break the
     * receipt document: it renders its own template, honors the
     * receipt-specific options and still reacts to the shared toggles.
     */
    public function test_receipt_renders_with_receipt_and_shared_options()
    {
        $order = $this->createOrder();

        ns()->option->set( 'ns_invoice_receipt_footer', 'Receipt footer marker' );
        ns()->option->set( 'ns_invoice_show_subtotal', 'yes' );

        $response = $this->get( '/dashboard/orders/receipt/' . $order->id );

        $response->assertStatus( 200 );
        $response->assertSee( $order->products->first()->name, false );
        $response->assertSee( 'Receipt footer marker', false );
        $response->assertSee( 'Sub Total', false );
        $response->assertSee( 'dashboard-content', false );

        // The naked receipt (print target) still works.
        $response = $this->get( '/dashboard/orders/receipt/' . $order->id . '?dash-visibility=disabled' );

        $response->assertStatus( 200 );
        $response->assertDontSee( 'dashboard-content' );
        $response->assertSee( 'Receipt footer marker', false );

        // The shared toggle moved to the Display tab but must still
        // drive the receipt subtotal row.
        ns()->option->set( 'ns_invoice_show_subtotal', 'no' );

        $response = $this->get( '/dashboard/orders/receipt/' . $order->id );

        $response->assertStatus( 200 );
        $response->assertDontSee( 'Sub Total', false );

        ns()->option->delete( 'ns_invoice_receipt_footer' );
        ns()->option->delete( 'ns_invoice_show_subtotal' );
    }

    /**
     * The font scale selects must apply zoom + width compensation to
     * both documents (fonts scale, printed footprint stays stable).
     */
    public function test_font_scale_options_scale_documents()
    {
        $order = $this->createOrder();

        ns()->option->set( 'ns_invoice_font_scale', 130 );
        ns()->option->set( 'ns_invoice_receipt_font_scale', 150 );

        $response = $this->get( '/dashboard/orders/invoice/' . $order->id );

        $response->assertStatus( 200 );
        $response->assertSee( 'zoom: 130%', false );
        $response->assertSee( 'calc(210mm * 100 / 130)', false );

        $response = $this->get( '/dashboard/orders/receipt/' . $order->id );

        $response->assertStatus( 200 );
        $response->assertSee( 'zoom: 150%', false );
        $response->assertSee( 'calc(50% * 100 / 150)', false );

        ns()->option->delete( 'ns_invoice_font_scale' );
        ns()->option->delete( 'ns_invoice_receipt_font_scale' );
    }
}
