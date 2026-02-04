<?php

namespace App\Enums;

enum BookType: string
{
  case BOOK = 'book';
  case THESIS = 'thesis'; // Khóa luận/Đồ án
  case DISSERTATION = 'dissertation'; // Luận văn/Luận án
  case RESEARCH = 'research'; // Nghiên cứu khoa học
  case MAGAZINE = 'magazine';
  case OTHER = 'other';

  public function label(): string
  {
    return match ($this) {
      self::BOOK => 'Sách',
      self::THESIS => 'Khóa luận/Đồ án',
      self::DISSERTATION => 'Luận văn/Luận án',
      self::RESEARCH => 'Nghiên cứu khoa học',
      self::MAGAZINE => 'Tạp chí',
      self::OTHER => 'Tài liệu khác',
    };
  }
}
