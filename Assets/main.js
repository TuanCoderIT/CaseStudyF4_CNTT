// Hàm hiển thị/ẩn mật khẩu
function togglePassword() {
    const passwordField = document.getElementById('password');
    const eyeIcon = document.querySelector('.password-toggle i');
    
    if (passwordField.type === 'password') {
        passwordField.type = 'text';
        eyeIcon.classList.remove('fa-eye');
        eyeIcon.classList.add('fa-eye-slash');
    } else {
        passwordField.type = 'password';
        eyeIcon.classList.remove('fa-eye-slash');
        eyeIcon.classList.add('fa-eye');
    }
}

// Hàm để xem trước ảnh trước khi upload
function previewImage(input) {
    if (input.files && input.files[0]) {
        var reader = new FileReader();
        
        reader.onload = function(e) {
            document.getElementById('avatar-preview').src = e.target.result;
        };
        
        reader.readAsDataURL(input.files[0]);
    }
}

// Hiệu ứng cho form input và các trường nhập liệu
document.addEventListener('DOMContentLoaded', function() {
    // Hiệu ứng cho các trường input khi focus
    const inputs = document.querySelectorAll('.form-control');
    
    inputs.forEach(input => {
        input.addEventListener('focus', function() {
            this.parentElement.classList.add('input-focus');
        });
        
        input.addEventListener('blur', function() {
            this.parentElement.classList.remove('input-focus');
        });
    });

    // Hiệu ứng khi hover vào form fields (chỉ áp dụng cho trang đăng ký)
    if (document.body.classList.contains('register-body')) {
        const formGroups = document.querySelectorAll('.mb-3, .mb-4');
        formGroups.forEach(group => {
            group.addEventListener('mouseenter', function() {
                this.style.transform = 'translateX(5px)';
                this.style.transition = 'all 0.3s ease';
            });
            
            group.addEventListener('mouseleave', function() {
                this.style.transform = 'translateX(0)';
            });
        });
    }
    
    // Hiệu ứng cho trang chỉnh sửa hồ sơ
    if (document.body.classList.contains('profile-body')) {
        // Hiệu ứng cho nút upload ảnh
        const fileUpload = document.querySelector('.custom-file-upload');
        if (fileUpload) {
            fileUpload.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-2px)';
            });
            
            fileUpload.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0)';
            });
        }
        
        // Hiệu ứng cho ảnh đại diện
        const avatarPreview = document.getElementById('avatar-preview');
        if (avatarPreview) {
            avatarPreview.addEventListener('mouseenter', function() {
                this.style.transform = 'scale(1.05)';
            });
            
            avatarPreview.addEventListener('mouseleave', function() {
                this.style.transform = 'scale(1)';
            });
        }
    }
});