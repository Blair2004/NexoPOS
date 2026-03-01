<?php

namespace Modules\PayTheFly\Services;

use App\Models\Order;
use App\Models\OrderPayment;
use App\Services\Options;
use kornrunner\Keccak;
use Modules\PayTheFly\Events\PayTheFlyPaymentConfirmedEvent;
use Modules\PayTheFly\Models\PayTheFlyTransaction;
use RuntimeException;

class PayTheFlyService
{
    // ─── Chain Configuration ────────────────────────────────────────────
    public const CHAINS = [
        'BSC' => [
            'chain_id'     => 56,
            'decimals'     => 18,
            'native_token' => '0x0000000000000000000000000000000000000000',
            'symbol'       => 'BNB',
        ],
        'TRON' => [
            'chain_id'     => 728126428,
            'decimals'     => 6,
            'native_token' => 'T9yD14Nj9j7xAB4dbGeiX9h8unkKHxuWwb',
            'symbol'       => 'TRX',
        ],
    ];

    // ─── Payment Type Identifier ────────────────────────────────────────
    public const PAYMENT_IDENTIFIER = 'paythefly-crypto';

    // ─── EIP-712 Domain Constants ───────────────────────────────────────
    private const EIP712_DOMAIN_NAME    = 'PayTheFlyPro';
    private const EIP712_DOMAIN_VERSION = '1';

    // ─── Options Keys ───────────────────────────────────────────────────
    public const OPT_PROJECT_ID      = 'paythefly_project_id';
    public const OPT_PROJECT_KEY     = 'paythefly_project_key';
    public const OPT_PRIVATE_KEY     = 'paythefly_private_key';
    public const OPT_CHAIN           = 'paythefly_chain';
    public const OPT_TOKEN_ADDRESS   = 'paythefly_token_address';
    public const OPT_CONTRACT        = 'paythefly_verifying_contract';
    public const OPT_DEADLINE_MINS   = 'paythefly_deadline_minutes';
    public const OPT_ENABLED         = 'paythefly_enabled';

    protected Options $options;

    public function __construct()
    {
        $this->options = app()->make( Options::class );
    }

    // ─── Configuration Helpers ──────────────────────────────────────────

    /**
     * Check if the module is fully configured.
     */
    public function isConfigured(): bool
    {
        return ! empty( $this->getProjectId() )
            && ! empty( $this->getProjectKey() )
            && ! empty( $this->getPrivateKey() )
            && ! empty( $this->getVerifyingContract() );
    }

    public function isEnabled(): bool
    {
        return (bool) $this->options->get( self::OPT_ENABLED, false );
    }

    public function getProjectId(): string
    {
        return (string) $this->options->get( self::OPT_PROJECT_ID, '' );
    }

    public function getProjectKey(): string
    {
        return (string) $this->options->get( self::OPT_PROJECT_KEY, '' );
    }

    public function getPrivateKey(): string
    {
        return (string) $this->options->get( self::OPT_PRIVATE_KEY, '' );
    }

    public function getChainKey(): string
    {
        return (string) $this->options->get( self::OPT_CHAIN, 'BSC' );
    }

    public function getChainConfig(): array
    {
        $key = $this->getChainKey();

        return self::CHAINS[ $key ] ?? self::CHAINS['BSC'];
    }

    public function getTokenAddress(): string
    {
        $custom = (string) $this->options->get( self::OPT_TOKEN_ADDRESS, '' );
        if ( $custom !== '' ) {
            return $custom;
        }

        return $this->getChainConfig()['native_token'];
    }

    public function getVerifyingContract(): string
    {
        return (string) $this->options->get( self::OPT_CONTRACT, '' );
    }

    public function getDeadlineMinutes(): int
    {
        return max( 5, (int) $this->options->get( self::OPT_DEADLINE_MINS, 30 ) );
    }

    // ─── Payment Link Generation ────────────────────────────────────────

