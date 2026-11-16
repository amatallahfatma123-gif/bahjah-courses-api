<?php

namespace App\Enums;

/**
 * حالات موافقة المستخدم
 *
 * pending  : في انتظار موافقة الأدمن — لا يستطيع الوصول للكورسات
 * approved : تمت الموافقة — يستطيع الوصول للكورسات المنشورة
 */
enum UserStatus: string
{
    case PENDING  = 'pending';
    case APPROVED = 'approved';

    /**
     * إرجاع كل القيم كمصفوفة
     *
     * @return array<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * هل الحالة pending؟
     */
    public function isPending(): bool
    {
        return $this === self::PENDING;
    }

    /**
     * هل الحالة approved؟
     */
    public function isApproved(): bool
    {
        return $this === self::APPROVED;
    }
}
