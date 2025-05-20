        </div> <!-- End of page-content -->
        </div> <!-- End of admin-content -->

        <!-- Bootstrap JS and dependencies -->
        <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.min.js"></script>

        <!-- TinyMCE Rich Text Editor -->
        <script src="https://cdn.tiny.cloud/1/no-api-key/tinymce/5/tinymce.min.js" referrerpolicy="origin"></script>

        <!-- Admin Custom JS -->
        <script>
            $(document).ready(function() {
                // Sidebar toggle for mobile
                $('#sidebarToggleBtn').on('click', function() {
                    $('body').toggleClass('sidebar-toggled');
                });

                // Initialize tooltips
                $('[data-toggle="tooltip"]').tooltip();

                // Auto-dismiss alerts after 5 seconds
                setTimeout(function() {
                    $(".alert").alert('close');
                }, 5000);
            });
        </script>
        </body>

        </html>