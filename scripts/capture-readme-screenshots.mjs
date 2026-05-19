/**
 * Chụp screenshot full-page cho README (demo EC2).
 * Chạy: node scripts/capture-readme-screenshots.mjs
 */
import { chromium } from 'playwright';
import { mkdir } from 'node:fs/promises';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const BASE = process.env.SCREENSHOT_BASE_URL || 'http://3.0.56.220';
const OUT = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '../readme/assets/screenshots');
const ADMIN_EMAIL = process.env.SCREENSHOT_ADMIN_EMAIL || 'admin@utc.edu.vn';
const ADMIN_PASSWORD = process.env.SCREENSHOT_ADMIN_PASSWORD || 'password';
const STUDENT_EMAIL = process.env.SCREENSHOT_STUDENT_EMAIL || 'student@st.utc.edu.vn';
const STUDENT_PASSWORD = process.env.SCREENSHOT_STUDENT_PASSWORD || 'password';

/** Màn hình máy tính Full HD (layout desktop, không mobile). */
const DESKTOP_VIEWPORT = { width: 1920, height: 1080 };
const DESKTOP_USER_AGENT =
  'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36';
const NAV_OPTS = { waitUntil: 'commit', timeout: 60000 };

function desktopContextOptions() {
  return {
    viewport: DESKTOP_VIEWPORT,
    screen: DESKTOP_VIEWPORT,
    deviceScaleFactor: 1,
    isMobile: false,
    hasTouch: false,
    userAgent: DESKTOP_USER_AGENT,
    locale: 'vi-VN',
    colorScheme: 'dark',
  };
}

async function waitReady(page, ms = 2500) {
  await page.waitForLoadState('domcontentloaded', { timeout: 30000 }).catch(() => {});
  await page.waitForLoadState('networkidle', { timeout: 25000 }).catch(() => {});
  await page.waitForTimeout(ms);
}

async function shot(page, name, opts = {}) {
  const file = path.join(OUT, name);
  await page.screenshot({
    path: file,
    fullPage: true,
    animations: 'disabled',
    ...opts,
  });
  console.log('  ✓', name);
}

async function gotoShot(page, url, name, waitMs = 3000, waitText = null) {
  await page.goto(url, NAV_OPTS);
  if (waitText) {
    await page.waitForSelector(`text=${waitText}`, { timeout: 20000 }).catch(() => {});
  }
  await waitReady(page, waitMs);
  await shot(page, name);
}

async function login(page, email, password, expectUrl = /\//) {
  await page.goto(`${BASE}/login`, NAV_OPTS);
  await waitReady(page, 1200);
  await page.locator('#login').fill(email);
  await page.locator('#password').fill(password);
  await page.locator('button[type="submit"]').first().click();
  await page.waitForURL(expectUrl, { timeout: 30000 });
  await waitReady(page, 1500);
}

async function loginAdmin(page) {
  await login(page, ADMIN_EMAIL, ADMIN_PASSWORD, /\/(admin|dashboard)/);
}

async function loginStudent(page) {
  await login(page, STUDENT_EMAIL, STUDENT_PASSWORD, /\/(admin|dich-vu|tai-khoan|\?|$)/);
}

