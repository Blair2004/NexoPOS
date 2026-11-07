# Màn hình bếp (KDS) + In bếp theo khu — MVP

Nhánh: `viet-hoa` (NexoPOSHH, fork từ Blair2004/NexoPOS, GPLv3).

## Core đã có gì (đã khảo sát trước khi làm)

- Core **không có** sẵn màn hình bếp, in bếp, hay khu bếp/quầy bar.
  Gastro / MultiStore là module trả phí, không có trong core.
- Core **đã có**: đơn hàng (`nexopos_orders`, `process_status`:
  pending/ongoing/ready), món trong đơn (`nexopos_orders_products`,
  cột `status` = sold/refunded phục vụ hoàn hàng), danh mục sản phẩm
  (`nexopos_products_categories`), in hóa đơn qua trình duyệt
  (`?autoprint=true` + `window.print()`), cài đặt POS theo tab,
  menu dashboard, API `/api/...` dùng Sanctum + `nsHttpClient`
  (trả về **rxjs Observable** — dùng `.subscribe`, không dùng `.then`).
- Core **không có**: bàn ăn (thuộc module Gastro), máy in ESC/POS,
  realtime/socket cho bếp.

## MVP này làm gì

- **Màn hình bếp** tại `/dashboard/kitchen` (menu "Màn hình bếp",
  quyền `nexopos.read.orders`): đơn hôm nay, `process_status`
  pending/ongoing, xếp theo thời gian cũ → mới.
- **Phân khu**: khu = **danh mục sản phẩm** của món
  (`product_category_id`). Ví dụ: danh mục "Món chính" → khu bếp,
  "Đồ uống" → quầy bar. Lọc bằng tab Tất cả / từng khu, kèm số món
  đang chờ của mỗi khu. Chưa có bảng khu riêng — xem "Chưa làm".
- **Đánh dấu món**: mỗi món có trạng thái bếp riêng
  `kitchen_status` = pending (Chờ) / preparing (Đang làm) / done
  (Xong), lưu ở cột mới trên `nexopos_orders_products`. Khi mọi món
  của đơn đều xong, đơn tự chuyển `process_status` → ready (dùng lại
  `OrdersService::changeProcessingStatus` và event sẵn có).
  Cột `status` (sold/refunded) không bị động vào.
- **Bật/tắt KDS**: cài đặt `ns_kds_enabled` trong Cài đặt POS →
  tab "Màn hình bếp". Đặt "No" thì menu "Màn hình bếp" bị ẩn và
  màn hình `/dashboard/kitchen`, API `/api/kitchen/orders`, cập
  nhật món và phiếu bếp đều trả 404 (`KitchenService::isEnabled()`).
- **Báo món chờ lâu**: đơn chờ ≥ ngưỡng (mặc định 15 phút, chỉnh ở
  Cài đặt POS → tab "Màn hình bếp") thì viền đỏ + nhãn "Chờ lâu".
- **In bếp**: nút "In phiếu bếp" trên mỗi đơn mở phiếu in của trình
  duyệt (`/dashboard/orders/{order}/kitchen-ticket/{zone?}?autoprint=true`),
  chỉ in món chưa xong của khu đó, không in giá. Nếu bật
  "Tự động in phiếu bếp" trong cài đặt, màn hình bếp tự mở hộp thoại
  in khi có đơn mới (xem giới hạn bên dưới).

## Giới hạn đã biết (cố tình của MVP)

1. **Polling, không realtime**: màn hình tự tải lại mỗi 15 giây
   (chỉnh được 10/15/30/60 giây). Khi tab trình duyệt bị ẩn thì
   tạm dừng gọi API. Độ trễ tối đa = khoảng polling.
2. **Tự động in là in trình duyệt, theo từng máy**: hộp thoại in của
   trình duyệt vẫn cần bấm xác nhận; mỗi máy bếp cần mở sẵn màn hình
   bếp và chọn đúng máy in bếp mặc định. Đơn đã in được nhớ bằng
   `localStorage` của trình duyệt đó (`ns-kds-printed-orders`), nên
   đổi máy/trình duyệt thì đơn cũ có thể in lại; đơn đã có sẵn lúc
   mở màn hình sẽ không tự in (chỉ đơn mới xuất hiện sau đó mới in).
3. **Chưa có máy in nhiệt ESC/POS / in trực tiếp qua mạng LAN**,
   chưa có âm báo đơn mới, chưa có bàn ăn (core không có bàn —
   phiếu chỉ ghi mã đơn, loại đơn, khách, ghi chú).
4. Chỉ hiện **đơn trong ngày** và đơn chưa sẵn sàng; đơn đã ready,
   đã hủy, đã hoàn thì không còn trên màn hình.

## File thay đổi

