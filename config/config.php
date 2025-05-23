<?php

// Định nghĩa cũ sử dụng đường dẫn vật lý

// Định nghĩa mới sử dụng URL gốc của trang web
// define('PROJECT_ROOT', __DIR__); // Đường dẫn URL gốc của trang web
define('PROJECT_ROOT', dirname(__DIR__)); // Nếu file config ở /config/

// Loại bỏ lệnh debugging
// print_r(PROJECT_ROOT);
