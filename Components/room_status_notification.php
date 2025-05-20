<?php
// Check for room status change notifications set in session
function getRoomStatusNotification()
{
    if (isset($_SESSION['room_status_change'])) {
        $notification = $_SESSION['room_status_change'];
        unset($_SESSION['room_status_change']); // Clear after showing

        $icon = '';
        $bgColor = '';

        switch ($notification['status']) {
            case 0:
                $icon = 'fas fa-clock';
                $bgColor = 'warning';
                $statusText = 'chờ duyệt';
                break;
            case 1:
                $icon = 'fas fa-check-circle';
                $bgColor = 'success';
                $statusText = 'đã duyệt';
                break;
            case 2:
                $icon = 'fas fa-ban';
                $bgColor = 'danger';
                $statusText = 'đã hủy';
                break;
            default:
                $icon = 'fas fa-info-circle';
                $bgColor = 'info';
                $statusText = 'đã thay đổi trạng thái';
        }

        // Format the HTML for the notification
        $html = '
        <div class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 1080;">
            <div id="roomStatusToast" class="toast show" role="alert" aria-live="assertive" aria-atomic="true" data-bs-autohide="true" data-bs-delay="5000">
                <div class="toast-header bg-' . $bgColor . ' text-white">
                    <i class="' . $icon . ' me-2"></i>
                    <strong class="me-auto">Thông báo trạng thái phòng</strong>
                    <small>' . date('H:i') . '</small>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
                <div class="toast-body">
                    <p class="mb-0">Phòng "<strong>' . $notification['title'] . '</strong>" đã được chuyển sang trạng thái <strong>' . $statusText . '</strong>.</p>
                </div>
            </div>
        </div>';

        return $html;
    }

    return '';
}
?>

<!-- Render notification if any -->
<?php echo getRoomStatusNotification(); ?>

<script>
    // Auto-close toast after 5 seconds
    document.addEventListener('DOMContentLoaded', function() {
        const roomStatusToast = document.getElementById('roomStatusToast');
        if (roomStatusToast) {
            setTimeout(() => {
                const bsToast = bootstrap.Toast.getInstance(roomStatusToast) || new bootstrap.Toast(roomStatusToast);
                bsToast.hide();
            }, 5000);
        }
    });
</script>