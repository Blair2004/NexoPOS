<?php

namespace Modules\PayTheFly\Http\Controllers;

use App\Http\Controllers\DashboardController;
use App\Models\Order;
use App\Services\Options;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Modules\PayTheFly\Services\PayTheFlyService;

class PayTheFlyController extends DashboardController
{
    public function __construct(
        protected PayTheFlyService $service,
        protected Options $options,
    ) {
        //
    }

    // ─── Settings Page ──────────────────────────────────────────────────

    /**
     * Display the PayTheFly configuration form.
     */
    public function settings()
    {
        return view( 'PayTheFly::settings', [
            'title'   => __( 'PayTheFly Settings' ),
            'service' => $this->service,
            'options' => $this->options,
        ]);
    }

    /**
     * Save the PayTheFly configuration.
     */
    public function saveSettings( Request $request )
    {
        $validated = $request->validate([
            'paythefly_project_id'         => 'required|string|max:255',
            'paythefly_project_key'        => 'required|string|max:255',
            'paythefly_private_key'        => 'required|string|max:255',
            'paythefly_chain'              => 'required|in:BSC,TRON',
            'paythefly_token_address'      => 'nullable|string|max:255',
            'paythefly_verifying_contract' => 'required|string|max:255',
            'paythefly_deadline_minutes'   => 'required|integer|min:5|max:1440',
            'paythefly_enabled'            => 'required|boolean',
        ]);

        foreach ( $validated as $key => $value ) {
            $this->options->set( $key, $value );
        }

        return redirect()
            ->back()
            ->with( 'success', __( 'PayTheFly settings saved successfully.' ) );
    }

    // ─── Payment Link ───────────────────────────────────────────────────

    /**
     * Generate a PayTheFly payment URL for an order and redirect.
     */
    public function pay( Order $order )
    {
        if ( ! $this->service->isEnabled() || ! $this->service->isConfigured() ) {
            return redirect()
                ->back()
                ->with( 'error', __( 'PayTheFly is not configured or is disabled.' ) );
        }

        if ( $order->payment_status === Order::PAYMENT_PAID ) {
            return redirect()
                ->back()
                ->with( 'info', __( 'This order is already paid.' ) );
        }

        try {
            $paymentUrl = $this->service->generatePaymentUrl( $order );
        } catch ( \Throwable $e ) {
            Log::error( 'PayTheFly: Failed to generate payment URL', [
                'order_id' => $order->id,
                'error'    => $e->getMessage(),
            ]);

            return redirect()
                ->back()
                ->with( 'error', __( 'Failed to generate payment link. Please check PayTheFly configuration.' ) );
        }

        return redirect()->away( $paymentUrl );
    }

    /**
     * Return the payment URL as JSON (for API / POS frontend usage).
     */
    public function paymentUrl( Order $order )
    {
        if ( ! $this->service->isEnabled() || ! $this->service->isConfigured() ) {
            return response()->json([
                'status'  => 'error',
                'message' => __( 'PayTheFly is not configured or is disabled.' ),
            ], 422);
        }

        if ( $order->payment_status === Order::PAYMENT_PAID ) {
            return response()->json([
                'status'  => 'info',
                'message' => __( 'This order is already paid.' ),
            ]);
        }

        try {
            $paymentUrl = $this->service->generatePaymentUrl( $order );
        } catch ( \Throwable $e ) {
            Log::error( 'PayTheFly: Failed to generate payment URL', [
                'order_id' => $order->id,
                'error'    => $e->getMessage(),
            ]);

            return response()->json([
                'status'  => 'error',
                'message' => __( 'Failed to generate payment link.' ),
            ], 500);
        }

        return response()->json([
            'status'      => 'success',
            'payment_url' => $paymentUrl,
        ]);
    }

    // ─── Webhook ────────────────────────────────────────────────────────

    /**
     * Handle incoming webhook from PayTheFly.
     *
     * The request body contains:
     *   { "data": "<json string>", "sign": "<hmac hex>", "timestamp": <unix int> }
     *
     * Response MUST contain the word "success" for PayTheFly to acknowledge.
     */
    public function webhook( Request $request )
    {
        $data      = $request->input( 'data', '' );
        $sign      = $request->input( 'sign', '' );
        $timestamp = (int) $request->input( 'timestamp', 0 );

        // ── Verify signature ────────────────────────────────────────
        if ( ! $this->service->verifyWebhookSignature( $data, $sign, $timestamp ) ) {
            Log::warning( 'PayTheFly: Webhook signature verification failed', [
                'ip' => $request->ip(),
            ]);

            return response()->json([
                'status'  => 'error',
                'message' => 'Invalid signature.',
            ], 403);
        }

        // ── Timestamp freshness check (5 minute tolerance) ──────────
        if ( abs( time() - $timestamp ) > 300 ) {
            Log::warning( 'PayTheFly: Webhook timestamp too old', [
                'timestamp' => $timestamp,
                'now'       => time(),
            ]);

            return response()->json([
                'status'  => 'error',
                'message' => 'Timestamp expired.',
            ], 403);
        }

        // ── Decode and process ──────────────────────────────────────
        $payload = json_decode( $data, true );

        if ( ! is_array( $payload ) ) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Invalid data payload.',
            ], 400);
        }

        Log::info( 'PayTheFly: Webhook received', [
            'serial_no' => $payload['serial_no'] ?? 'N/A',
            'tx_hash'   => $payload['tx_hash'] ?? 'N/A',
            'tx_type'   => $payload['tx_type'] ?? 'N/A',
            'confirmed' => $payload['confirmed'] ?? false,
        ]);

        try {
            $result = $this->service->processWebhook( $payload );
        } catch ( \Throwable $e ) {
            Log::error( 'PayTheFly: Webhook processing failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'status'  => 'error',
                'message' => 'Internal processing error.',
            ], 500);
        }

        // PayTheFly requires the response to contain "success"
        return response()->json( $result );
    }

    // ─── Order Status Check ─────────────────────────────────────────────

    /**
     * Check the PayTheFly payment status for an order (poll endpoint for frontend).
     */
    public function status( Order $order )
    {
        $meta = json_decode( $order->note ?? '{}', true ) ?: [];

        return response()->json([
            'status'         => 'success',
            'payment_status' => $order->payment_status,
            'confirmed'      => $meta['paythefly_confirmed'] ?? false,
            'tx_hash'        => $meta['paythefly_tx_hash'] ?? null,
            'chain'          => $meta['paythefly_chain'] ?? null,
        ]);
    }
}
