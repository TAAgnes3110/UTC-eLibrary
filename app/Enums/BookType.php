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
    case NEWSPAPER = 'newspaper';
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
            self::NEWSPAPER => 'Báo',
            self::OTHER => 'Tài liệu khác',
        };
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

    /**
     * Nhóm tài nguyên (Tài nguyên thông tin): printed | newspaper_magazine | thesis.
     * Tài liệu số lọc theo is_digital, không theo type.
     */
    public function resourceGroup(): string
    {
        return match ($this) {
            self::BOOK, self::TEXTBOOK, self::OTHER => 'printed',
            self::NEWSPAPER, self::MAGAZINE => 'newspaper_magazine',
            self::THESIS, self::DISSERTATION, self::RESEARCH => 'thesis',
        };
    }

    /** Trả về danh sách value (type) thuộc nhóm. */
    public static function getTypesByGroup(string $group): array
    {
        if ($group === 'digital') {
            return []; // Tài liệu số: lọc theo is_digital, không theo type
        }
        return array_values(array_map(
            fn (self $c) => $c->value,
            array_filter(self::cases(), fn (self $c) => $c->resourceGroup() === $group)
        ));
    }
}
