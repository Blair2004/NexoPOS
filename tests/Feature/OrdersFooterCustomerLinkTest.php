<?php

namespace Tests\Feature;

use Tests\TestCase;

class OrdersFooterCustomerLinkTest extends TestCase
{
    public function test_customer_edit_url_keeps_a_replaceable_placeholder(): void
    {
        $rendered = view( 'pages.dashboard.orders.footer' )->render();

        $this->assertMatchesRegularExpression(
            '/"customer_edit_url":"([^"]+)"/',
            $rendered,
            'The rendered footer must expose a customer_edit_url.'
        );

        preg_match( '/"customer_edit_url":"([^"]+)"/', $rendered, $matches );
        $customerEditUrl = json_decode( '"' . $matches[ 1 ] . '"' );

        $this->assertStringNotContainsString(
            '%23',
            $customerEditUrl,
            'The customer URL must not be URL-encoded before the client-side replacement happens.'
        );

        $this->assertStringEndsWith(
            '/customers/edit/{customer}',
            $customerEditUrl,
            'The customer URL must expose a placeholder the client can replace with the customer id.'
        );
    }
}
