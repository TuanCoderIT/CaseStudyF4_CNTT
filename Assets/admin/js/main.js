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

// Format price with thousand separators
function formatPrice(price) {
    return new Intl.NumberFormat('vi-VN').format(price);
}

// Update the price range filter display
function updatePriceDisplay(minValue, maxValue) {
    const priceDisplay = document.getElementById('price-display');
    if (priceDisplay) {
        if (maxValue >= 999999999) {
            priceDisplay.textContent = `Trên ${formatPrice(minValue)} đ`;
        } else {
            priceDisplay.textContent = `${formatPrice(minValue)} đ - ${formatPrice(maxValue)} đ`;
        }
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
    
    // Mobile filter toggle button
    const mobileFilterToggle = document.getElementById('mobile-filter-toggle');
    const filterSidebar = document.querySelector('.filter-sidebar');
    
    if (mobileFilterToggle && filterSidebar) {
        mobileFilterToggle.addEventListener('click', function() {
            filterSidebar.classList.toggle('show');
            
            // Change icon based on state
            const icon = mobileFilterToggle.querySelector('i');
            if (filterSidebar.classList.contains('show')) {
                icon.classList.remove('fa-filter');
                icon.classList.add('fa-times');
            } else {
                icon.classList.remove('fa-times');
                icon.classList.add('fa-filter');
            }
        });
        
        // Close filters when clicking outside
        document.addEventListener('click', function(e) {
            if (!filterSidebar.contains(e.target) && e.target !== mobileFilterToggle && !mobileFilterToggle.contains(e.target)) {
                filterSidebar.classList.remove('show');
                const icon = mobileFilterToggle.querySelector('i');
                icon.classList.remove('fa-times');
                icon.classList.add('fa-filter');
            }
        });
    }
    
    // Collapsible filter sections for tablet and mobile
    const filterTitles = document.querySelectorAll('.filter-title');
    
    filterTitles.forEach(title => {
        title.addEventListener('click', function() {
            if (window.innerWidth <= 991) {
                const content = this.parentElement.nextElementSibling;
                const toggle = this.querySelector('.filter-toggle');
                
                if (content.style.maxHeight) {
                    content.style.maxHeight = null;
                    toggle.classList.remove('active');
                } else {
                    content.style.maxHeight = content.scrollHeight + 'px';
                    toggle.classList.add('active');
                }
            }
        });
    });

    initFilterAccordion();
    
    // Close filters when clicking the close button
    const closeFilterBtn = document.querySelector('.filter-sidebar .btn-close');
    
    if (closeFilterBtn && filterSidebar) {
        closeFilterBtn.addEventListener('click', function() {
            filterSidebar.classList.remove('show');
        });
    }

    // Check if we're on the home page
    if (document.body.classList.contains('home-body')) {
        setupHomePageAnimations();
    }
    
    // Check if we're on the search page
    if (document.body.classList.contains('search-body')) {
        setupSearchPageAnimations();
    }
    
    // Initialize all animations
    initScrollAnimations();
    
    // Initialize room detail page enhancements
    if (document.body.classList.contains('room-detail-body')) {
        initMobileImageZoom();
        initStickyRoomActions();
    }

    // Khởi tạo hiệu ứng nút yêu thích
    animateFavoriteButton();

    const urlParams = new URLSearchParams(window.location.search);
    const action = urlParams.get('action');
    
    if (action === 'favorite' || action === 'unfavorite') {
        const favoriteCounters = document.querySelectorAll('.favorite-counter');
        favoriteCounters.forEach(counter => {
            counter.classList.add('animate__animated', 'animate__heartBeat');
        });
    }
});

// Handle filter section toggle on mobile/tablet
function initFilterAccordion() {
    const filterTitles = document.querySelectorAll('.filter-title');
    
    filterTitles.forEach(title => {
        title.addEventListener('click', function(e) {
            if (window.innerWidth <= 991) {
                // Get the filter content section
                const content = this.closest('.filter-header').nextElementSibling;
                const toggle = this.querySelector('.filter-toggle');
                
                if (content && toggle) {
                    if (content.style.maxHeight) {
                        content.style.maxHeight = null;
                        toggle.classList.remove('active');
                    } else {
                        content.style.maxHeight = content.scrollHeight + 'px';
                        toggle.classList.add('active');
                    }
                }
            }
        });
    });
}

// Animate elements when they come into view
function initScrollAnimations() {
    const animatedElements = document.querySelectorAll('.animated-element');
    
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('show');
                // Once the animation has played, we can stop observing
                observer.unobserve(entry.target);
            }
        });
    }, {
        root: null, // Use the viewport
        threshold: 0.1, // Trigger when at least 10% of the element is visible
        rootMargin: '0px 0px -50px 0px' // Slightly before the element comes into view
    });
    
    animatedElements.forEach(el => {
        observer.observe(el);
    });
}

// Add animation classes to elements on the home page
function setupHomePageAnimations() {
    const sections = document.querySelectorAll('.home-body main section');
    sections.forEach((section, index) => {
        section.classList.add('animated-element');
        section.style.animationDelay = `${index * 0.2}s`;
    });
    
    const cards = document.querySelectorAll('.room-card');
    cards.forEach((card, index) => {
        card.classList.add('animated-element');
        card.style.animationDelay = `${(index % 4) * 0.1}s`;
    });
}

