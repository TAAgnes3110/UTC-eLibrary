<?php

use App\Enums\DeployProfile;

$profile = DeployProfile::tryFromEnv(env('DEPLOY_PROFILE'));
$readerDefaults = $profile->readerDefaults();
$maxPdfBytes = (int) ($readerDefaults['max_pdf_bytes'] ?? 0);

return [

    /*
    |--------------------------------------------------------------------------
    | Deploy profile (local | vps)
    |--------------------------------------------------------------------------
    |
    | local: máy dev — preview sync mặc định, đủ công cụ PDF.
    | vps: production Docker/VPS — Redis, qpdf, queue worker.
    |
    */
    'profile' => $profile->value,

    /** Giới hạn upload PDF (byte). 0 = không ép theo profile (dùng rule FormRequest riêng). */
    'max_digital_pdf_bytes' => $maxPdfBytes,

    /** Rule Laravel `max:` cho file PDF (kilobyte). */
    'max_digital_pdf_kilobytes' => $maxPdfBytes > 0
        ? (int) floor($maxPdfBytes / 1024)
        : (int) env('DIGITAL_PDF_MAX_KB', 51200),

    /** qpdf / Ghostscript */
    'allow_shell_pdf_tools' => (bool) env(
        'DEPLOY_ALLOW_SHELL_PDF_TOOLS',
        $profile->allowsShellPdfTools()
    ),

    /** Imagick render PDF */
    'allow_imagick_pdf' => (bool) env(
        'DEPLOY_ALLOW_IMAGICK_PDF',
        $profile->allowsImagickPdf()
    ),

    /**
     * Tạo preview lúc request nếu chưa có file.
     */
    'allow_runtime_preview_generation' => (bool) env(
        'DEPLOY_ALLOW_RUNTIME_PREVIEW_GENERATION',
        $profile->allowsRuntimePreviewGeneration()
    ),

    /** Ảnh bìa + preview sau upload (afterResponse). */
    'run_post_upload_processing_on_host' => (bool) env(
        'DEPLOY_RUN_POST_UPLOAD_ON_HOST',
        $profile->runsPostUploadProcessingOnHost()
    ),

    /** Queue chạy đồng bộ thay vì worker nền. */
    'prefer_sync_queue' => (bool) env('DEPLOY_PREFER_SYNC_QUEUE', false),

    /**
     * Tạo preview ngay trong request (sau HTTP) thay vì đẩy Redis/database queue.
     * Mặc định bật trên local — tránh job treo khi dev không chạy queue:work.
     */
    'preview_dispatch_sync' => (bool) env(
        'DIGITAL_PREVIEW_DISPATCH_SYNC',
        $profile === DeployProfile::Local
    ),

];
