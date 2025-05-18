<section class="search-banner">
    <div class="container">
        <div class="row">
            <div class="col-lg-10 mx-auto">
                <div class="search-container">
                    <h1 class="text-center mb-4">Tìm phòng trọ phù hợp với bạn</h1>
                    <form action="search.php" method="GET" class="search-form">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-map-marker-alt"></i></span>
                                    <select name="district" class="form-select">
                                        <option value="">Chọn khu vực</option>
                                        <option value="1">Quận Hồng Bàng</option>
                                        <option value="2">Quận Lê Chân</option>
                                        <option value="3">Quận Ngô Quyền</option>
                                        <option value="4">Quận Kiến An</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-dollar-sign"></i></span>
                                    <select name="price" class="form-select">
                                        <option value="">Chọn khoảng giá</option>
                                        <option value="0-1000000">Dưới 1 triệu</option>
                                        <option value="1000000-2000000">1 - 2 triệu</option>
                                        <option value="2000000-3000000">2 - 3 triệu</option>
                                        <option value="3000000-5000000">3 - 5 triệu</option>
                                        <option value="5000000-999999999">Trên 5 triệu</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="fas fa-search me-2"></i>Tìm kiếm
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>