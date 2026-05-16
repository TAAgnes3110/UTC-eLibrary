-- Bổ sung sau: php artisan migrate --force
-- Tài liệu số mẫu + cấu hình giá tải PDF (xem trước cố định 5 trang trong code).

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS=0;

INSERT INTO `library_settings` (`key`, `type`, `value`, `json_value`, `created_at`, `updated_at`, `created_by`, `updated_by`, `deleted_by`) VALUES
('digital.default_pdf_download_price_vnd', 'int', '50000', NULL, NOW(), NOW(), 3, 3, NULL),
('loan.late_return_fine_mode', 'string', 'fixed_per_day', NULL, NOW(), NOW(), 3, 3, NULL),
('loan.late_return_fine_percent_of_book', 'int', '20', NULL, NOW(), NOW(), 3, 3, NULL),
('loan.external_borrow_fee_vnd', 'int', '0', NULL, NOW(), NOW(), 3, 3, NULL)
ON DUPLICATE KEY UPDATE `value` = VALUES(`value`), `updated_at` = VALUES(`updated_at`);

INSERT INTO `books` (`id`,`registration_number`,`book_code`,`title`,`sub_title`,`language`,`edition`,`published_year`,`pages`,`illustration_pages`,`book_size`,`price`,`quantity`,`view_count`,`summary`,`notes`,`publisher_place`,`cabinet`,`cover_image`,`classification_id`,`warehouse_id`,`resource_type`,`access_mode`,`params`,`created_by`,`updated_by`,`deleted_by`,`created_at`,`updated_at`,`deleted_at`) VALUES
(501,'REG-UTC-2026-0501','LV-UTC-2026-501','Đồ án: Hệ thống quản lý thư viện số UTC','','Tiếng Việt',NULL,2026,120,NULL,NULL,0,0,0,'Đồ án tốt nghiệp mẫu — tài liệu số có paywall, xem trước 5 trang, tải PDF sau thanh toán.','Mẫu demo tài liệu số.','Hà Nội',NULL,'utc-elibrary/book-covers/digital/submission-501.jpg',4,1,'digital','online_only','{"source":"sample-dump-v1"}',3,3,NULL,NOW(),NOW(),NULL),
(502,'REG-UTC-2026-0502','LV-UTC-2026-502','Luận văn: Tối ưu truy vấn CSDL cho thư viện','','Tiếng Việt',NULL,2025,95,NULL,NULL,0,0,0,'Luận văn thạc sĩ mẫu — miễn phí tải (paywall tắt).','Mẫu demo miễn phí.','Hà Nội',NULL,'utc-elibrary/book-covers/digital/submission-502.jpg',4,2,'digital','online_only','{"source":"sample-dump-v1"}',3,3,NULL,NOW(),NOW(),NULL)
ON DUPLICATE KEY UPDATE `title` = VALUES(`title`), `updated_at` = VALUES(`updated_at`);

INSERT INTO `book_authors` (`book_id`,`author_id`,`order`,`created_by`,`updated_by`,`deleted_by`,`created_at`,`updated_at`) VALUES
(501,1001,0,3,3,NULL,NOW(),NOW()),
(502,1002,0,3,3,NULL,NOW(),NOW())
ON DUPLICATE KEY UPDATE `updated_at` = VALUES(`updated_at`);

INSERT INTO `thesis_metadata` (`book_id`,`work_type`,`degree_program`,`supervisor_name`,`supervisor_user_id`,`defense_year`,`keywords`,`abstract_text`,`params`,`created_at`,`updated_at`,`created_by`,`updated_by`,`deleted_by`) VALUES
(501,'graduation_project','Công nghệ thông tin',NULL,NULL,2026,'thư viện số; Laravel; Vue','Tóm tắt đồ án mẫu về xây dựng hệ thống quản lý thư viện điện tử cho UTC.',NULL,NOW(),NOW(),3,3,NULL),
(502,'master_thesis','Công nghệ thông tin',NULL,NULL,2025,'CSDL; truy vấn; thư viện','Tóm tắt luận văn mẫu về tối ưu truy vấn cơ sở dữ liệu.',NULL,NOW(),NOW(),3,3,NULL)
ON DUPLICATE KEY UPDATE `abstract_text` = VALUES(`abstract_text`), `updated_at` = VALUES(`updated_at`);

INSERT INTO `digital_assets` (`id`,`book_id`,`version`,`is_primary`,`storage_disk`,`path`,`preview_path`,`preview_page_count`,`preview_generated_at`,`original_name`,`mime`,`byte_size`,`view_count`,`download_count`,`preview_display`,`checksum_sha256`,`visibility`,`embargo_until`,`params`,`created_at`,`updated_at`,`deleted_at`,`created_by`,`updated_by`,`deleted_by`) VALUES
(1,501,1,1,'local','utc-elibrary/books/digital-assets/501/sample-do-an.pdf',NULL,NULL,NULL,'do-an-quan-ly-thu-vien-so.pdf','application/pdf',245760,0,0,NULL,NULL,'internal',NULL,NULL,NOW(),NOW(),NULL,3,3,NULL),
(2,502,1,1,'local','utc-elibrary/books/digital-assets/502/sample-luan-van.pdf',NULL,NULL,NULL,'luan-van-csdl.pdf','application/pdf',189440,0,0,NULL,NULL,'internal',NULL,NULL,NOW(),NOW(),NULL,3,3,NULL)
ON DUPLICATE KEY UPDATE `path` = VALUES(`path`), `updated_at` = VALUES(`updated_at`);

INSERT INTO `digital_asset_paywall_settings` (`digital_asset_id`,`is_paywall_enabled`,`pdf_download_price_vnd`,`currency`,`internal_note`,`created_at`,`updated_at`,`created_by`,`updated_by`,`deleted_by`) VALUES
(1,1,50000,'VND','Mẫu có thu phí tải PDF',NOW(),NOW(),3,3,NULL),
(2,0,0,'VND','Mẫu miễn phí tải PDF',NOW(),NOW(),3,3,NULL)
ON DUPLICATE KEY UPDATE `pdf_download_price_vnd` = VALUES(`pdf_download_price_vnd`), `updated_at` = VALUES(`updated_at`);

SET FOREIGN_KEY_CHECKS=1;
