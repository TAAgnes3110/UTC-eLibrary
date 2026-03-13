@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row">
        <!-- Sidebar bên trái: Danh mục và thống kê -->
        <div class="col-md-3">
            <!-- Danh mục sách -->
            <div class="card mb-4 shadow-sm">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Danh mục sách</h5>
                    <a href="{{ route('home') }}" class="btn btn-sm btn-light">
                        <i class="fas fa-list me-1"></i>Tất cả
                    </a>
                </div>
                <div class="card-body p-0">
                    <ul class="list-group list-group-flush">
                        @foreach($danhMucs as $dm)
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <a href="{{ route('danh-muc.filter', $dm->id) }}" class="text-decoration-none w-100 d-flex justify-content-between">
                                    <span>{{ $dm->ten_danh_muc }}</span>
                                    <span class="badge bg-primary rounded-pill">{{ $dm->sachs_count }}</span>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>
                <!-- Thêm nút xem tất cả ở dưới danh sách -->
                <div class="card-footer bg-white text-center">
                    <a href="{{ route('home') }}" class="btn btn-outline-primary btn-sm w-100">
                        <i class="fas fa-book-open me-1"></i>Xem tất cả sách
                    </a>
                </div>
            </div>

            <!-- Thống kê -->
            <div class="card mb-4 shadow-sm">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0">Thống kê</h5>
                </div>
                <div class="card-body p-0">
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            Tổng số danh mục
                            <span class="badge bg-info rounded-pill">{{ count($danhMucs) }}</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            Tổng số sách
                            <span class="badge bg-success rounded-pill">{{ $totalBooks ?? $sachs->total() }}</span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Nội dung chính bên phải: Danh sách sách -->
        <div class="col-md-9">
            <div class="card mb-4 shadow-sm">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <div>
                        @if(isset($danhMuc))
                            <h4 class="mb-0">Sách thuộc danh mục: {{ $danhMuc->ten_danh_muc }}</h4>
                        @elseif(isset($query))
                            <h4 class="mb-0">Kết quả tìm kiếm cho: "{{ $query }}"</h4>
                        @else
                            <h4 class="mb-0">Sách mới nhất</h4>
                        @endif
                    </div>
                    @if(isset($danhMuc))
                        <a href="{{ route('home') }}" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left me-1"></i>Quay lại tất cả
                        </a>
                    @endif
                </div>
                <div class="card-body">
                    <div class="row row-cols-1 row-cols-md-2 row-cols-lg-4 g-4">
                        @foreach($sachs as $sach)
                            <div class="col">
                                <div class="card h-100 book-card shadow-sm">
                                    <img src="{{ $sach->hinh_anh ? asset('storage/'.$sach->hinh_anh) : 'https://via.placeholder.com/300x450?text=No+Image' }}" 
                                        class="card-img-top book-img" alt="{{ $sach->tieu_de }}">
                                    <div class="card-body">
                                        <h5 class="card-title">{{ Str::limit($sach->tieu_de, 30) }}</h5>
                                        <p class="card-text text-muted">{{ $sach->tacGia->ten_tac_gia }}</p>
                                        <span class="badge bg-primary">{{ $sach->danhMuc->ten_danh_muc }}</span>
                                    </div>
                                    <div class="card-footer bg-white border-top-0">
                                        <a href="{{ route('sach.detail', $sach->id) }}" class="btn btn-outline-primary w-100 mb-2">Xem chi tiết</a>
                                        <div class="text-end">
                                            <span class="text-danger fw-bold book-price">{{ number_format($sach->gia) }} VNĐ</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
                <div class="card-footer bg-white">
                    {{ $sachs->links('pagination::bootstrap-5') }}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('styles')
<style>
    /* Định dạng cho card sách */
    .book-card {
        transition: transform 0.3s, box-shadow 0.3s;
        border: none;
    }
    
    .book-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 20px rgba(0,0,0,0.15) !important;
    }
    
    /* Định dạng cho hình ảnh sách */
    .book-img {
        height: 200px;
        object-fit: cover;
    }
    
    /* Định dạng cho tiêu đề sách */
    .card-title {
        font-weight: 600;
        margin-bottom: 0.5rem;
        min-height: 48px;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }
    
    /* Định dạng cho card footer */
    .card-footer {
        padding: 1rem;
        background-color: white;
    }
    
    /* Định dạng cho giá sách */
    .book-price {
        font-size: 1.1rem;
    }
    
    /* Định dạng cho danh mục */
    .list-group-item {
        transition: all 0.2s;
        border-left: 3px solid transparent;
    }
    
    .list-group-item:hover {
        background-color: #f8f9fa;
        border-left: 3px solid #0d6efd;
    }
    
    .list-group-item a {
        color: #212529;
    }
    
    .list-group-item:hover a {
        color: #0d6efd;
    }
    
    /* Định dạng cho phân trang */
    .pagination {
        justify-content: center;
        margin-bottom: 0;
    }
    
    .pagination .page-item:first-child .page-link,
    .pagination .page-item:last-child .page-link {
        border-radius: 0.25rem;
    }
    
    .page-link {
        color: #0d6efd;
        background-color: #fff;
        border: 1px solid #dee2e6;
        padding: 0.5rem 0.75rem;
    }
    
    .page-item.active .page-link {
        background-color: #0d6efd;
        border-color: #0d6efd;
    }
    
    /* Thêm shadow cho các card */
    .shadow-sm {
        box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075) !important;
    }

    /* Định dạng cho nút "Xem tất cả" */
    .btn-outline-primary {
        transition: all 0.3s;
    }
    
    .btn-outline-primary:hover {
        background-color: #0d6efd;
        color: white;
        transform: translateY(-2px);
    }
</style>
@endsection