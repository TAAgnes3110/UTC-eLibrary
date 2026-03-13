<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ config('app.name', 'Thư Viện Sách') }} - @yield('title')</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
    <style>
        body {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        
        main {
            flex: 1;
            padding-top: 70px; /* Đảm bảo nội dung không bị che bởi navbar */
        }
        
        .navbar-brand {
            font-weight: bold;
            font-size: 1.5rem;
        }
        
        .book-card {
        transition: transform 0.3s, box-shadow 0.3s;
        border: none;
        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    }
        
        .book-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
        
        .book-img {
        height: 250px;
        object-fit: cover;
        border-top-left-radius: 0.375rem;
        border-top-right-radius: 0.375rem;
    }
        
        footer {
            margin-top: auto;
        }

        /* Style cho navbar fixed */
        .navbar {
            position: fixed !important; /* Force fixed position */
            top: 0 !important;
            width: 100% !important;
            z-index: 1000 !important;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1) !important;
        }

        /* Style cho view-all-btn */
        .view-all-btn {
            padding: 8px 16px;
            border-radius: 4px;
            background-color: #f8f9fa;
            color: #0d6efd;
            border: 1px solid #0d6efd;
            transition: all 0.3s;
            font-weight: 500;
            text-decoration: none;
        }
        
        .view-all-btn:hover {
            background-color: #0d6efd;
            color: white;
        }
        
        /* Style cho card-footer và giá */
        .card-footer {
        padding: 0.75rem 1.25rem;
        background-color: white;
        border-top: 1px solid rgba(0,0,0,0.05);
    }
        
        .card-title {
        font-weight: 600;
        margin-bottom: 0.5rem;
        height: 40px;
        overflow: hidden;
    }

        .book-price {
            color: #dc3545;
            font-weight: bold;
            font-size: 1.1rem;
            text-align: right;
            margin-bottom: 10px;
        }

        .list-group-item {
        transition: background-color 0.2s;
        border-left: 3px solid transparent;
    }
    
    .list-group-item:hover {
        background-color: #f8f9fa;
        border-left: 3px solid #0d6efd;
    }

    .text-danger.fw-bold {
        font-size: 1.1rem;
    }
    
    /* Cải thiện phân trang */
    .pagination {
        margin-top: 2rem;
        margin-bottom: 1rem;
    }
    
    .page-link {
        color: #0d6efd;
        background-color: #fff;
        border: 1px solid #dee2e6;
    }
    
    .page-item.active .page-link {
        background-color: #0d6efd;
        border-color: #0d6efd;
    }

    /* Cải thiện form */
    .form-control:focus {
        border-color: #86b7fe;
        box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
    }
    
    .btn-outline-primary {
        border-color: #0d6efd;
        color: #0d6efd;
    }
    
    .btn-outline-primary:hover {
        background-color: #0d6efd;
        color: white;
    }
    
    /* Nút xem chi tiết */
    .btn-outline-primary {
        padding: 0.375rem 0.75rem;
        border-radius: 0.25rem;
        transition: all 0.15s ease-in-out;
    }
    </style>
    
    @yield('styles')
</head>
<body>
    <header>
        <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
            <div class="container">
                <a class="navbar-brand" href="{{ route('home') }}">
                    <i class="fas fa-book-open me-2"></i>Thư Viện Sách
                </a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbarNav">
                    <ul class="navbar-nav me-auto">
                        <li class="nav-item">
                            <a class="nav-link" href="{{ route('home') }}">Trang chủ</a>
                        </li>
                        @auth
                            @if(auth()->user()->isAdmin() || auth()->user()->isThuThu())
                                <li class="nav-item">
                                    <a class="nav-link" href="{{ route('admin.dashboard') }}">Quản lý</a>
                                </li>
                            @endif
                        @endauth
                    </ul>
                    
                    <form class="d-flex me-2" action="{{ route('search') }}" method="GET">
                        <input class="form-control me-2" type="search" name="query" placeholder="Tìm kiếm sách..." value="{{ request('query') }}">
                        <button class="btn btn-light" type="submit"><i class="fas fa-search"></i></button>
                    </form>
                    
                    <ul class="navbar-nav">
                        @guest
                            <li class="nav-item">
                                <a class="nav-link" href="{{ route('login') }}">Đăng nhập</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="{{ route('register') }}">Đăng ký</a>
                            </li>
                        @else
                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                                    {{ Auth::user()->name }}
                                </a>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li>
                                        <a class="dropdown-item" href="{{ route('logout') }}"
                                           onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                                            Đăng xuất
                                        </a>
                                        <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                                            @csrf
                                        </form>
                                    </li>
                                </ul>
                            </li>
                        @endguest
                    </ul>
                </div>
            </div>
        </nav>

        <!-- Thêm banner ở đây -->
@include('partials.banner')
    </header>

    <main class="py-4">
        <div class="container">
            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif
            
            @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show">
                    {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif
            
            @yield('content')
        </div>
    </main>

    <footer class="bg-dark text-white py-4">
        <div class="container">
            <div class="row">
                <div class="col-md-4">
                    <h5>Thư Viện Sách</h5>
                    <p>Nơi kết nối tri thức và đam mê đọc sách</p>
                </div>
                <div class="col-md-4">
                    <h5>Liên hệ</h5>
                    <p><i class="fas fa-map-marker-alt me-2"></i> 123 Đường ABC, Quận XYZ, TP. Hồ Chí Minh</p>
                    <p><i class="fas fa-phone me-2"></i> (028) 1234 5678</p>
                    <p><i class="fas fa-envelope me-2"></i> info@thuviensach.com</p>
                </div>
                <div class="col-md-4">
                    <h5>Giờ mở cửa</h5>
                    <p>Thứ Hai - Thứ Sáu: 8:00 - 20:00</p>
                    <p>Thứ Bảy - Chủ Nhật: 9:00 - 18:00</p>
                </div>
            </div>
            <hr>
            <div class="text-center">
                <p>&copy; {{ date('Y') }} Thư Viện Sách. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    @yield('scripts')
</body>
</html>