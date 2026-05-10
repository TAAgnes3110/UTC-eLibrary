#!/usr/bin/env bash
set -euo pipefail

BASE_URL="${BASE_URL:-http://127.0.0.1:8000}"
RUNS="${RUNS:-10}"

token_from_tinker() {
  php artisan tinker --execute="echo \PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth::fromUser(\App\Models\User::query()->whereIn('user_type',['admin','super_admin','librarian'])->first());"
}

if [[ -z "${ADMIN_BEARER_TOKEN:-}" ]]; then
  ADMIN_BEARER_TOKEN="$(token_from_tinker)"
fi

if [[ -z "${ADMIN_BEARER_TOKEN}" ]]; then
  echo "Khong tao duoc ADMIN_BEARER_TOKEN."
  exit 1
fi

bench_endpoint() {
  local name="$1"
  local url="$2"
  local mode="${3:-warm}"
  local tmp_file
  tmp_file="$(mktemp)"

  if [[ "${mode}" == "warm" ]]; then
    curl -s -o /dev/null \
      -H "Authorization: Bearer ${ADMIN_BEARER_TOKEN}" \
      "${BASE_URL}${url}"
  fi

  for ((i = 1; i <= RUNS; i++)); do
    if [[ "${mode}" == "cold" ]]; then
      php artisan cache:clear --quiet >/dev/null 2>&1 || true
    fi
    curl -s -o /dev/null \
      -H "Authorization: Bearer ${ADMIN_BEARER_TOKEN}" \
      -w "%{time_starttransfer},%{time_total}\n" \
      "${BASE_URL}${url}" >> "${tmp_file}"
  done

  node -e "
const fs = require('fs');
const lines = fs.readFileSync(process.argv[1], 'utf8').trim().split('\n').filter(Boolean);
const pairs = lines.map((line) => line.split(',').map(Number)).filter((arr) => arr.length === 2 && arr.every((v) => Number.isFinite(v)));
const ttfb = pairs.map((p) => p[0]).sort((a, b) => a - b);
const total = pairs.map((p) => p[1]).sort((a, b) => a - b);
const avg = (arr) => arr.reduce((s, v) => s + v, 0) / arr.length;
const p95 = (arr) => arr[Math.min(arr.length - 1, Math.max(0, Math.ceil(arr.length * 0.95) - 1))];
const ms = (v) => (v * 1000).toFixed(2) + 'ms';
console.log([
  process.argv[2].padEnd(28, ' '),
  'avg_ttfb=' + ms(avg(ttfb)),
  'p95_ttfb=' + ms(p95(ttfb)),
  'avg_total=' + ms(avg(total)),
  'p95_total=' + ms(p95(total)),
].join(' | '));
" "${tmp_file}" "${name}"

  rm -f "${tmp_file}"
}

echo "Benchmark admin APIs (cold cache) | runs=${RUNS} | base_url=${BASE_URL}"
bench_endpoint "books:cold" "/api/v1/books?per_page=20&page=1&sort=newest&resource_type=textbook,reference" "cold"
bench_endpoint "users:cold" "/api/v1/users?per_page=20&page=1" "cold"
bench_endpoint "news-posts:cold" "/api/v1/news-posts?per_page=20&page=1&sort=newest" "cold"
bench_endpoint "library-cards:cold" "/api/v1/library-cards?per_page=20&page=1&management=1&sort_by=newest" "cold"
echo
echo "Benchmark admin APIs (warm cache) | runs=${RUNS} | base_url=${BASE_URL}"
bench_endpoint "books:warm" "/api/v1/books?per_page=20&page=1&sort=newest&resource_type=textbook,reference" "warm"
bench_endpoint "users:warm" "/api/v1/users?per_page=20&page=1" "warm"
bench_endpoint "news-posts:warm" "/api/v1/news-posts?per_page=20&page=1&sort=newest" "warm"
bench_endpoint "library-cards:warm" "/api/v1/library-cards?per_page=20&page=1&management=1&sort_by=newest" "warm"
