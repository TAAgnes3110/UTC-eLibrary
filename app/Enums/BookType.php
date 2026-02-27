<?php

namespace App\Enums;

enum BookType: string
{
  case BOOK = 'book';                    // Sách
  case TEXTBOOK = 'textbook';            // Giáo trình
  case THESIS = 'thesis';                // Bài luận / Khóa luận / Đồ án
  case DISSERTATION = 'dissertation';    // Luận văn / Luận án
  case RESEARCH = 'research';            // Báo cáo khoa học
  case MAGAZINE = 'magazine';            // Tạp chí
  case OTHER = 'other';                  // Tài liệu khác

  public function label(): string
  {
    return match ($this) {
      self::BOOK => 'Sách',
      self::TEXTBOOK => 'Giáo trình',
      self::THESIS => 'Bài luận / Khóa luận / Đồ án',
      self::DISSERTATION => 'Luận văn / Luận án',
      self::RESEARCH => 'Báo cáo khoa học',
      self::MAGAZINE => 'Tạp chí',
      self::OTHER => 'Tài liệu khác',
    };
  }

  /** Các loại mặc định NXB = Trường ĐH Giao thông vận tải khi không nhập từ bên ngoài */
  public function useDefaultPublisher(): bool
  {
    return in_array($this, [
      self::TEXTBOOK,
      self::RESEARCH,
      self::THESIS,
      self::DISSERTATION,
    ], true);
  }
}
