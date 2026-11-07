{{--
    QR Order — Trang tra cứu trạng thái đơn theo mã (Tính năng 1).
    Công khai, chỉ hiện trạng thái + món + tổng tiền (không lộ SĐT).
--}}
<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title }}</title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; background: #f6f6f4; color: #1f2937; }
        .wrap { max-width: 560px; margin: 0 auto; padding: 24px 12px 60px; }
        .card { background: #fff; border: 1px solid #e5e7eb; border-radius: 14px; padding: 20px; }
        h1 { font-size: 20px; margin: 0 0 4px; text-align: center; }
        .store { text-align: center; color: #6b7280; font-size: 14px; margin-bottom: 16px; }
        .code { text-align: center; font-size: 26px; font-weight: 800; letter-spacing: 1px; margin: 6px 0 14px; }
        .row { display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #f3f4f6; font-size: 15px; }
        .row span:first-child { color: #6b7280; }
        .pill { display: inline-block; background: #fef3c7; color: #92400e; border-radius: 999px; padding: 2px 10px; font-size: 13px; font-weight: 600; }
        .items { margin-top: 14px; }
        .items .row span:last-child { font-weight: 600; }
        .total { display: flex; justify-content: space-between; font-size: 17px; font-weight: 700; margin-top: 14px; }
        .back { display: block; text-align: center; margin-top: 18px; color: #166534; font-weight: 600; }
    </style>
</head>
<body>
<div class="wrap">
    <div class="card">
        <h1>{{ __( 'Order Status' ) }}</h1>
        <div class="store">{{ $storeName }}</div>
        <div class="code">{{ $order->code }}</div>
        <div class="row"><span>{{ $order->title }}</span><span>{{ $typeLabel }}</span></div>
        <div class="row"><span>{{ __( 'Order Status' ) }}</span><span class="pill">{{ $processLabel }}</span></div>
        <div class="row"><span>{{ __( 'Payment Status' ) }}</span><span>{{ $paymentLabel }}</span></div>
        <div class="items">
            @foreach( $order->products as $product )
                <div class="row">
                    <span>{{ $product->name }} × {{ $product->quantity }}</span>
                    <span>{{ ns()->currency->define( $product->total_price ) }}</span>
                </div>
            @endforeach
        </div>
        <div class="total"><span>{{ __( 'Total' ) }}</span><span>{{ ns()->currency->define( $order->total ) }}</span></div>
        @if( $order->note )
            <div class="row"><span>{{ __( 'Note' ) }}</span><span>{{ $order->note }}</span></div>
        @endif
    </div>
    <a class="back" href="{{ route( 'ns.qr-order.menu' ) }}">← {{ __( 'Back to menu' ) }}</a>
</div>
</body>
</html>
