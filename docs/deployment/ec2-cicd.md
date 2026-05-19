# CI/CD deploy EC2 (tự động sau push `main`)

Mỗi lần push lên nhánh `main`, GitHub Actions SSH vào EC2 và chạy `scripts/ec2-deploy.sh` (pull → build → migrate).

## 1. Chuẩn bị trên EC2 (một lần)

```bash
# Repo đã clone tại ~/utc-elibrary, Docker đã cài
cd ~/utc-elibrary
chmod +x scripts/ec2-deploy.sh scripts/ec2-prepare-build.sh

# Thử deploy tay
bash scripts/ec2-deploy.sh
```

**Security group EC2:** mở port **22** cho IP GitHub Actions (hoặc tạm `0.0.0.0/0` — nên thu hẹp sau).

**Khuyến nghị:** tạo user deploy hoặc dùng `ubuntu`, khóa SSH `.pem` không commit lên git.

## 2. Cấu hình GitHub Secrets

Vào repo GitHub → **Settings** → **Secrets and variables** → **Actions** → **New repository secret**:

| Secret | Ví dụ | Bắt buộc |
|--------|--------|----------|
| `EC2_HOST` | `3.0.56.220` | Có |
| `EC2_USER` | `ubuntu` | Có |
| `EC2_SSH_KEY` | Nội dung file `.pem` (toàn bộ `-----BEGIN...`) | Có |
| `EC2_SSH_PORT` | `22` | Không |
| `EC2_APP_PATH` | `/home/ubuntu/utc-elibrary` | Không (mặc định path trên) |

Sau khi lưu secret, push lên `main` → tab **Actions** xem workflow **Deploy EC2**.

## 3. Chạy tay trên server (không qua GitHub)

```bash
cd ~/utc-elibrary
bash scripts/ec2-deploy.sh
```

## 4. Tắt auto deploy tạm thời

- Push lên nhánh khác (không phải `main`), hoặc
- GitHub → **Actions** → **Deploy EC2** → **Disable workflow**, hoặc
- Xóa / comment file `.github/workflows/deploy-ec2.yml`

## 5. Cron trên EC2 (không khuyến nghị)

Chỉ dùng nếu không dùng GitHub Actions:

```cron
*/30 * * * * cd /home/ubuntu/utc-elibrary && git pull --ff-only origin main && bash scripts/ec2-deploy.sh >> /home/ubuntu/deploy.log 2>&1
```

Nhược điểm: build mỗi 30 phút dù không có code mới; không có log tập trung như Actions.

## 6. Migration / DB import

Deploy dùng `php artisan migrate:existing-schema --force` thay vì `migrate` thuần — tránh lỗi **Table already exists** khi DB restore từ backup SQL.

## 7. Lưu ý

- Build EC2 t3.micro ~15–25 phút; workflow `timeout-minutes: 45`.
- `.env` **không** do CI sửa — chỉnh trực tiếp trên server.
- Lần đầu sau khi bật CI/CD: chạy `bash scripts/ec2-deploy.sh` tay một lần để chắc `.env` / DB ổn.