async function main() {
  await mkdir(OUT, { recursive: true });

  const browser = await chromium.launch({ headless: true });
  const context = await browser.newContext(desktopContextOptions());
  const page = await context.newPage();

  console.log('Reader pages…');
  await page.goto(`${BASE}/`, NAV_OPTS);
  await waitReady(page, 3000);
  await shot(page, '01-home.png');

  await page.goto(`${BASE}/login`, NAV_OPTS);
  await waitReady(page, 1500);
  await shot(page, '02-login.png');

  await page.goto(`${BASE}/register`, NAV_OPTS);
  await waitReady(page, 1500);
  await shot(page, '03-register.png');

  await page.goto(`${BASE}/tra-cuu-sach`, NAV_OPTS);
  await waitReady(page, 3500);
  await shot(page, '04-catalog.png');

  const bookLink = page.locator('a[href*="/tra-cuu-sach/"]').first();
  if (await bookLink.count()) {
    await bookLink.click();
    await page.waitForURL(/\/tra-cuu-sach\/\d+/, { timeout: 15000 });
    await waitReady(page, 3500);
    await shot(page, '05-book-detail.png');
  } else {
    await page.goto(`${BASE}/tra-cuu-sach/1`, NAV_OPTS);
    await waitReady(page, 3500);
    await shot(page, '05-book-detail.png');
  }

  console.log('Admin pages…');
  await loginAdmin(page);

  await page.goto(`${BASE}/admin`, NAV_OPTS);
  await page.waitForSelector('text=Chào mừng', { timeout: 20000 }).catch(() => {});
  await waitReady(page, 4000);
  await shot(page, '06-admin-dashboard.png');

  await page.goto(`${BASE}/admin/books/digital`, NAV_OPTS);
  await page.waitForSelector('text=Đồ án', { timeout: 20000 }).catch(() => {});
  await waitReady(page, 3500);
  await shot(page, '07-admin-digital-books.png');

  await gotoShot(page, `${BASE}/admin/loans`, '08-admin-loans.png', 3500, 'phiếu mượn');

  console.log('Luồng trọng tâm — độc giả (sinh viên)…');
  const studentContext = await browser.newContext(desktopContextOptions());
  const studentPage = await studentContext.newPage();
  await loginStudent(studentPage);

  await gotoShot(studentPage, `${BASE}/dich-vu/cap-the-thu-vien`, '09-reader-library-card.png', 3000, 'thẻ');
  await gotoShot(studentPage, `${BASE}/dich-vu/phieu-muon`, '10-reader-loan-requests.png', 3500, 'mượn');
  await gotoShot(studentPage, `${BASE}/quy-dinh/muon-sach`, '11-reader-borrowing-rules.png', 2500);
  await gotoShot(studentPage, `${BASE}/dich-vu/gio-sach`, '12-reader-book-cart.png', 3500);
  await gotoShot(studentPage, `${BASE}/dich-vu/gio-sach?tab=purchase`, '13-reader-digital-cart.png', 3500);
  await gotoShot(studentPage, `${BASE}/dich-vu/thanh-toan`, '14-reader-payment.png', 3500);
  await gotoShot(studentPage, `${BASE}/dich-vu/don-hang-cua-toi`, '15-reader-orders.png', 3500, 'đơn');
  await gotoShot(studentPage, `${BASE}/dich-vu/tai-lieu-so`, '16-reader-digital-documents.png', 3000);

  const loanDetailLink = studentPage.locator('a[href*="/dich-vu/phieu-muon/"]').first();
  if (await loanDetailLink.count()) {
    await loanDetailLink.click();
    await studentPage.waitForURL(/\/phieu-muon\/\d+/, { timeout: 15000 });
    await waitReady(studentPage, 3000);
    await shot(studentPage, '17-reader-loan-detail.png');
  }

  await studentContext.close();

  console.log('Luồng trọng tâm — quản trị…');
  await gotoShot(page, `${BASE}/admin/loans/borrow-requests`, '18-admin-borrow-requests.png', 3500, 'mượn');
  await gotoShot(page, `${BASE}/admin/loans/create`, '19-admin-loan-create.png', 3000);
  await gotoShot(page, `${BASE}/admin/loans/renewal-requests`, '20-admin-renewal-requests.png', 3500, 'gia hạn');

  await page.goto(`${BASE}/admin/loans`, NAV_OPTS);
  await waitReady(page, 3000);
  const viewBtn = page.getByRole('button', { name: 'Xem' }).first();
  if (await viewBtn.count()) {
    await viewBtn.click();
    await page.waitForURL(/\/admin\/loans\/\d+/, { timeout: 15000 }).catch(() => {});
    await waitReady(page, 3500);
    if (/\/admin\/loans\/\d+/.test(page.url())) {
      await shot(page, '21-admin-loan-detail.png');
    }
  }

  await gotoShot(page, `${BASE}/admin/library-cards`, '22-admin-library-cards.png', 3500, 'thẻ');
  await gotoShot(page, `${BASE}/admin/library-cards/requests`, '23-admin-library-card-requests.png', 3500);
  await gotoShot(page, `${BASE}/admin/library-settings/pricing`, '24-admin-digital-pricing.png', 3500, 'giá');
  await gotoShot(page, `${BASE}/admin/books/printed`, '25-admin-printed-books.png', 3500, 'Sách in');
  await gotoShot(page, `${BASE}/admin/books/digital/submissions`, '26-admin-digital-submissions.png', 3500, 'nộp');

  await browser.close();
  console.log('\nDone →', OUT);
}

main().catch((e) => {
  console.error(e);
  process.exit(1);
});
