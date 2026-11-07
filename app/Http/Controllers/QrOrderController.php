<?php

/**
 * QR Order Controller — Tính năng 1: QR order tại bàn + đặt món online.
 *
 * Menu công khai, không cần đăng nhập. Khách quét QR tại bàn (hoặc mở
 * /dat-mon) để xem thực đơn, gọi món, gọi nhân viên, yêu cầu thanh toán.
 * Đơn được tạo ở trạng thái "hold" (chờ xác nhận) nên hiện ngay trong
 * Dashboard → Hold Orders và trong POS, thu ngân/bếp không cần nhập lại.
 *
 * Thiết kế MVP, cố tình nhỏ:
 * - Core NexoPOS KHÔNG có bảng "bàn" (bàn thuộc module Gastro trả phí,
 *   không có trong core). Vì vậy "bàn" chỉ là nhãn (slug) lấy từ URL
 *   QR, ví dụ /dat-mon/ban/A1, và được lưu vào tiêu đề/ghi chú của đơn.
 * - Loại đơn "dine-in" được thêm vào OrdersService::getTypeOptions()
 *   (xem thêm ở đó). Đơn mang về / giao hàng dùng 2 loại có sẵn.
 * - Giá luôn được tính lại phía server từ ProductUnitQuantity theo đúng
 *   logic giá bán của POS (getSalePrice). Giá client gửi lên (nếu có)
 *   không bao giờ được tin.
 * - Khách hàng của đơn QR là "khách hàng mặc định" đã cấu hình trong
 *   Settings → Customers (ns_customers_default) — đúng như POS dùng
 *   cho khách vãng lai. Nếu chưa cấu hình, endpoint trả lỗi 422 rõ ràng.
 * - author_id của đơn (cột NOT NULL) lấy user đầu tiên của cửa hàng
 *   (thường là chủ cửa hàng tạo lúc cài đặt).
 * - "Gọi nhân viên" / "Yêu cầu thanh toán" không tạo bảng mới: gửi
 *   Notification cho các vai trò có quyền nexopos.read.orders, nhân
 *   viên thấy ngay ở chuông thông báo trên Dashboard.
 */

namespace App\Http\Controllers;

use App\Exceptions\NotFoundException;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductUnitQuantity;
use App\Models\User;
use App\Services\NotificationService;
use App\Services\OrdersService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

class QrOrderController extends Controller
{
    /**
     * Tiền tố tiêu đề chỉ đơn QR mới có (đặt trong store()).
     * Dùng làm dấu hiệu để trang trạng thái công khai chỉ tra cứu
     * được đơn QR, không tra cứu được đơn POS.
     */
    public const QR_TITLE_PREFIX = 'QR — ';
    public function __construct(
        private OrdersService $ordersService,
        private NotificationService $notificationService
    ) {}

    /**
     * Trang thực đơn công khai.
     * /dat-mon           → đặt mang về / giao hàng (chọn kênh trên trang)
     * /dat-mon/ban/{ban} → ăn tại bàn, kênh cố định là dine-in
     */
    public function menu( ?string $table = null )
    {
        $table = $table !== null ? $this->normalizeTable( $table ) : null;

        if ( request()->route( 'table' ) !== null && $table === null ) {
            abort( 422, __( 'Invalid table.' ) );
        }

        $categories = ProductCategory::displayOnPOS()
            ->orderBy( 'name' )
            ->get( [ 'id', 'name' ] );

        $products = Product::with( [
            'unit_quantities' => fn( $query ) => $query->where( 'visible', true )->orderBy( 'id' ),
            'unit_quantities.unit',
            'galleries',
        ] )
            ->searchable()
            ->where( 'status', Product::STATUS_AVAILABLE )
            ->excludeVariations()
            ->orderBy( 'name' )
            ->limit( 500 )
            ->get()
            ->map( function ( Product $product ) {
                $unitQuantity = $product->unit_quantities->first();

                if ( ! $unitQuantity instanceof ProductUnitQuantity ) {
                    return null;
                }

                $featured = $product->galleries->firstWhere( 'featured', true )
                    ?? $product->galleries->first();

                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'description' => Str::limit( strip_tags( $product->description ?? '' ), 140 ),
                    'category_id' => $product->category_id,
                    'unit_quantity_id' => $unitQuantity->id,
                    'unit' => $unitQuantity->unit?->name ?? '',
                    'price' => (float) $this->resolveSalePrice( $unitQuantity ),
                    'price_formatted' => (string) ns()->currency->define( $this->resolveSalePrice( $unitQuantity ) ),
                    'image' => $featured?->url,
                ];
            } )
            ->filter()
            ->values();

