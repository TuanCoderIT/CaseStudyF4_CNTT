# Hướng dẫn thiết lập và sử dụng Chatbot PhòngTrọ

## Giới thiệu

Chatbot PhòngTrọ là một trợ lý ảo được tích hợp vào website PhòngTrọ, cho phép người dùng tương tác và tìm kiếm thông tin về phòng trọ thông qua giao diện chat. Chatbot sử dụng Google Gemini API để xử lý ngôn ngữ tự nhiên và kết nối với cơ sở dữ liệu của website để truy xuất thông tin phòng trọ.

## Thiết lập

### 1. Đăng ký API key Google Gemini

1. Truy cập [Google AI Studio](https://ai.google.dev/)
2. Đăng ký tài khoản Google (nếu chưa có)
3. Tạo một API key mới
4. Sao chép API key

### 2. Cấu hình Chatbot

1. Mở file `components/chatbot_api.php`
2. Tìm dòng: `$api_key = 'YOUR_GEMINI_API_KEY';`
3. Thay thế `YOUR_GEMINI_API_KEY` bằng API key bạn đã tạo ở bước trên
4. Lưu file

## Tính năng

Chatbot có thể trả lời các loại câu hỏi sau:

### 1. Tìm kiếm phòng trọ xem nhiều nhất

Các cách hỏi:

- "Cho tôi xem top 3 phòng trọ được xem nhiều nhất"
- "Phòng trọ nào phổ biến nhất?"
- "Top 5 phòng trọ xem nhiều nhất"

### 2. Tìm kiếm phòng trọ giá rẻ

Các cách hỏi:

- "Cho tôi xem phòng trọ giá dưới 2 triệu"
- "Có phòng trọ nào rẻ không?"
- "Phòng trọ dưới 1.5 triệu"

### 3. Tìm kiếm phòng trọ gần Đại học Vinh

Các cách hỏi:

- "Phòng trọ gần Đại học Vinh"
- "Có phòng nào gần trường đại học không?"

### 4. Các câu hỏi chung về website

Chatbot cũng có thể trả lời các câu hỏi chung về website PhòngTrọ, ví dụ:

- "Làm thế nào để đăng tin phòng trọ?"
- "Làm sao để đặt phòng?"
- "Cách tìm kiếm phòng trọ trên website?"

## Tùy chỉnh thêm

### Thêm ý định mới

Để thêm các loại câu hỏi mới mà chatbot có thể xử lý:

1. Mở file `components/chatbot_api.php`
2. Tìm mảng `$keywords` và thêm từ khóa mới và ý định tương ứng:
   ```php
   $keywords = [
       // Thêm từ khóa mới
       'từ khóa của bạn' => 'tên_ý_định',
   ];
   ```
3. Thêm case xử lý cho ý định mới trong switch-case:
   ```php
   switch ($intent) {
       // Thêm case mới
       case 'tên_ý_định':
           // Xử lý ý định
           break;
   }
   ```

### Tùy chỉnh giao diện

Để tùy chỉnh giao diện chatbot:

1. Mở file `components/chatbot.php`
2. Chỉnh sửa CSS trong thẻ `<style>` để thay đổi màu sắc, kích thước và vị trí của chatbot

## Gỡ lỗi

Nếu chatbot không hoạt động:

1. Kiểm tra console của trình duyệt để xem lỗi JavaScript
2. Kiểm tra logs của server PHP
3. Đảm bảo API key của Gemini hợp lệ và còn quota
4. Kiểm tra kết nối đến cơ sở dữ liệu

## Lưu ý

- API key Gemini có giới hạn về số lượng yêu cầu. Hãy đảm bảo rằng bạn không vượt quá giới hạn này.
- Chatbot chỉ có thể truy xuất dữ liệu đã có trong cơ sở dữ liệu.
- Đối với một số câu hỏi phức tạp, chatbot có thể cần được cấu hình thêm để xử lý chính xác.

---

Với bất kỳ câu hỏi hoặc vấn đề nào, vui lòng liên hệ với người quản trị website.
