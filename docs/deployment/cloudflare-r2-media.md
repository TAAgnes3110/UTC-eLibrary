# Cấu hình Cloudflare R2 cho media (ảnh)

Tài liệu này hướng dẫn cấu hình R2 để đưa ảnh ra ngoài source/server local, dùng với command:

- `php artisan media:migrate-images`

## 1) Tạo bucket và API token trên Cloudflare

1. Vào **Cloudflare Dashboard** -> **R2**.
2. Tạo bucket, ví dụ: `utc-elibrary-media`.
3. Tạo API Token có quyền:
   - `Object Read`
   - `Object Write`
4. Lấy thông tin:
   - `Access Key ID`
   - `Secret Access Key`
   - `S3 API endpoint` (dạng `https://<ACCOUNT_ID>.r2.cloudflarestorage.com`)

## 2) Cấu hình .env

Mở file `.env` và điền:

```env
MEDIA_DISK=r2
MEDIA_SOURCE_DISK=public

R2_ACCESS_KEY_ID=your_access_key_id
R2_SECRET_ACCESS_KEY=your_secret_access_key
R2_DEFAULT_REGION=auto
R2_BUCKET=utc-elibrary-media
R2_ENDPOINT=https://<ACCOUNT_ID>.r2.cloudflarestorage.com
R2_URL=https://pub-<your-public-id>.r2.dev
R2_USE_PATH_STYLE_ENDPOINT=false
```

Ghi chú:

- `R2_URL` nên dùng domain public của bucket (`*.r2.dev`) hoặc custom domain CDN.
- Nếu chưa public bucket, URL ảnh sẽ không truy cập được từ trình duyệt.

## 3) Publish bucket (public read)

Trong R2 bucket:

1. Mở tab **Settings**.
2. Bật chế độ public (hoặc gắn custom domain public).
3. Kiểm tra một object bất kỳ có thể truy cập bằng URL public.

## 4) Clear config cache

```bash
php artisan optimize:clear
```

## 5) Dry-run trước khi migrate thật

```bash
php artisan media:migrate-images --dry-run
```

Kỳ vọng:

- Command chạy xong và trả thống kê `migrated/skipped/missing/failed`.
- Không ghi file khi đang `--dry-run`.

## 6) Migrate thật

```bash
php artisan media:migrate-images
```

Tuỳ chọn hữu ích:

```bash
# Chỉ migrate avatar user
php artisan media:migrate-images --only=users

# Ghi đè file đã tồn tại trên R2
php artisan media:migrate-images --overwrite

# Điều chỉnh batch size
php artisan media:migrate-images --chunk=500
```

## 7) Rollout an toàn

Khuyến nghị rollout:

1. Migrate trước trên staging.
2. Verify trang admin có ảnh:
   - User avatar
   - Ảnh thẻ thư viện
   - Ảnh bìa tin tức/sách
3. Sau khi xác nhận OK, chạy production migrate.

## 8) Lỗi thường gặp

- **Ảnh 403/404**: bucket chưa public hoặc `R2_URL` sai.
- **Command báo missing nhiều**: dữ liệu DB đang là URL tuyệt đối hoặc path không nằm ở disk nguồn.
- **Ảnh vẫn trỏ local**: quên `MEDIA_DISK=r2` hoặc chưa `optimize:clear`.
