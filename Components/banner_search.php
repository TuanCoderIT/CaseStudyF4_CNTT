<link rel="stylesheet" href="../Assets/style.css">
<section class="search-banner">
    <div class="container py-5">
        <div class="row">
            <div class="col-lg-10 mx-auto">
                <div class="search-container">
                    <h1 class="text-center mb-4">Tìm phòng trọ phù hợp với bạn</h1>
                    <form action="search.php" method="GET" class="search-form">
                        <div class="row g-3">
                            <div class="col-md-10">
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-search"></i></span>
                                    <input type="text" name="keyword" class="form-control form-control-lg" placeholder="Nhập địa điểm, tên đường...">
                                </div>
                            </div>
                            <div class="col-md-2">
                                <button type="submit" class="btn btn-primary btn-lg w-100">
                                    <i class="fas fa-search me-2"></i>Tìm kiếm
                                </button>
                            </div>
                        </div>
                    </form>

                    <!-- Quick filter links -->
                    <div class="row mt-4">
                        <div class="col-12 text-center">
                            <div class="btn-group" role="group">
                                <a href="search.php?sort=view" class="btn btn-light btn-lg">
                                    <i class="fas fa-fire text-danger me-2"></i>Xem nhiều nhất
                                </a>
                                <a href="search.php?sort=newest" class="btn btn-light btn-lg">
                                    <i class="fas fa-clock text-success me-2"></i>Mới đăng tải
                                </a>
                                <a href="search.php?sort=nearest" class="btn btn-light btn-lg">
                                    <i class="fas fa-university text-primary me-2"></i>Gần trường ĐH Vinh
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>