    /**
     * Build a PayTheFly Pro payment URL for the given order.
     *
     * @return string The full payment URL
     */
    public function generatePaymentUrl( Order $order ): string
    {
        $chain    = $this->getChainConfig();
        $chainId  = $chain['chain_id'];
        $decimals = $chain['decimals'];

        $serialNo = $this->buildSerialNo( $order );
        $amount   = $this->toSmallestUnit( (float) $order->total, $decimals );
        $deadline = now()->addMinutes( $this->getDeadlineMinutes() )->timestamp;

        $signature = $this->signPaymentRequest(
            projectId: $this->getProjectId(),
            token:     $this->getTokenAddress(),
            amount:    $amount,
            serialNo:  $serialNo,
            deadline:  $deadline,
            chainId:   $chainId,
        );

        // Human-readable amount for the URL (e.g. 0.01)
        $humanAmount = $this->toHumanAmount( $amount, $decimals );

        return 'https://pro.paythefly.com/pay?' . http_build_query([
            'chainId'   => $chainId,
            'projectId' => $this->getProjectId(),
            'amount'    => $humanAmount,
            'serialNo'  => $serialNo,
            'deadline'  => $deadline,
            'signature' => $signature,
            'token'     => $this->getTokenAddress(),
        ]);
    }

    /**
     * Generate a unique serial number from an order.
     */
    public function buildSerialNo( Order $order ): string
    {
        return 'NXPOS-' . $order->id . '-' . $order->code;
    }

    /**
     * Parse the order ID back from a serial number.
     */
    public function parseOrderIdFromSerialNo( string $serialNo ): ?int
    {
        if ( preg_match( '/^NXPOS-(\d+)-/', $serialNo, $m ) ) {
            return (int) $m[1];
        }

        return null;
    }

    // ─── Webhook Verification ───────────────────────────────────────────

    /**
     * Verify the HMAC signature of an incoming webhook payload.
     *
     * @param string $data      Raw JSON string from the "data" field
     * @param string $sign      Hex-encoded HMAC from the "sign" field
     * @param int    $timestamp Unix timestamp from the "timestamp" field
     *
     * @return bool
     */
    public function verifyWebhookSignature( string $data, string $sign, int $timestamp ): bool
    {
        $message  = $data . '.' . $timestamp;
        $expected = hash_hmac( 'sha256', $message, $this->getProjectKey() );

        return hash_equals( $expected, $sign );
    }

    /**
     * Process a verified webhook payload and update the order.
     *
     * @param array $payload Decoded webhook data array
     *
     * @return array{status: string, message: string, order_id?: int}
     */
    public function processWebhook( array $payload ): array
    {
        // tx_type 1 = payment, 2 = withdrawal; we only handle payments
        $txType = (int) ( $payload['tx_type'] ?? 0 );
        if ( $txType !== 1 ) {
            return [
                'status'  => 'success',
                'message' => 'Non-payment transaction ignored.',
            ];
        }

        $serialNo  = $payload['serial_no'] ?? '';
        $confirmed = (bool) ( $payload['confirmed'] ?? false );
        $txHash    = $payload['tx_hash'] ?? '';
        $value     = $payload['value'] ?? '0';
        $fee       = $payload['fee'] ?? '0';

        $orderId = $this->parseOrderIdFromSerialNo( $serialNo );

        if ( $orderId === null ) {
            return [
                'status'  => 'error',
                'message' => 'Unable to parse order from serial_no.',
            ];
        }

        $order = Order::find( $orderId );

        if ( ! $order ) {
            return [
                'status'  => 'error',
                'message' => 'Order not found.',
            ];
        }

        // ── Record transaction in dedicated table ────────────────
        PayTheFlyTransaction::updateOrCreate(
            [ 'tx_hash' => $txHash, 'order_id' => $order->id ],
            [
                'serial_no'    => $serialNo,
                'chain_symbol' => $payload['chain_symbol'] ?? '',
                'wallet'       => $payload['wallet'] ?? '',
                'value'        => $value,
                'fee'          => $fee,
                'tx_type'      => $txType,
                'confirmed'    => $confirmed,
                'project_id'   => $payload['project_id'] ?? '',
                'raw_payload'  => json_encode( $payload, JSON_UNESCAPED_SLASHES ),
            ]
        );

        // Store tx hash in order note for quick reference
        $meta = json_decode( $order->note ?? '{}', true ) ?: [];
        $meta['paythefly_tx_hash']      = $txHash;
        $meta['paythefly_confirmed']    = $confirmed;
        $meta['paythefly_chain']        = $payload['chain_symbol'] ?? '';
        $meta['paythefly_wallet']       = $payload['wallet'] ?? '';
        $meta['paythefly_value']        = $value;
        $meta['paythefly_fee']          = $fee;
        $meta['paythefly_webhook_at']   = now()->toIso8601String();
        $order->note = json_encode( $meta, JSON_UNESCAPED_SLASHES );

        if ( $confirmed ) {
            // Record the payment via NexoPOS's OrderPayment model
            $existingPayment = OrderPayment::where( 'order_id', $order->id )
                ->where( 'identifier', self::PAYMENT_IDENTIFIER )
                ->first();

            if ( ! $existingPayment ) {
                $orderPayment             = new OrderPayment;
                $orderPayment->order_id   = $order->id;
                $orderPayment->identifier = self::PAYMENT_IDENTIFIER;
                $orderPayment->value      = (float) $order->total;
                $orderPayment->author     = $order->author;
                $orderPayment->save();
            }

            // Update payment status
            if ( $order->payment_status !== Order::PAYMENT_PAID ) {
                $order->payment_status = Order::PAYMENT_PAID;
                $order->tendered       = (float) $order->total;
                $order->change         = 0;
            }

            // Dispatch event so other modules can react
            $transaction = PayTheFlyTransaction::where( 'tx_hash', $txHash )
                ->where( 'order_id', $order->id )
                ->first();

            if ( $transaction ) {
                PayTheFlyPaymentConfirmedEvent::dispatch( $order, $transaction );
            }
        }

        $order->save();

        return [
            'status'   => 'success',
            'message'  => $confirmed ? 'Payment confirmed.' : 'Payment pending confirmation.',
            'order_id' => $orderId,
        ];
    }

