<?php

namespace App\Enums;

enum NotificationType: string
{
    case ADMIN_PROFILE_REVIEW_NEEDED = 'admin.profile_review_needed';
    case ADMIN_CARD_REQUEST_SUBMITTED = 'admin.card_request_submitted';
    case ADMIN_LOAN_OVERDUE_DETECTED = 'admin.loan_overdue_detected';
    case ADMIN_LOAN_RENEWAL_PENDING = 'admin.loan_renewal_pending';

    case USER_PROFILE_UPDATE_APPROVED = 'user.profile_update_approved';
    case USER_PROFILE_UPDATE_REJECTED = 'user.profile_update_rejected';
    case USER_LOAN_OVERDUE_REMINDER = 'user.loan_overdue_reminder';
    case USER_LOAN_RENEWAL_APPROVED = 'user.loan_renewal_approved';
    case USER_LOAN_RENEWAL_REJECTED = 'user.loan_renewal_rejected';
    case USER_CARD_APPROVED = 'user.card_approved';
    case USER_CARD_REJECTED = 'user.card_rejected';
    case USER_CARD_EXPIRING_SOON = 'user.card_expiring_soon';
    case USER_CARD_EXPIRED = 'user.card_expired';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * @return list<string>
     */
    public static function adminTypes(): array
    {
        return [
            self::ADMIN_PROFILE_REVIEW_NEEDED->value,
            self::ADMIN_CARD_REQUEST_SUBMITTED->value,
            self::ADMIN_LOAN_OVERDUE_DETECTED->value,
            self::ADMIN_LOAN_RENEWAL_PENDING->value,
        ];
    }

    /**
     * @return list<string>
     */
    public static function userTypes(): array
    {
        return [
            self::USER_PROFILE_UPDATE_APPROVED->value,
            self::USER_PROFILE_UPDATE_REJECTED->value,
            self::USER_LOAN_OVERDUE_REMINDER->value,
            self::USER_LOAN_RENEWAL_APPROVED->value,
            self::USER_LOAN_RENEWAL_REJECTED->value,
            self::USER_CARD_APPROVED->value,
            self::USER_CARD_REJECTED->value,
            self::USER_CARD_EXPIRING_SOON->value,
            self::USER_CARD_EXPIRED->value,
        ];
    }

    public function isAdminType(): bool
    {
        return str_starts_with($this->value, 'admin.');
    }

    public function isUserType(): bool
    {
        return str_starts_with($this->value, 'user.');
    }
}

