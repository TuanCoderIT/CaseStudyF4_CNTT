# PHÒNG TRỌ SINH VIÊN - HỆ THỐNG TÌM KIẾM VÀ QUẢN LÝ PHÒNG TRỌ

## Giới thiệu

Hệ thống Phòng Trọ Sinh Viên là một nền tảng trực tuyến giúp sinh viên và người dùng khác tìm kiếm, đăng tin và quản lý phòng trọ. Hệ thống được phát triển với mục tiêu đơn giản hóa quá trình tìm kiếm chỗ ở cho sinh viên, đồng thời cung cấp công cụ hiệu quả cho chủ trọ quản lý thông tin phòng cho thuê.

## Tính năng chính

### Dành cho người thuê trọ

- **Tìm kiếm phòng trọ**: Tìm kiếm phòng theo nhiều tiêu chí như khu vực, giá cả, diện tích, tiện ích
- **Xem phòng dựa trên khoảng cách**: Tìm phòng gần vị trí hiện tại hoặc gần trường học
- **Đặt cọc trực tuyến**: Thanh toán tiền cọc qua VNPay hoặc tiền mặt
- **Yêu thích phòng**: Lưu các phòng yêu thích để xem lại sau
- **Quản lý thông tin cọc**: Theo dõi trạng thái đặt cọc và thanh toán

### Dành cho chủ trọ

- **Đăng tin phòng trọ**: Đăng thông tin phòng với hình ảnh và mô tả chi tiết
- **Quản lý phòng trọ**: Cập nhật thông tin phòng, đánh dấu đã cho thuê
- **Quản lý đặt cọc**: Xác nhận đặt cọc, giải ngân hoặc hoàn tiền
- **Thống kê và báo cáo**: Xem thống kê về phòng đã đăng và tình trạng đặt cọc

### Dành cho quản trị viên

- **Phê duyệt phòng trọ**: Kiểm duyệt tin đăng trước khi hiển thị
- **Quản lý người dùng**: Thêm, sửa, xóa người dùng
- **Quản lý danh mục**: Quản lý loại phòng và quận/huyện
- **Giám sát giao dịch**: Theo dõi các giao dịch đặt cọc trên hệ thống

## Cài đặt và chạy hệ thống

### Yêu cầu hệ thống

- PHP 7.4 trở lên
- MySQL 5.7 trở lên
- Web server (Apache hoặc Nginx)
- Đã cài đặt Composer (nếu cần cài đặt các thư viện)

### Các bước cài đặt

1. **Cài đặt cơ sở dữ liệu**:

   - Tạo cơ sở dữ liệu MySQL
   - Import file `database/backup.sql` vào cơ sở dữ liệu

2. **Cấu hình kết nối**:

   - Chỉnh sửa thông tin kết nối trong file `config/db.php`

   ```php
   $servername = "localhost";  // Thay đổi nếu cần
   $username = "root";         // Thay đổi username MySQL của bạn
   $password = "";             // Thay đổi mật khẩu MySQL của bạn
   $dbname = "phongtro_db";    // Thay đổi tên database của bạn
   ```

3. **Cấu hình VNPay** (nếu sử dụng chức năng thanh toán):

   - Chỉnh sửa thông tin trong file `config/vnpay.php` với thông tin tài khoản VNPay của bạn

4. **Phân quyền thư mục uploads**:

   - Chắc chắn thư mục `uploads` và các thư mục con có quyền ghi (755 hoặc 777)

5. **Truy cập hệ thống**:
   - Khởi động web server
   - Truy cập trang web qua đường dẫn: `http://localhost/phongtro/CaseStudyF4_CNTT`

### Tài khoản mặc định

1. **Tài khoản admin**:

   - Username: admin
   - Password: admin123

2. **Tài khoản chủ trọ**:

   - Username: chutro
   - Password: chutro123

3. **Tài khoản người dùng**:
   - Username: user
   - Password: user123

## Hướng dẫn sử dụng

### Dành cho người dùng

1. **Đăng ký và đăng nhập**:

   - Truy cập trang chủ và nhấn "Đăng ký" để tạo tài khoản mới
   - Hoặc đăng nhập nếu đã có tài khoản

2. **Tìm kiếm phòng trọ**:

   - Sử dụng thanh tìm kiếm ở trang chủ
   - Lọc theo quận/huyện, khoảng giá, diện tích, tiện ích
   - Xem danh sách phòng trọ phù hợp với tiêu chí

3. **Xem chi tiết phòng trọ**:

   - Nhấn vào phòng trọ để xem thông tin chi tiết
   - Xem hình ảnh, mô tả, tiện ích và thông tin chủ trọ
   - Thêm vào danh sách yêu thích bằng cách nhấn nút "Yêu thích"

4. **Đặt cọc phòng trọ**:

   - Từ trang chi tiết phòng, nhấn "Đặt cọc"
   - Chọn phương thức thanh toán (VNPay hoặc tiền mặt)
   - Hoàn tất thanh toán theo hướng dẫn

5. **Quản lý đặt cọc và yêu thích**:
   - Vào mục "Phòng đã đặt cọc" để xem trạng thái đặt cọc
   - Vào mục "Phòng yêu thích" để xem danh sách phòng đã lưu

### Dành cho chủ trọ

1. **Đăng phòng trọ mới**:

   - Vào mục "Đăng phòng trọ mới"
   - Điền thông tin phòng, tải lên hình ảnh
   - Nhấn "Đăng tin" và chờ quản trị viên phê duyệt

2. **Quản lý phòng trọ**:

   - Vào mục "Quản lý phòng trọ" để xem danh sách phòng đã đăng
   - Chỉnh sửa thông tin phòng hoặc đánh dấu đã cho thuê

3. **Quản lý đặt cọc**:
   - Vào mục "Quản lý đặt cọc" để xem danh sách yêu cầu đặt cọc
   - Xác nhận đặt cọc, giải ngân tiền hoặc hoàn trả tiền cọc

### Dành cho quản trị viên

1. **Đăng nhập vào trang quản trị**:

   - Truy cập `/admin` và đăng nhập với tài khoản admin

2. **Quản lý phòng trọ**:

   - Phê duyệt phòng trọ mới đăng
   - Chỉnh sửa hoặc xóa phòng trọ không phù hợp

3. **Quản lý người dùng**:

   - Thêm, sửa, xóa thông tin người dùng
   - Phân quyền cho người dùng

4. **Quản lý danh mục**:

   - Thêm, sửa, xóa loại phòng và quận/huyện
   - Cập nhật các tiện ích có thể chọn

5. **Giám sát giao dịch**:
   - Xem tất cả các giao dịch đặt cọc
   - Xử lý các vấn đề liên quan đến đặt cọc và thanh toán

## Hỗ trợ và liên hệ

Nếu có bất kỳ vấn đề hoặc câu hỏi nào, vui lòng liên hệ:

- Email: support@phongtrosinhvien.com
- Điện thoại: 0123 456 789

## Phát triển bởi

Đồ án này được phát triển bởi nhóm sinh viên khoa Công nghệ thông tin - F4, với sự hướng dẫn của Thầy/Cô [Tên giảng viên hướng dẫn].

## Giấy phép

© 2024 Phòng Trọ Sinh Viên. Bảo lưu mọi quyền.
