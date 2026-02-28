<?php

namespace App\Enums;

/**
 * Loại tài liệu / sách (cột books.type).
 * Khớp với enum trong migration create_books_table.
 */
enum BookType: string
{
    case BOOK = 'book';
    case TEXTBOOK = 'textbook';
    case THESIS = 'thesis';
    case DISSERTATION = 'dissertation';
    case RESEARCH = 'research';
    case MAGAZINE = 'magazine';
    case OTHER = 'other';

    /** Nhãn hiển thị (đa ngôn ngữ, fallback tiếng Việt). */
    public function label(): string
    {
        return __('enums.BookType.' . $this->name) !== 'enums.BookType.' . $this->name
            ? __('enums.BookType.' . $this->name)
            : $this->defaultLabel();
    }

    /** Nhãn mặc định tiếng Việt (khi chưa có bản dịch). */
    private function defaultLabel(): string
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

    /** Các loại mặc định NXB = Trường ĐH Giao thông vận tải khi không nhập từ bên ngoài. */
    public function useDefaultPublisher(): bool
    {
        return in_array($this, [
            self::TEXTBOOK,
            self::RESEARCH,
            self::THESIS,
            self::DISSERTATION,
        ], true);
    }

    /** Mảng giá trị value (cho validation Rule::in). */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /** [ [ id => value, text => label ], ... ] cho dropdown. */
    public static function getOptions(): array
    {
        return array_map(fn (self $c) => [
            'id' => $c->value,
            'text' => $c->label(),
        ], self::cases());
    }
}
