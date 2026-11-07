# Thông báo và chăm sóc khách qua Zalo OA

Tính năng MVP gửi thông báo cho khách hàng qua Zalo OA (Official Account):
xác nhận đơn, trạng thái món, hóa đơn và khuyến mãi cho khách quen.

## Đã làm được gì (MVP)

- **Xác nhận đơn**: khi đơn hàng được tạo ở POS, listener
  `ZaloOrderCreatedListener` gửi tin xác nhận qua Zalo OA.
- **Trạng thái món**: khi trạng thái xử lý của đơn đổi
  (`pending` → `ongoing` → `ready`), listener
  `ZaloOrderProcessStatusListener` gửi cập nhật cho khách.
- **Thanh toán + hóa đơn**: khi đơn chuyển sang `paid`, listener
  `ZaloOrderPaymentListener` gửi xác nhận đã nhận tiền kèm hóa đơn.
- **Khuyến mãi**: `ZaloOaService::sendPromotion()` gửi ưu đãi cho khách quen
  đã liên kết Zalo (dùng trong code/command, chưa có nút bấm trong giao diện).
- **Webhook**: `POST /api/zalo/webhook` nhận sự kiện `follow` (khách theo dõi
  OA) và `user_send_text` (khách nhắn tin). Hiện chỉ ghi log để nhân viên
  liên kết tài khoản; webhook không tạo hay sửa đơn hàng.
- **Chạy thử (dry-run)**: mặc định không gọi API thật, chỉ ghi log.

## Lấy OA ID và access token thật

1. **Tạo Zalo OA** tại https://oa.zalo.me (đăng nhập, tạo OA cho cửa hàng,
   chờ Zalo duyệt).
2. **Tạo ứng dụng** tại https://developers.zalo.me, bật sản phẩm liên quan
   tới OA cho ứng dụng đó.
