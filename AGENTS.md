# UTC eLibrary — hướng dẫn agent

Dự án thư viện số **Đại học Giao thông Vận tải (UTC)**. Stack: Laravel 12 + Vue 3 Inertia.

## Đọc trước khi code

1. `docs/ai/context-utc-library.md` — nghiệp vụ mượn/trả, thẻ, tài liệu số
2. `.cursor/rules/utc-elibrary-core.mdc` — quy tắc cốt lõi (luôn áp dụng)
3. Skill `.cursor/skills/utc-elibrary/SKILL.md` — search-first trong repo

## ECC (Everything Claude Code)

Repo tích hợp [affaan-m/ECC](https://github.com/affaan-m/ECC): agents trong `.cursor/agents/ecc-*.md`, skills Laravel/frontend trong `.cursor/skills/`, commands trong `.cursor/commands/`. Cập nhật: `docs/ai/ecc/README.md`.

Gợi ý agent theo việc:

| Việc | Agent ECC |
|------|-----------|
| Lỗi build / PHP | `ecc-build-error-resolver`, `ecc-php-reviewer` (nếu có) |
| Review sau sửa | `ecc-code-reviewer` |
| Kiến trúc / refactor lớn | `ecc-architect`, `ecc-planner` |
| E2E Playwright | `ecc-e2e-runner` |
| Bảo mật | `ecc-security-reviewer` |

## Kiểm tra trước merge

```bash
vendor/bin/pint
php artisan test
npm run build
```

## Ngôn ngữ

Mặc định **tiếng Việt** cho UI, API message, chat agent — trừ khi user yêu cầu khác.
