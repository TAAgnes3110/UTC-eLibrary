/**
 * Icon @iconify/vue — bundle offline (không gọi api.iconify.design).
 * Tránh icon trống khi CSP chặn CDN hoặc EC2 không ra ngoài.
 */
import { addCollection, addIcon } from '@iconify/vue';
import lucideIcons from '@iconify-json/lucide/icons.json';
import mdiFacebook from './icons/mdi-facebook.json';

// Thêm icon mới phải đăng ký tại đây để tránh fallback fetch CDN bị CSP chặn.
addCollection(lucideIcons);
addIcon('mdi:facebook', mdiFacebook);
