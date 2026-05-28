# UTC eLibrary - Deploy Runbook (EC2)

Runbook nay gom cac lenh can thiet cho moi dot cap nhat production.

## 1) Up code moi (khuyen dung)

Chay tren EC2:

```bash
cd ~/utc-elibrary
bash scripts/ec2-deploy.sh
```

Kiem tra nhanh:

```bash
docker compose -f docker-compose.ec2.yml ps
docker compose -f docker-compose.ec2.yml exec app php artisan about | head -30
```

## 2) Chi doi `.env` (khong doi code)

Chay tren EC2:

```bash
cd ~/utc-elibrary
bash scripts/ec2-apply-env.sh
```

Neu can, clear lai cache:

```bash
docker compose -f docker-compose.ec2.yml exec app php artisan optimize:clear
docker compose -f docker-compose.ec2.yml exec app php artisan config:cache
```

## 3) Code + `.env` cung luc

```bash
cd ~/utc-elibrary
bash scripts/ec2-deploy.sh
bash scripts/ec2-apply-env.sh
```

## 4) Preview PDF (khi can tao lai)

Tao lai preview cho 1 asset:

```bash
docker compose -f docker-compose.ec2.yml exec -u www-data app php artisan digital-assets:regenerate-previews --asset=<DIGITAL_ASSET_ID> --force
```

Test URL trang 1:

```bash
docker compose -f docker-compose.ec2.yml exec app curl -sI "http://127.0.0.1/tra-cuu-sach/<BOOK_ID>/tai-lieu/<ASSET_ID>/xem-truoc/trang/1.png" | head -5
```

## 5) Debug nhanh khi gap 500

```bash
docker compose -f docker-compose.ec2.yml exec app sh -lc "tail -120 storage/logs/laravel.log"
docker compose -f docker-compose.ec2.yml logs --tail=120 app queue scheduler
```

## 6) "1 lenh" theo tinh huong

Script tong hop:

```bash
bash scripts/release-quick.sh
```

Che do:

- `--code`: pull code + deploy image/container
- `--env`: apply `.env` va clear/cache config
- `--all`: code + env (mac dinh)
- `--smoke`: them check nhanh sau deploy

Vi du:

```bash
bash scripts/release-quick.sh --all --smoke
bash scripts/release-quick.sh --env
```

## 7) Luu y quan trong

- `.env` va secret khong duoc commit Git.
- Chi `git pull` la chua du cho code moi; can `ec2-deploy.sh`.
- Neu dung Cloudflare, sau thay doi frontend nen Purge cache + Ctrl+F5.

## 8) Rollback nhanh (khi deploy loi)

```bash
cd ~/utc-elibrary
git log --oneline -5
git checkout <commit_on_dinh>
bash scripts/ec2-deploy.sh
```

Sau khi he thong on dinh, chuyen lai branch:

```bash
git checkout main
```
