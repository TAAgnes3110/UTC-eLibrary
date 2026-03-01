<?php

namespace App\Models;

class LibrarySetting extends BaseModel
{
    protected $table = 'library_settings';

    protected $fillable = [
        'key',
        'value',
        'type',
        'group',
        'description',
        'params',
    ];

    protected $casts = [
        'params' => 'array',
    ];

  /**
   * Get a setting value by key.
   */
  public static function get(string $key, mixed $default = null): mixed
  {
    $setting = self::where('key', $key)->first();

    if (!$setting) {
      return $default;
    }

    return self::castValue($setting->value, $setting->type);
  }

  /**
   * Set a setting value.
   */
  public static function set(string $key, mixed $value, string $type = 'string', string $group = 'general'): self
  {
    $stringValue = is_array($value) || is_object($value)
      ? json_encode($value)
      : (string) $value;

    return self::updateOrCreate(
      ['key' => $key],
      [
        'value' => $stringValue,
        'type' => $type,
        'group' => $group,
      ]
    );
  }

  /**
   * Get all settings in a group.
   */
  public static function getGroup(string $group): array
  {
    $settings = self::where('group', $group)->get();

    $result = [];
    foreach ($settings as $setting) {
      $result[$setting->key] = self::castValue($setting->value, $setting->type);
    }

    return $result;
  }

  /**
   * Cast value to appropriate type.
   */
  protected static function castValue(mixed $value, string $type): mixed
  {
    return match ($type) {
      'integer' => (int) $value,
      'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
      'json' => json_decode($value, true),
      'float', 'decimal' => (float) $value,
      default => $value,
    };
  }

  /**
   * Seed default settings.
   */
  public static function seedDefaults(): void
  {
    $defaults = [
      // Loan settings (mô hình: 3–5 cuốn, 7–14 ngày, có gia hạn)
      ['key' => 'loan_duration_days', 'value' => '14', 'type' => 'integer', 'group' => 'loan', 'description' => 'Số ngày mượn mặc định (7–14 ngày)'],
      ['key' => 'max_renewals', 'value' => '2', 'type' => 'integer', 'group' => 'loan', 'description' => 'Số lần gia hạn tối đa'],
      ['key' => 'max_books_per_reader', 'value' => '5', 'type' => 'integer', 'group' => 'loan', 'description' => 'Số sách tối đa mỗi độc giả (3–5 cuốn)'],

      // Fine settings
      ['key' => 'overdue_fine_per_day', 'value' => '5000', 'type' => 'integer', 'group' => 'fine', 'description' => 'Tiền phạt mỗi ngày quá hạn (VNĐ)'],
      ['key' => 'lost_book_fine_multiplier', 'value' => '2', 'type' => 'integer', 'group' => 'fine', 'description' => 'Hệ số phạt sách mất (x giá sách)'],
      ['key' => 'damaged_book_fine_percentage', 'value' => '50', 'type' => 'integer', 'group' => 'fine', 'description' => 'Phần trăm phạt sách hỏng'],

      // Reservation settings
      ['key' => 'reservation_expiry_hours', 'value' => '48', 'type' => 'integer', 'group' => 'reservation', 'description' => 'Số giờ hết hạn đặt sách'],

      // Library hours (T2–T6 hành chính; thi/đồ án có thể mở thêm ca)
      ['key' => 'library_opening_time', 'value' => '08:00', 'type' => 'string', 'group' => 'general', 'description' => 'Giờ mở cửa'],
      ['key' => 'library_closing_time', 'value' => '17:00', 'type' => 'string', 'group' => 'general', 'description' => 'Giờ đóng cửa'],
      ['key' => 'library_hours_notes', 'value' => 'Thứ 2 – Thứ 6: giờ hành chính. Giai đoạn thi, làm đồ án có thể mở thêm ca.', 'type' => 'string', 'group' => 'general', 'description' => 'Ghi chú giờ hoạt động'],

      // Notification settings
      ['key' => 'notify_due_soon_days', 'value' => '3', 'type' => 'integer', 'group' => 'notification', 'description' => 'Thông báo trước hạn trả (ngày)'],
      ['key' => 'notify_card_expiry_days', 'value' => '30', 'type' => 'integer', 'group' => 'notification', 'description' => 'Thông báo trước hạn thẻ (ngày)'],

      // Cohorts (khóa): tối đa 7 khóa; K66 là khóa mới nhất (tuyển sinh 2025–2026)
      ['key' => 'cohorts_max_count', 'value' => '7', 'type' => 'integer', 'group' => 'cohorts', 'description' => 'Số khóa tối đa lưu (mặc định 7)'],
      ['key' => 'cohorts_list', 'value' => '["K60","K61","K62","K63","K64","K65","K66"]', 'type' => 'json', 'group' => 'cohorts', 'description' => 'Danh sách mã khóa (K66 khóa mới nhất 2025–2026)'],
    ];

    foreach ($defaults as $setting) {
      self::firstOrCreate(
        ['key' => $setting['key']],
        $setting
      );
    }
  }
}