3. **Ủy quyền và lấy access token**: cho ứng dụng quyền truy cập OA rồi đổi
   mã ủy quyền lấy `access_token` (xem tài liệu chính thức của Zalo tại
   https://developers.zalo.me/docs/api/official-account-api-5).
   Access token có thời hạn — dùng `refresh_token` để lấy token mới trước
   khi hết hạn; hiện chưa có tự động refresh (xem "Chưa làm" bên dưới).
4. **Đăng ký webhook** trong trang quản trị OA: trỏ về
   `https://<tên-miền-của-bạn>/api/zalo/webhook` để nhận sự kiện `follow`
   và tin nhắn của khách.

> Không bao giờ ghi access token thật vào code hay commit lên Git. Token
> chỉ nằm trong file `.env` trên máy chủ.

## Xác minh chữ ký webhook

Zalo ký mọi sự kiện webhook bằng header `X-ZEvent-Signature: mac=<hex>`,
với `mac = sha256(appId + rawBody + timestamp + OAsecretKey)` (rawBody là
body JSON gốc, timestamp là trường `timestamp` trong body). Endpoint
`/api/zalo/webhook` kiểm tra chữ ký này trước khi xử lý sự kiện
(`ZaloOaService::verifyWebhookSignature()`), dùng `ZALO_OA_APP_ID` (ID ứng
dụng trên developers.zalo.me — khác với `ZALO_OA_ID` là ID của OA) và
`ZALO_OA_SECRET`.

**Mức độ kiểm chứng của công thức:** công thức này được nhiều nguồn độc lập
xác nhận, kể cả tài liệu Zalo được trích dẫn lại, nhưng chưa được kiểm
chứng bằng một sự kiện có chữ ký thật từ Zalo. Vì vậy chế độ cưỡng chế
mặc định **tắt**: khi chữ ký sai, hệ thống chỉ ghi log cảnh báo
(`Zalo OA webhook: signature verification failed.`) mà vẫn xử lý sự kiện
như trước. Cách bật cưỡng chế an toàn:

1. Điền đủ `ZALO_OA_APP_ID` và `ZALO_OA_SECRET` trong `.env`, giữ
   `ZALO_OA_WEBHOOK_ENFORCE=false`.
2. Gửi một sự kiện thử từ trang quản trị OA (hoặc bấm theo dõi OA) rồi xem
   log: nếu thấy `Zalo OA webhook: signature verified.` thì công thức đã
   đúng với OA của bạn.
3. Đặt `ZALO_OA_WEBHOOK_ENFORCE=true`, chạy `php artisan config:clear`.
   Từ lúc này, sự kiện không có chữ ký hoặc sai chữ ký sẽ bị trả 401 và
   không được xử lý; sự kiện thiếu secret cấu hình cũng bị trả 401.

## Cấu hình (.env)

```env
ZALO_OA_ENABLED=true
ZALO_OA_DRY_RUN=true        # true = chỉ ghi log, không gửi thật
ZALO_OA_ID=123456789012345
ZALO_OA_APP_ID=1234567890123456789
ZALO_OA_WEBHOOK_ENFORCE=false   # true = trả 401 khi chữ ký webhook sai (chỉ bật sau khi đã kiểm chứng, xem bên dưới)
ZALO_OA_ACCESS_TOKEN=<token lấy ở bước trên>
ZALO_OA_SECRET=             # dự phòng, hiện chưa dùng để xác thực webhook
ZALO_OA_API_BASE_URL=https://openapi.zalo.me
ZALO_OA_TIMEOUT=10
```

Sau khi sửa `.env`, chạy `php artisan config:clear` (hoặc khởi động lại
hàng đợi nếu dùng queue) rồi chạy migration để có cột lưu mã Zalo:

```bash
php artisan migrate
```

## Liên kết khách hàng với Zalo

Zalo OA **chỉ nhắn được cho người đã theo dõi OA**, và tin nhắn CS cần
`user_id` của Zalo (không gửi được bằng số điện thoại). Quy trình:

1. Khách bấm theo dõi OA của cửa hàng trên Zalo.
2. Zalo gọi webhook; mã `user_id` của khách được ghi vào log của hệ thống.
3. Nhân viên liên kết mã đó với hồ sơ khách:
   ```bash
   php artisan ns:zalo-link-customer <id-khach-hang> <zalo-user-id>
   ```
   Ví dụ: `php artisan ns:zalo-link-customer 42 123456789012345`

Khách chưa liên kết sẽ không nhận được tin — đơn hàng vẫn tạo và thanh toán
bình thường, chỉ có dòng log ghi lại là đã bỏ qua.

## Chạy thử

```bash
# Chạy thử (dry-run): chỉ ghi log, không gửi thật
php artisan ns:zalo-test --recipient=<zalo-user-id> --message="Xin chào"
php artisan ns:zalo-test --order=12

# Gửi thật (sau khi đã cấu hình token và đặt ZALO_OA_DRY_RUN=false,
# hoặc dùng --live cho một lần chạy)
php artisan ns:zalo-test --order=12 --live
```

Kiểm tra log (`storage/logs/laravel.log`) sẽ thấy các dòng `Zalo OA: ...`.

## Chưa làm (bước tiếp theo)

- **Giao diện trong dashboard**: trang cài đặt Zalo OA (nhập OA ID/token,
  bật/tắt, xem log gửi) và ô nhập `zalo_user_id` trên form khách hàng.
  Hiện tại cấu hình bằng `.env` và liên kết bằng lệnh artisan.
- **Tự động refresh access token**: access token Zalo hết hạn sau một thời
  gian; cần job định kỳ dùng `refresh_token` để lấy token mới và lưu lại.
- **Đặt hàng qua Zalo**: khách nhắn tin đặt món qua OA, nhân viên xác nhận
  rồi tạo đơn. Webhook hiện mới ghi log tin nhắn, chưa tạo đơn.
- **Gửi bằng số điện thoại (ZNS)**: tin nhắn CS cần `user_id`; muốn gửi
  theo số điện thoại phải dùng Zalo Notification Service (ZNS, tính phí,
  cần đăng ký mẫu tin) — chưa tích hợp.
- **Gửi khuyến mãi hàng loạt**: hiện mới có hàm gửi cho từng khách
  (`sendPromotion`); chưa có màn hình chọn nhóm khách và gửi chiến dịch.
- **Theo dõi trạng thái gửi**: chưa lưu lịch sử tin đã gửi vào DB để xem
  lại trong dashboard (hiện chỉ có log file).
