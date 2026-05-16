<?php

use App\Enums\DeployProfile;

$profile = DeployProfile::tryFromEnv(env('DEPLOY_PROFILE'));
$readerDefaults = $profile->readerDefaults();
$maxPdfBytes = (int) ($readerDefaults['max_pdf_bytes'] ?? 0);

return [

    /*
    |--------------------------------------------------------------------------
    | Deploy profile (local | infinityfree | vps)
    |--------------------------------------------------------------------------
    |
    | infinityfree: shared hosting — không Redis, thường không exec/shell.
    | vps/local: đủ tính năng (Redis, qpdf, queue worker).
    |
    */
    'profile' => $profile->value,

    'is_infinityfree' => $profile === DeployProfile::Infinityfree,

    /** Giới hạn upload PDF (byte). 0 = không ép theo profile (dùng rule FormRequest riêng). */
    'max_digital_pdf_bytes' => $maxPdfBytes,

    /** Rule Laravel `max:` cho file PDF (kilobyte). */
    'max_digital_pdf_kilobytes' => $maxPdfBytes > 0
        ? (int) floor($maxPdfBytes / 1024)
        : (int) env('DIGITAL_PDF_MAX_KB', 51200),

    /** qpdf / Ghostscript — InfinityFree thường chặn proc_open. */
    'allow_shell_pdf_tools' => (bool) env(
        'DEPLOY_ALLOW_SHELL_PDF_TOOLS',
        $profile->allowsShellPdfTools()
    ),

    /** Imagick render PDF — hay bị tắt policy trên shared host. */
    'allow_imagick_pdf' => (bool) env(
        'DEPLOY_ALLOW_IMAGICK_PDF',
        $profile->allowsImagickPdf()
    ),

    /**
     * Tạo preview lúc request nếu chưa có file — tắt trên InfinityFree
     * (preview tạo sẵn trên máy dev: digital-assets:regenerate-previews).
     */
    'allow_runtime_preview_generation' => (bool) env(
        'DEPLOY_ALLOW_RUNTIME_PREVIEW_GENERATION',
        $profile->allowsRuntimePreviewGeneration()
    ),

    /** Ảnh bìa + preview sau upload (afterResponse) — tắt trên InfinityFree. */
    'run_post_upload_processing_on_host' => (bool) env(
        'DEPLOY_RUN_POST_UPLOAD_ON_HOST',
        $profile->runsPostUploadProcessingOnHost()
    ),

    /** Queue chạy nền — InfinityFree không có worker lâu. */
    'prefer_sync_queue' => (bool) env(
        'DEPLOY_PREFER_SYNC_QUEUE',
        $profile->prefersSyncQueue()
    ),

];
