<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LibrarySetting extends BaseModel
{


  protected $fillable = [
    'key',
    'value',
    'type',
    'group',
    'description',
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
      // Loan settings
      ['key' => 'loan_duration_days', 'value' => '30', 'type' => 'integer', 'group' => 'loan', 'description' => 'Số ngày mượn mặc định'],
      ['key' => 'max_renewals', 'value' => '2', 'type' => 'integer', 'group' => 'loan', 'description' => 'Số lần gia hạn tối đa'],
      ['key' => 'max_books_per_reader', 'value' => '5', 'type' => 'integer', 'group' => 'loan', 'description' => 'Số sách tối đa mỗi độc giả'],

      // Fine settings
      ['key' => 'overdue_fine_per_day', 'value' => '5000', 'type' => 'integer', 'group' => 'fine', 'description' => 'Tiền phạt mỗi ngày quá hạn (VNĐ)'],
      ['key' => 'lost_book_fine_multiplier', 'value' => '2', 'type' => 'integer', 'group' => 'fine', 'description' => 'Hệ số phạt sách mất (x giá sách)'],
      ['key' => 'damaged_book_fine_percentage', 'value' => '50', 'type' => 'integer', 'group' => 'fine', 'description' => 'Phần trăm phạt sách hỏng'],

      // Reservation settings
      ['key' => 'reservation_expiry_hours', 'value' => '48', 'type' => 'integer', 'group' => 'reservation', 'description' => 'Số giờ hết hạn đặt sách'],

      // Library hours
      ['key' => 'library_opening_time', 'value' => '08:00', 'type' => 'string', 'group' => 'general', 'description' => 'Giờ mở cửa'],
      ['key' => 'library_closing_time', 'value' => '17:00', 'type' => 'string', 'group' => 'general', 'description' => 'Giờ đóng cửa'],

      // Notification settings
      ['key' => 'notify_due_soon_days', 'value' => '3', 'type' => 'integer', 'group' => 'notification', 'description' => 'Thông báo trước hạn trả (ngày)'],
      ['key' => 'notify_card_expiry_days', 'value' => '30', 'type' => 'integer', 'group' => 'notification', 'description' => 'Thông báo trước hạn thẻ (ngày)'],
    ];

    foreach ($defaults as $setting) {
      self::firstOrCreate(
        ['key' => $setting['key']],
        $setting
      );
    }
  }
}
