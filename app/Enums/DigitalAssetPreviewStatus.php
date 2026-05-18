<?php

namespace App\Enums;

/** Trạng thái tạo bản xem trước tài liệu số (PDF + PNG). */
enum DigitalAssetPreviewStatus: string
{
    case Pending = 'pending';
    case Processing = 'processing';
    case Ready = 'ready';
    case Failed = 'failed';
    case Disabled = 'disabled';
}