    // ─── EIP-712 Signature ──────────────────────────────────────────────

    /**
     * Sign a PaymentRequest using EIP-712 typed data.
     *
     * @return string Hex-encoded signature prefixed with 0x
     */
    public function signPaymentRequest(
        string $projectId,
        string $token,
        string $amount,
        string $serialNo,
        int    $deadline,
        int    $chainId,
    ): string {
        $this->ensureKeccakAvailable();

        $privateKey = $this->getPrivateKey();
        if ( empty( $privateKey ) ) {
            throw new RuntimeException( 'PayTheFly private key is not configured.' );
        }

        // Strip 0x prefix if present
        if ( str_starts_with( $privateKey, '0x' ) || str_starts_with( $privateKey, '0X' ) ) {
            $privateKey = substr( $privateKey, 2 );
        }

        // ── Domain separator ────────────────────────────────────────
        $domainTypeHash = $this->keccak256(
            'EIP712Domain(string name,string version,uint256 chainId,address verifyingContract)'
        );

        $domainSeparator = $this->keccak256Packed(
            $domainTypeHash
            . $this->keccak256( self::EIP712_DOMAIN_NAME )
            . $this->keccak256( self::EIP712_DOMAIN_VERSION )
            . $this->abiEncodeUint256( $chainId )
            . $this->abiEncodeAddress( $this->getVerifyingContract() )
        );

        // ── Struct hash ─────────────────────────────────────────────
        $paymentRequestTypeHash = $this->keccak256(
            'PaymentRequest(string projectId,address token,uint256 amount,string serialNo,uint256 deadline)'
        );

        $structHash = $this->keccak256Packed(
            $paymentRequestTypeHash
            . $this->keccak256( $projectId )
            . $this->abiEncodeAddress( $token )
            . $this->abiEncodeUint256( $amount )
            . $this->keccak256( $serialNo )
            . $this->abiEncodeUint256( $deadline )
        );

        // ── Final digest ────────────────────────────────────────────
        $digest = $this->keccak256Packed(
            hex2bin( '1901' ) . $domainSeparator . $structHash
        );

        // ── ECDSA sign ──────────────────────────────────────────────
        $ec       = new \Elliptic\EC( 'secp256k1' );
        $key      = $ec->keyFromPrivate( $privateKey, 'hex' );
        $sig      = $key->sign( bin2hex( $digest ), [ 'canonical' => true ] );

        $r = str_pad( $sig->r->toString( 16 ), 64, '0', STR_PAD_LEFT );
        $s = str_pad( $sig->s->toString( 16 ), 64, '0', STR_PAD_LEFT );
        $v = dechex( $sig->recoveryParam + 27 );

        return '0x' . $r . $s . $v;
    }

