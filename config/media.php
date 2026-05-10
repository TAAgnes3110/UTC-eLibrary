<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Ảnh công khai: nguồn và đích migrate
    |--------------------------------------------------------------------------
    */
    'source_disk' => env('MEDIA_SOURCE_DISK', 'public'),
    'target_disk' => env('MEDIA_DISK', 'public'),

    /*
    |--------------------------------------------------------------------------
    | Danh sách bảng/cột cần migrate ảnh
    |--------------------------------------------------------------------------
    |
    | Chỉ map các cột dạng ảnh. Cột file tài liệu (PDF/doc) không nằm ở đây.
    |
    */
    'image_migration_targets' => [
        ['table' => 'users', 'column' => 'avatar'],
        ['table' => 'books', 'column' => 'cover_image'],
        ['table' => 'library_cards', 'column' => 'photo_path'],
        ['table' => 'news_posts', 'column' => 'thumbnail_path'],
        ['table' => 'digital_document_submissions', 'column' => 'cover_image_path'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Ảnh mặc định (fallback)
    |--------------------------------------------------------------------------
    |
    | Có thể là URL tuyệt đối hoặc path trong public.
    |
    */
    'defaults' => [
        'news_thumbnail' => 'images/default-news-cover.jpg',
        'book_cover' => 'images/default-book-cover.png',
        'avatar' => 'images/default-avatar.png',
        'library_card_photo' => 'images/default-avatar.png',
    ],
];
