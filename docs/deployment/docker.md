# Chạy UTC-eLibrary bằng Docker

Một lệnh chạy **Nginx + PHP 8.3 + MySQL 8 + Redis + Laravel scheduler** — phù hợp máy dev, VPS, hoặc **EC2 AWS** (cài Docker thay vì cài tay từng gói).

## Yêu cầu

- [Docker](https://docs.docker.com/get-docker/) 24+
- [Docker Compose](https://docs.docker.com/compose/install/) v2

## Lần đầu (local hoặc EC2)

```bash
cd UTC-eLibrary

cp .env.docker.example .env
# Sửa .env nếu cần: APP_URL, mật khẩu DB, SePay, Azure…

docker compose up -d --build

docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate
# docker compose exec app php artisan db:seed   # tùy chọn
```

Mở trình duyệt: **http://localhost** (hoặc `http://<IP-EC2>` nếu deploy AWS).

Port khác 80: trong `.env` đặt `APP_PORT=8080` rồi `docker compose up -d` → http://localhost:8080

## Lệnh thường dùng

```bash
docker compose ps
docker compose logs -f app
docker compose exec app php artisan optimize:clear
docker compose exec app php artisan digital-assets:regenerate-previews

# Cập nhật code
git pull
docker compose up -d --build
docker compose exec app php artisan migrate --force
```

## Deploy lên EC2 AWS (Free Tier)

Giả định trên Windows:

- File khóa SSH: **`D:\AWS\utc-elibrary.pem`** (file `.pem` tải khi tạo key pair — đặt vào `D:\AWS`)
- Mã nguồn: **`D:\UTC-eLibrary`**
- Trong **Git Bash**, `D:\` viết thành **`/d/`**

Thay `<PUBLIC_IP>` bằng IPv4 của instance (EC2 → Instances).

### 1. SSH vào EC2 (Git Bash trên Windows)

```bash
chmod 400 /d/AWS/utc-elibrary.pem
ssh -i /d/AWS/utc-elibrary.pem ubuntu@<PUBLIC_IP>
```

### 2. Trên EC2 — cài Docker

```bash
sudo apt update
sudo apt install -y docker.io docker-compose-v2 git
sudo usermod -aG docker ubuntu
exit
```

SSH **lại** (lệnh ở bước 1).

### 3. Đưa code lên EC2

**Cách A — Git** (trên EC2):

```bash
git clone https://github.com/TAAgnes3110/UTC-eLibrary.git utc-elibrary
cd utc-elibrary
```

**Cách B — Upload từ Windows** (Git Bash, **không** cần SSH):

```bash
scp -i /d/AWS/utc-elibrary.pem -r /d/UTC-eLibrary ubuntu@<PUBLIC_IP>:~/utc-elibrary
```

Rồi SSH vào EC2: `cd ~/utc-elibrary`

### 4. Chạy Docker trên EC2

```bash
cp .env.docker.example .env
nano .env   # APP_URL=http://<PUBLIC_IP>, DB_PASSWORD=…, giữ R2_* nếu dùng Cloudflare

docker compose up -d --build
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate --force
```

Mở: **http://&lt;PUBLIC_IP&gt;**

### 5. Import DB từ InfinityFree (Windows → EC2)

Trên Windows (đã có `backup.sql`):

```bash
scp -i /d/AWS/utc-elibrary.pem backup.sql ubuntu@<PUBLIC_IP>:~/
```

Trên EC2 (`MatKhauDbManh123!` = `DB_PASSWORD` trong `.env`):

```bash
cd ~/utc-elibrary
docker compose exec -T mysql mysql -u utc -pMatKhauDbManh123! utc_elibrary < ~/backup.sql
```

### 6. (Tùy chọn) Elastic IP + domain + HTTPS

## Cấu trúc services

| Service | Vai trò |
|---------|---------|
| `app` | Nginx + PHP-FPM, port 80 |
| `mysql` | Database |
| `redis` | Cache, session, queue |
| `scheduler` | `php artisan schedule:work` (cron nghiệp vụ) |

Dữ liệu persist: volumes `mysql_data`, `redis_data`, `app_storage`.

## S3 thay media local

Trong `.env` (sau khi tạo bucket IAM):

```env
MEDIA_DISK=s3
AWS_ACCESS_KEY_ID=...
AWS_SECRET_ACCESS_KEY=...
AWS_DEFAULT_REGION=ap-southeast-1
AWS_BUCKET=utc-elibrary-media
AWS_URL=https://...
```

```bash
docker compose exec app php artisan media:migrate-images
```

## Import DB từ InfinityFree

```bash
# Trên máy có file backup.sql
docker compose exec -T mysql mysql -u utc -psecret utc_elibrary < backup.sql
```

## Ghi chú

- PDF preview cần **qpdf**, **ghostscript**, **poppler** — đã có trong image.
- `DIGITAL_PREVIEW_DISPATCH_SYNC=true` mặc định — không cần container `queue:work` riêng.
- Build frontend (`npm run build`) chạy **trong Dockerfile** — không cần Node trên host.