| File | Nội dung |
| --- | --- |
| `database/migrations/update/2026_11_07_000000_add_kitchen_status_to_nexopos_orders_products_table.php` | Thêm cột `kitchen_status` (mặc định `pending`) cho các bản cài sẵn có |
| `database/migrations/create/2020_06_20_000000_create_orders_products_table.php` | Thêm cột `kitchen_status` vào bảng tạo mới, để bản cài mới (fresh install) cũng có cột này — migration update không chạy trên fresh install vì setup đánh dấu mọi update migration là đã chạy |
| `app/Models/OrderProduct.php` | Hằng `KITCHEN_*` + docblock |
| `app/Services/KitchenService.php` | Lấy đơn/khu theo ngày, đổi trạng thái món, món cho phiếu in |
| `app/Http/Controllers/Dashboard/KitchenController.php` | Trang bếp, API JSON, đổi trạng thái món, phiếu in |
| `routes/web/orders.php` | `GET /dashboard/kitchen`, `GET /dashboard/orders/{order}/kitchen-ticket/{zone?}` |
| `routes/api/kitchen.php` (mới) + `routes/api-base.php` | `GET /api/kitchen/orders`, `POST /api/kitchen/order-products/{item}/status` |
| `app/Settings/pos/kitchen.php` (mới) + `app/Settings/PosSettings.php` | Tab "Màn hình bếp" trong Cài đặt POS |
| `app/Services/MenuService.php` | Menu "Màn hình bếp" |
| `resources/views/pages/dashboard/orders/kitchen.blade.php` (mới) | Trang bếp |
| `resources/views/pages/dashboard/orders/templates/kitchen-ticket.blade.php` (mới) | Phiếu bếp khổ hẹp, không giá |
| `resources/ts/pages/dashboard/orders/ns-kitchen-display.vue` (mới) + `resources/ts/app-init.ts` | Component Vue: tab khu, polling, đánh dấu món, tự động in |
| `lang/vi.json` | Thêm 30 key tiếng Việt mới, không đổi key cũ |

Quyền dùng lại quyền sẵn có, không tạo quyền mới:
xem bếp + phiếu in dùng `nexopos.read.orders`,
đánh dấu món dùng `nexopos.update.orders`.

## Chạy thử trên máy local

```bash
# 1. Cài đặt / cập nhật (chạy migration update mới)
php artisan migrate --force

# 2. Kiểm tra cú pháp (đã chạy trong lúc làm, xem commit)
php -l app/Services/KitchenService.php
php -l app/Http/Controllers/Dashboard/KitchenController.php
php -l app/Models/OrderProduct.php
php -l routes/api/kitchen.php
php -l routes/web/orders.php
php -l app/Settings/pos/kitchen.php
python3 -c "import json; json.load(open('lang/vi.json'))"

# 3. Build lại frontend (Vue) rồi chạy app
npm run build
php artisan serve
```

Thử tay:

1. Đăng nhập tài khoản có quyền xem đơn. Vào **Màn hình bếp**
   (menu bên trái, ngay dưới POS).
2. Tạo đơn mới ở POS với món thuộc 2 danh mục khác nhau
   (ví dụ "Món chính" và "Đồ uống") → trong ≤ 15 giây đơn xuất
   hiện trên màn hình bếp, đúng tab khu.
3. Bấm **Bắt đầu** rồi **Xong** từng món; khi xong hết món,
   đơn biến khỏi màn hình (đã ready — kiểm tra ở danh sách đơn).
4. Bấm **In phiếu bếp** → phiếu chỉ liệt kê món chưa xong của khu
   đang chọn, không có giá. Bật "Tự động in phiếu bếp" ở
   Cài đặt → POS → Màn hình bếp rồi tạo đơn mới để thử tự động in.
5. Đổi ngưỡng "Báo chờ lâu sau" về 10 phút và chờ đơn cũ hơn
   10 phút để thấy viền đỏ + nhãn "Chờ lâu".

## Phần còn lại (chưa làm, theo thứ tự ưu tiên)

1. **Bảng khu bếp riêng** (ánh xạ danh mục → khu, đặt tên khu, gán
   máy in theo khu) thay cho việc dùng thẳng danh mục sản phẩm.
2. **Realtime** (Echo/socket có sẵn của NexoPOS hoặc polling ngắn
   hơn) + âm báo khi có đơn mới.
3. **In ESC/POS trực tiếp** (máy in mạng LAN/USB qua print server)
   để không cần bấm hộp thoại trình duyệt; hiện tại chỉ in trình duyệt.
4. **Bàn ăn / số bàn** trên phiếu bếp (cần module Gastro hoặc thêm
   trường bàn vào đơn).
5. Test tự động (Feature test cho `GET /api/kitchen/orders` và
   `POST .../status`) — chưa có vì môi trường làm việc không có PHP
   để chạy PHPUnit; cần chạy `php artisan test` trên máy dev.