// Add animation classes to search page elements
function setupSearchPageAnimations() {
    const sidebar = document.querySelector('.filter-sidebar');
    if (sidebar) {
        sidebar.classList.add('animated-element');
    }
    
    const cards = document.querySelectorAll('.search-body .room-card');
    cards.forEach((card, index) => {
        card.classList.add('animated-element');
        card.style.animationDelay = `${(index % 3) * 0.1}s`;
    });
}

// Mobile image zoom functionality for room detail page
function initMobileImageZoom() {
    // Only run on mobile devices
    if (window.innerWidth <= 767) {
        const galleryImages = document.querySelectorAll('.room-gallery .swiper-slide img');
        
        galleryImages.forEach(img => {
            // Add mobile-zoom class to each image
            img.classList.add('mobile-zoom');
            
            // Add click event
            img.addEventListener('click', function() {
                // Create a fullscreen container
                const zoomContainer = document.createElement('div');
                zoomContainer.className = 'mobile-zoom active';
                
                // Create close button
                const closeButton = document.createElement('button');
                closeButton.className = 'zoom-close';
                closeButton.innerHTML = '<i class="fas fa-times"></i>';
                
                // Clone the image
                const zoomedImg = this.cloneNode(true);
                
                // Append elements
                zoomContainer.appendChild(zoomedImg);
                zoomContainer.appendChild(closeButton);
                document.body.appendChild(zoomContainer);
                
                // Add no-scroll class to body
                document.body.style.overflow = 'hidden';
                
                // Close functionality
                closeButton.addEventListener('click', function() {
                    zoomContainer.remove();
                    document.body.style.overflow = '';
                });
                
                // Also close on click anywhere
                zoomContainer.addEventListener('click', function(e) {
                    if (e.target === zoomContainer) {
                        zoomContainer.remove();
                        document.body.style.overflow = '';
                    }
                });
            });
        });
    }
}

// Add sticky action bar for mobile on room detail page
function initStickyRoomActions() {
    if (window.innerWidth <= 767 && document.body.classList.contains('room-detail-body')) {
        const roomActions = document.querySelector('.room-actions');
        
        if (roomActions) {
            // Create a clone of the actions for the sticky bar
            const stickyActions = document.createElement('div');
            stickyActions.className = 'room-actions-sticky';
            
            // Get all action buttons and clone them
            const actionButtons = roomActions.querySelectorAll('.btn');
            actionButtons.forEach(btn => {
                const cloneBtn = btn.cloneNode(true);
                // Simplify text for mobile
                const btnText = cloneBtn.textContent.trim();
                const btnIcon = cloneBtn.querySelector('i').outerHTML;
                
                if (btnText.includes('Liên hệ')) {
                    cloneBtn.innerHTML = `${btnIcon} Liên hệ`;
                } else if (btnText.includes('Chia sẻ')) {
                    cloneBtn.innerHTML = `${btnIcon} Chia sẻ`;
                } else if (btnText.includes('Báo cáo')) {
                    cloneBtn.innerHTML = `${btnIcon} Báo cáo`;
                }
                
                stickyActions.appendChild(cloneBtn);
            });
            
            // Append to body
            document.body.appendChild(stickyActions);
        }
    }
}

// Hiệu ứng cho nút yêu thích
function animateFavoriteButton() {
    const favoriteButton = document.querySelector('.favorite-btn');
    if (favoriteButton) {
        favoriteButton.addEventListener('click', function(e) {
            const icon = this.querySelector('i');
            const isAdding = icon.classList.contains('far'); // Kiểm tra nếu đang thêm yêu thích
            
            // Hiệu ứng khi nhấn nút
            this.classList.add('btn-pulse');
            setTimeout(() => {
                this.classList.remove('btn-pulse');
            }, 500);
            
            // Hiển thị thông báo nổi tạm thời
            const toastMessage = isAdding ? 'Đã thêm vào danh sách yêu thích!' : 'Đã xóa khỏi danh sách yêu thích!';
            const toastClass = isAdding ? 'bg-success' : 'bg-warning text-dark';
            
            const toast = document.createElement('div');
            toast.className = `toast-notification ${toastClass} animate__animated animate__fadeIn`;
            toast.innerHTML = `<i class="fas ${isAdding ? 'fa-check-circle' : 'fa-exclamation-circle'} me-2"></i>${toastMessage}`;
            
            document.body.appendChild(toast);
            
            setTimeout(() => {
                toast.classList.remove('animate__fadeIn');
                toast.classList.add('animate__fadeOut');
                setTimeout(() => {
                    toast.remove();
                }, 500);
            }, 2000);
        });
    }
}

// Hiệu ứng cho favorite counter trong header
function updateFavoriteCounter(count) {
    const counter = document.querySelector('.favorite-counter');
    if (counter) {
        // Lưu giá trị cũ
        const oldValue = parseInt(counter.textContent);
        
        // Cập nhật giá trị mới
        counter.textContent = count;
        
        // Thêm hiệu ứng dựa trên thay đổi
        if (count > oldValue) {
            counter.classList.add('animate__animated', 'animate__heartBeat');
        } else if (count < oldValue) {
            counter.classList.add('animate__animated', 'animate__fadeOut');
            setTimeout(() => {
                counter.classList.remove('animate__fadeOut');
                counter.classList.add('animate__fadeIn');
            }, 300);
        }
        
        // Xóa class sau khi animation hoàn tất
        setTimeout(() => {
            counter.classList.remove('animate__heartBeat', 'animate__fadeIn');
        }, 1000);
    }
}