        return view( 'pages.qr-order.menu', [
            'title' => $table
                ? sprintf( __( 'Table %s — %s' ), $table, ns()->option->get( 'ns_store_name', 'NexoPOS' ) )
                : sprintf( __( 'Order Online — %s' ), ns()->option->get( 'ns_store_name', 'NexoPOS' ) ),
            'storeName' => ns()->option->get( 'ns_store_name', 'NexoPOS' ),
            'table' => $table,
            'categories' => $categories,
            'products' => $products,
            'currency' => [
                'symbol' => ns()->option->get( 'ns_currency_symbol', '' ),
                'position' => ns()->option->get( 'ns_currency_position', 'before' ),
                'precision' => (int) ns()->option->get( 'ns_currency_precision', 0 ),
                'decimal_separator' => ns()->option->get( 'ns_currency_decimal_separator', ',' ),
                'thousand_separator' => ns()->option->get( 'ns_currency_thousand_separator', '.' ),
            ],
        ] );
    }

    /**
     * Tạo đơn từ giỏ hàng của khách (JSON).
     * Đơn được tạo ở trạng thái hold → chờ thu ngân/bếp xác nhận trên POS.
     */
    public function store( Request $request )
    {
        $validated = $request->validate( [
            'channel' => [ 'required', 'in:dine-in,takeaway,delivery' ],
            'table' => [ 'nullable', 'string', 'max:20', 'regex:/^[A-Za-z0-9\-]{1,20}$/' ],
            'items' => [ 'required', 'array', 'min:1', 'max:50' ],
            'items.*.product_id' => [ 'required', 'integer', 'min:1' ],
            'items.*.unit_quantity_id' => [ 'required', 'integer', 'min:1' ],
            'items.*.quantity' => [ 'required', 'numeric', 'min:0.1', 'max:99' ],
            'customer_name' => [ 'nullable', 'string', 'max:100' ],
            'customer_phone' => [ 'nullable', 'string', 'max:20' ],
            'note' => [ 'nullable', 'string', 'max:500' ],
            'address_1' => [ 'nullable', 'string', 'max:255' ],
            'city' => [ 'nullable', 'string', 'max:100' ],
        ] );

        if ( $validated[ 'channel' ] === 'dine-in' && empty( $validated[ 'table' ] ) ) {
            return response()->json( [
                'status' => 'error',
                'message' => __( 'Invalid table.' ),
            ], 422 );
        }

        if ( $validated[ 'channel' ] === 'delivery' && empty( $validated[ 'address_1' ] ) ) {
            return response()->json( [
                'status' => 'error',
                'message' => __( 'Please enter your delivery address.' ),
            ], 422 );
        }

        $customerId = $this->resolveGuestCustomerId();
        $author = User::orderBy( 'id' )->first();

        if ( ! $author instanceof User ) {
            return response()->json( [
                'status' => 'error',
                'message' => __( 'The order could not be placed.' ),
            ], 500 );
        }

        /**
         * Gộp các dòng trùng unit_quantity_id và tính lại giá phía server.
         * Không bao giờ dùng giá client gửi lên.
         */
        $grouped = collect( $validated[ 'items' ] )->groupBy( 'unit_quantity_id' );
        $orderProducts = [];

        foreach ( $grouped as $unitQuantityId => $lines ) {
            $unitQuantity = ProductUnitQuantity::with( 'unit' )->find( $unitQuantityId );

            if ( ! $unitQuantity instanceof ProductUnitQuantity ) {
                return response()->json( [
                    'status' => 'error',
                    'message' => __( 'The order could not be placed.' ),
                ], 422 );
            }

            $product = Product::where( 'id', $unitQuantity->product_id )
                ->where( 'status', Product::STATUS_AVAILABLE )
                ->first();

            if (
                ! $product instanceof Product
                || (int) $lines->first()[ 'product_id' ] !== (int) $product->id
            ) {
                return response()->json( [
                    'status' => 'error',
                    'message' => __( 'The order could not be placed.' ),
                ], 422 );
            }

            $orderProducts[] = [
                'product_id' => $product->id,
                'unit_quantity_id' => $unitQuantity->id,
                'quantity' => $lines->sum( fn( $line ) => (float) $line[ 'quantity' ] ),
                'unit_price' => $this->resolveSalePrice( $unitQuantity ),
            ];
        }

        $channel = $validated[ 'channel' ];
        $table = $validated[ 'table' ] ?? null;

        $title = match ( $channel ) {
            'dine-in' => sprintf( __( 'QR — Table %s' ), $table ),
            'takeaway' => __( 'QR — Take Away' ),
            default => __( 'QR — Delivery' ),
        };

        $noteLines = [];

        if ( ! empty( $validated[ 'customer_name' ] ) ) {
            $noteLines[] = sprintf( __( 'Customer Name: %s' ), $validated[ 'customer_name' ] );
        }

        if ( ! empty( $validated[ 'customer_phone' ] ) ) {
            $noteLines[] = sprintf( __( 'Phone Number: %s' ), $validated[ 'customer_phone' ] );
        }

        if ( $channel === 'delivery' ) {
            $noteLines[] = sprintf(
                __( 'Delivery Address: %s' ),
                trim( ( $validated[ 'address_1' ] ?? '' ) . ( ! empty( $validated[ 'city' ] ) ? ', ' . $validated[ 'city' ] : '' ) )
            );
        }

        if ( ! empty( $validated[ 'note' ] ) ) {
            $noteLines[] = $validated[ 'note' ];
        }

        $fields = [
            'customer_id' => $customerId,
            'author_id' => $author->id,
            'type' => [ 'identifier' => $channel ],
            'payment_status' => Order::PAYMENT_HOLD,
            'title' => $title,
            'note' => implode( "\n", $noteLines ) ?: null,
            'products' => $orderProducts,
            'payments' => [],
            'discount' => 0,
            'shipping' => 0,
        ];

        if ( $channel === 'delivery' ) {
            $address = [
                'first_name' => $validated[ 'customer_name' ] ?? '',
                'phone' => $validated[ 'customer_phone' ] ?? '',
                'address_1' => $validated[ 'address_1' ],
                'city' => $validated[ 'city' ] ?? '',
            ];

            $fields[ 'addresses' ] = [
                'shipping' => $address,
                'billing' => $address,
            ];
        }

        try {
            $result = $this->ordersService->create( $fields );
        } catch ( NotFoundException $exception ) {
            return response()->json( [
                'status' => 'error',
                'message' => __( 'The order could not be placed.' ),
            ], 422 );
        } catch ( \Exception $exception ) {
            Log::warning( 'QR order failed: ' . $exception->getMessage() );

            return response()->json( [
                'status' => 'error',
                'message' => __( 'The order could not be placed.' ),
            ], 422 );
        }

        /** @var Order $order */
        $order = $result[ 'data' ][ 'order' ];

        $this->notifyStaff(
            title: __( 'New QR order' ),
            description: sprintf(
                '%s · %s · %s',
                $title,
                sprintf( __( 'Order Code: %s' ), $order->code ),
                (string) ns()->currency->define( $order->total )
            )
        );

        return response()->json( [
            'status' => 'success',
            'message' => __( 'Your order has been placed.' ),
            'data' => [
                'order_id' => $order->id,
                'code' => $order->code,
                'total' => (float) $order->total,
                'total_formatted' => (string) ns()->currency->define( $order->total ),
                /**
                 * Link trạng thái có chữ ký (signed URL): mã đơn dạng
                 * yymmdd-NNN rất dễ đoán nên không thể để link công khai
                 * dạng /dat-mon/don/{code}. Route trạng thái có
                 * middleware 'signed' nên link không có chữ ký hợp lệ
                 * sẽ bị từ chối.
                 */
                'status_url' => URL::signedRoute( 'ns.qr-order.status', [ 'code' => $order->code ] ),
            ],
        ], 201 );
    }

    /**
     * Trang tra cứu trạng thái đơn theo mã (công khai, chỉ hiện trạng
     * thái + món + tổng tiền, không lộ số điện thoại của khách).
     * Chỉ tra cứu được đơn QR: đơn QR luôn có tiêu đề bắt đầu bằng
     * "QR — " (chỉ store() đặt tiêu đề này). Route có middleware
     * 'signed' + throttle nên không thể liệt kê đơn bằng cách đoán
     * mã yymmdd-NNN. Ghi chú của đơn (chứa tên/SĐT/địa chỉ) không
     * bao giờ hiển thị ở trang này.
     */
    public function show( string $code )
    {
        $order = Order::with( 'products' )
            ->where( 'code', $code )
            ->where( 'title', 'like', self::QR_TITLE_PREFIX . '%' )
            ->first();

        if ( ! $order instanceof Order ) {
            abort( 404, __( 'Order not found.' ) );
        }

        $processLabels = [
            Order::PROCESSING_PENDING => __( 'Awaiting confirmation' ),
            Order::PROCESSING_ONGOING => __( 'Preparing' ),
            Order::PROCESSING_READY => __( 'Ready' ),
            Order::PROCESSING_FAILED => __( 'Error' ),
        ];

        $paymentLabels = config( 'nexopos.orders.statuses', [] );

        return view( 'pages.qr-order.status', [
            'title' => sprintf( __( 'Order Status — %s' ), $order->code ),
            'storeName' => ns()->option->get( 'ns_store_name', 'NexoPOS' ),
            'order' => $order,
            'typeLabel' => $this->ordersService->getTypeLabel( $order->getRawOriginal( 'type' ) ),
            'processLabel' => $processLabels[ $order->process_status ] ?? $order->process_status,
            'paymentLabel' => $paymentLabels[ $order->payment_status ] ?? $order->payment_status,
        ] );
    }

    /**
     * Khách tại bàn bấm "Gọi nhân viên" hoặc "Yêu cầu thanh toán".
     * Gửi notification cho nhân viên có quyền đọc đơn (hiện ở chuông
     * thông báo trên Dashboard). Giới hạn 10 lần/phút/IP chống spam.
     */
    public function requestService( Request $request )
    {
        $validated = $request->validate( [
            'kind' => [ 'required', 'in:call-staff,request-bill' ],
            'table' => [ 'nullable', 'string', 'max:20', 'regex:/^[A-Za-z0-9\-]{1,20}$/' ],
            'order_code' => [ 'nullable', 'string', 'max:20' ],
        ] );

        $order = null;

        if ( ! empty( $validated[ 'order_code' ] ) ) {
            /**
             * Không tiết lộ đơn có tồn tại hay không: mã sai chỉ đơn
             * giản là không gắn mã đơn vào thông báo, vẫn trả thành
             * công như thường (tránh làm oracle để liệt kê mã đơn).
             */
            $order = Order::where( 'code', $validated[ 'order_code' ] )->first();
        }

        $where = [];

        if ( ! empty( $validated[ 'table' ] ) ) {
            $where[] = sprintf( __( 'Table %s' ), $validated[ 'table' ] );
        }

        if ( $order instanceof Order ) {
            $where[] = sprintf( __( 'Order Code: %s' ), $order->code );
        }

        $this->notifyStaff(
            title: $validated[ 'kind' ] === 'call-staff'
                ? __( 'Customer is calling staff' )
                : __( 'Customer requested the bill' ),
            description: implode( ' · ', $where ) ?: __( 'QR order' )
        );

        return response()->json( [
            'status' => 'success',
            'message' => __( 'Staff has been notified.' ),
        ] );
    }

    /**
     * Giá bán của POS: VAT tắt → sale_price; VAT bật → gross/net theo
     * cài đặt ns_pos_prefered_price. Giống hệt PosService::getSalePrice().
     * Đây là giá chuẩn của POS (không phải giá khách tự sửa), nên
     * OrdersService::__checkPosActionPermissions() chấp nhận nó mà
     * không yêu cầu quyền nexopos.cart.product-price.
     */
    private function resolveSalePrice( ProductUnitQuantity $unitQuantity ): float
    {
        if ( ns()->option->get( 'ns_pos_vat', 'disabled' ) === 'disabled' ) {
            return (float) $unitQuantity->sale_price;
        }

        return ns()->option->get( 'ns_pos_prefered_price' ) === 'gross_prices'
            ? (float) $unitQuantity->sale_price_gross
            : (float) $unitQuantity->sale_price_net;
    }

    /**
     * Khách hàng mặc định của cửa hàng (Settings → Customers).
     * Đây là khách vãng lai mà POS vẫn dùng cho đơn không có khách đăng ký.
     */
    private function resolveGuestCustomerId(): int
    {
        $defaultCustomerId = (int) ns()->option->get( 'ns_customers_default', 0 );

        if ( $defaultCustomerId > 0 ) {
            $customer = Customer::find( $defaultCustomerId );

            if ( $customer instanceof Customer ) {
                return $customer->id;
            }
        }

        abort( 422, __( 'Please set a default customer first.' ) );
    }

    private function normalizeTable( string $table ): ?string
    {
        $table = trim( $table );

        return preg_match( '/^[A-Za-z0-9\-]{1,20}$/', $table ) ? $table : null;
    }

    /**
     * Báo cho nhân viên qua Notification có sẵn của NexoPOS.
     * Lỗi notification không được làm hỏng đơn / yêu cầu của khách.
     */
    private function notifyStaff( string $title, string $description ): void
    {
        try {
            $this->notificationService
                ->create(
                    title: $title,
                    description: $description,
                    url: ns()->route( 'ns.dashboard.orders' ),
                    source: 'qr-order'
                )
                ->dispatchForPermissions( [ 'nexopos.read.orders' ] );
        } catch ( \Exception $exception ) {
            Log::warning( 'QR order notification failed: ' . $exception->getMessage() );
        }
    }
}