    // ─── Keccak-256 Helpers ─────────────────────────────────────────────

    /**
     * Ensure kornrunner/keccak is available.
     * PHP's native hash('sha3-256') is NOT the same as Keccak-256!
     */
    private function ensureKeccakAvailable(): void
    {
        if ( ! class_exists( \kornrunner\Keccak::class ) ) {
            throw new RuntimeException(
                'PayTheFly requires kornrunner/keccak. '
                . 'Run: composer require kornrunner/keccak inside the PayTheFly module directory.'
            );
        }
    }

    /**
     * Keccak-256 hash of a UTF-8 string, returns raw 32 bytes.
     */
    private function keccak256( string $input ): string
    {
        return hex2bin( Keccak::hash( $input, 256 ) );
    }

    /**
     * Keccak-256 hash of a packed (binary) input, returns raw 32 bytes.
     */
    private function keccak256Packed( string $binaryInput ): string
    {
        return hex2bin( Keccak::hash( $binaryInput, 256, true ) );
    }

    /**
     * ABI-encode a uint256 value as 32 bytes.
     */
    private function abiEncodeUint256( int|string $value ): string
    {
        $hex = gmp_strval( gmp_init( (string) $value, 10 ), 16 );

        return hex2bin( str_pad( $hex, 64, '0', STR_PAD_LEFT ) );
    }

    /**
     * ABI-encode an address as 32 bytes (left-padded).
     */
    private function abiEncodeAddress( string $address ): string
    {
        // Handle TRON base58 addresses — convert to hex if needed
        if ( str_starts_with( $address, 'T' ) && strlen( $address ) === 34 ) {
            // TRON address: strip the 0x41 prefix and zero-pad
            $address = $this->tronAddressToHex( $address );
        }

        $hex = str_replace( '0x', '', strtolower( $address ) );

        return hex2bin( str_pad( $hex, 64, '0', STR_PAD_LEFT ) );
    }

    /**
     * Convert a TRON base58check address to a 20-byte hex address (without prefix).
     */
    private function tronAddressToHex( string $base58 ): string
    {
        $alphabet = '123456789ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz';
        $base     = gmp_init( 58 );
        $num      = gmp_init( 0 );

        for ( $i = 0; $i < strlen( $base58 ); $i++ ) {
            $num = gmp_add(
                gmp_mul( $num, $base ),
                gmp_init( strpos( $alphabet, $base58[ $i ] ) )
            );
        }

        $hex = gmp_strval( $num, 16 );
        // Full decoded: 1 byte version + 20 bytes address + 4 bytes checksum = 25 bytes = 50 hex chars
        $hex = str_pad( $hex, 50, '0', STR_PAD_LEFT );

        // Strip version byte (41 for TRON mainnet) and 4-byte checksum
        return substr( $hex, 2, 40 );
    }

    // ─── Amount Conversion ──────────────────────────────────────────────

    /**
     * Convert a human-readable amount to smallest unit (wei / sun).
     *
     * @param float $amount   Human-readable amount (e.g. 1.5)
     * @param int   $decimals Token decimals (18 for BSC native, 6 for TRON native)
     *
     * @return string Amount in smallest unit as string to avoid float precision issues
     */
    public function toSmallestUnit( float $amount, int $decimals ): string
    {
        return bcmul( (string) $amount, bcpow( '10', (string) $decimals, 0 ), 0 );
    }

    /**
     * Convert smallest unit back to human-readable amount.
     */
    public function toHumanAmount( string $smallestUnit, int $decimals ): string
    {
        return bcdiv( $smallestUnit, bcpow( '10', (string) $decimals, 0 ), $decimals );
    }
}
