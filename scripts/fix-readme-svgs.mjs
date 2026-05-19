import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..');
const assets = path.join(root, 'readme/assets');

const files = {
  'architecture.svg': `<?xml version="1.0" encoding="UTF-8"?>
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 920 420" font-family="Segoe UI, system-ui, sans-serif">
  <defs>
    <linearGradient id="bg" x1="0" y1="0" x2="1" y2="1">
      <stop offset="0%" stop-color="#eff6ff"/>
      <stop offset="100%" stop-color="#f8fafc"/>
    </linearGradient>
    <marker id="arrow" markerWidth="8" markerHeight="8" refX="7" refY="4" orient="auto">
      <path d="M0,0 L8,4 L0,8 Z" fill="#64748b"/>
    </marker>
  </defs>
  <rect width="920" height="420" fill="url(#bg)" rx="12"/>
  <text x="460" y="36" text-anchor="middle" font-size="20" font-weight="700" fill="#0f172a">UTC eLibrary — Kiến trúc tổng quan</text>
  <rect x="40" y="70" width="180" height="90" rx="10" fill="#dbeafe" stroke="#2563eb"/>
  <text x="130" y="105" text-anchor="middle" font-size="14" font-weight="600" fill="#1e3a8a">Trình duyệt</text>
  <text x="130" y="128" text-anchor="middle" font-size="12" fill="#334155">Vue 3 + Inertia</text>
  <text x="130" y="146" text-anchor="middle" font-size="11" fill="#64748b">Độc giả / Admin SPA</text>
  <rect x="370" y="55" width="200" height="120" rx="10" fill="#fef3c7" stroke="#d97706"/>
  <text x="470" y="95" text-anchor="middle" font-size="14" font-weight="600" fill="#92400e">Laravel 12</text>
  <text x="470" y="118" text-anchor="middle" font-size="12" fill="#334155">API /api/v1</text>
  <text x="470" y="136" text-anchor="middle" font-size="12" fill="#334155">Web + Sanctum</text>
  <text x="470" y="154" text-anchor="middle" font-size="11" fill="#64748b">Services + Queue</text>
  <rect x="700" y="70" width="180" height="90" rx="10" fill="#dcfce7" stroke="#16a34a"/>
  <text x="790" y="105" text-anchor="middle" font-size="14" font-weight="600" fill="#14532d">MySQL</text>
  <text x="790" y="128" text-anchor="middle" font-size="12" fill="#334155">Dữ liệu nghiệp vụ</text>
  <rect x="700" y="190" width="180" height="70" rx="10" fill="#fce7f3" stroke="#db2777"/>
  <text x="790" y="222" text-anchor="middle" font-size="14" font-weight="600" fill="#9d174d">Redis</text>
  <text x="790" y="244" text-anchor="middle" font-size="12" fill="#334155">Cache / Queue</text>
  <rect x="370" y="220" width="200" height="70" rx="10" fill="#e0e7ff" stroke="#4f46e5"/>
  <text x="470" y="252" text-anchor="middle" font-size="14" font-weight="600" fill="#312e81">Storage</text>
  <text x="470" y="274" text-anchor="middle" font-size="12" fill="#334155">PDF private / media</text>
  <rect x="40" y="220" width="180" height="70" rx="10" fill="#f1f5f9" stroke="#64748b"/>
  <text x="130" y="252" text-anchor="middle" font-size="14" font-weight="600" fill="#0f172a">Postman / App</text>
  <text x="130" y="274" text-anchor="middle" font-size="12" fill="#334155">JWT API client</text>
  <path d="M220 115 H370" stroke="#64748b" stroke-width="2" marker-end="url(#arrow)"/>
  <path d="M570 115 H700" stroke="#64748b" stroke-width="2" marker-end="url(#arrow)"/>
  <path d="M470 175 V220" stroke="#64748b" stroke-width="2" marker-end="url(#arrow)"/>
  <path d="M570 255 H700" stroke="#64748b" stroke-width="2" marker-end="url(#arrow)"/>
  <path d="M220 255 H370" stroke="#64748b" stroke-width="2" marker-end="url(#arrow)"/>
  <text x="460" y="360" text-anchor="middle" font-size="12" fill="#475569">Admin: cookie session · Mobile/API: Bearer JWT · Header domain bắt buộc</text>
</svg>`,
  'roles.svg': `<?xml version="1.0" encoding="UTF-8"?>
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 800 300" font-family="Segoe UI, system-ui, sans-serif">
  <rect width="800" height="300" fill="#f8fafc" rx="12"/>
  <text x="400" y="32" text-anchor="middle" font-size="18" font-weight="700" fill="#0f172a">Vai trò hệ thống</text>
  <rect x="40" y="60" width="160" height="200" rx="10" fill="#dbeafe" stroke="#2563eb"/>
  <text x="120" y="90" text-anchor="middle" font-weight="600" fill="#1e40af">Độc giả</text>
  <text x="120" y="115" text-anchor="middle" font-size="11" fill="#334155">Tra cứu sách</text>
  <text x="120" y="135" text-anchor="middle" font-size="11" fill="#334155">Mượn / trả</text>
  <text x="120" y="155" text-anchor="middle" font-size="11" fill="#334155">Thẻ thư viện</text>
  <text x="120" y="175" text-anchor="middle" font-size="11" fill="#334155">Tài liệu số</text>
  <text x="120" y="195" text-anchor="middle" font-size="11" fill="#334155">Nộp đồ án</text>
  <rect x="220" y="60" width="160" height="200" rx="10" fill="#fef3c7" stroke="#d97706"/>
  <text x="300" y="90" text-anchor="middle" font-weight="600" fill="#92400e">Thủ thư</text>
  <text x="300" y="120" text-anchor="middle" font-size="11" fill="#334155">Lập phiếu mượn/trả</text>
  <text x="300" y="140" text-anchor="middle" font-size="11" fill="#334155">Duyệt thẻ / mượn</text>
  <text x="300" y="160" text-anchor="middle" font-size="11" fill="#334155">Quản lý kho</text>
  <rect x="400" y="60" width="160" height="200" rx="10" fill="#dcfce7" stroke="#16a34a"/>
  <text x="480" y="90" text-anchor="middle" font-weight="600" fill="#166534">Admin</text>
  <text x="480" y="120" text-anchor="middle" font-size="11" fill="#334155">User / RBAC</text>
  <text x="480" y="140" text-anchor="middle" font-size="11" fill="#334155">Sách &amp; tài liệu số</text>
  <text x="480" y="160" text-anchor="middle" font-size="11" fill="#334155">Cấu hình thư viện</text>
  <rect x="580" y="60" width="180" height="200" rx="10" fill="#ede9fe" stroke="#7c3aed"/>
  <text x="670" y="90" text-anchor="middle" font-weight="600" fill="#5b21b6">Super Admin</text>
  <text x="670" y="120" text-anchor="middle" font-size="11" fill="#334155">Toàn quyền</text>
  <text x="670" y="140" text-anchor="middle" font-size="11" fill="#334155">Roles / Permissions</text>
  <text x="670" y="160" text-anchor="middle" font-size="11" fill="#334155">Deploy / hệ thống</text>
</svg>`,
  'loan-flow.svg': `<?xml version="1.0" encoding="UTF-8"?>
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 900 200" font-family="Segoe UI, system-ui, sans-serif">
  <rect width="900" height="200" fill="#ffffff" rx="12" stroke="#e2e8f0"/>
  <text x="450" y="28" text-anchor="middle" font-size="16" font-weight="700" fill="#0f172a">Luồng mượn sách in (tóm tắt)</text>
  <rect x="30" y="60" width="120" height="50" rx="8" fill="#dbeafe"/>
  <text x="90" y="90" text-anchor="middle" font-size="11" fill="#1e3a8a">Yêu cầu mượn</text>
  <text x="165" y="90" font-size="18" fill="#64748b">&#8594;</text>
  <rect x="190" y="60" width="120" height="50" rx="8" fill="#fef3c7"/>
  <text x="250" y="90" text-anchor="middle" font-size="11" fill="#92400e">Duyệt thủ thư</text>
  <text x="325" y="90" font-size="18" fill="#64748b">&#8594;</text>
  <rect x="350" y="60" width="120" height="50" rx="8" fill="#dcfce7"/>
  <text x="410" y="90" text-anchor="middle" font-size="11" fill="#166534">Phiếu mượn</text>
  <text x="485" y="90" font-size="18" fill="#64748b">&#8594;</text>
  <rect x="510" y="60" width="120" height="50" rx="8" fill="#e0e7ff"/>
  <text x="570" y="90" text-anchor="middle" font-size="11" fill="#3730a3">Đang mượn</text>
  <text x="645" y="90" font-size="18" fill="#64748b">&#8594;</text>
  <rect x="670" y="60" width="120" height="50" rx="8" fill="#fce7f3"/>
  <text x="730" y="90" text-anchor="middle" font-size="11" fill="#9d174d">Trả sách</text>
  <text x="450" y="160" text-anchor="middle" font-size="11" fill="#64748b">Kiểm tra: thẻ hợp lệ · policy · quá hạn · phạt · bản sao khả dụng</text>
</svg>`,
};

for (const [name, content] of Object.entries(files)) {
  const file = path.join(assets, name);
  fs.writeFileSync(file, content, 'utf8');
  const ok = fs.readFileSync(file, 'utf8').includes('Kiến trúc') || fs.readFileSync(file, 'utf8').includes('Vai trò') || fs.readFileSync(file, 'utf8').includes('Luồng');
  console.log(ok ? '✓' : '✗', name);
}
