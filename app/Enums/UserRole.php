<?php

namespace App\Enums;

/**
 * أدوار المستخدمين في النظام
 *
 * admin   : مدير النظام — يدير الطلاب والكورسات
 * student : طالب — يشاهد الكورسات المنشورة فقط بعد الموافقة
 */
enum UserRole: string
{
    case ADMIN   = 'admin';
    case STUDENT = 'student';

    /**
     * إرجاع كل القيم كمصفوفة (مفيد للتحقق في Validation)
     *
     * @return array<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * هل الدور admin؟
     */
    public function isAdmin(): bool
    {
        return $this === self::ADMIN;
    }

    /**
     * هل الدور student؟
     */
    public function isStudent(): bool
    {
        return $this === self::STUDENT;
    }
}
