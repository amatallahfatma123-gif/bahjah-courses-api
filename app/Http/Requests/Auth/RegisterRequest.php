<?php

declare(strict_types=1);

namespace App\Http\Requests\Auth;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

final class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, list<ValidationRule|string>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],

            // منع أي محاولة لإرسال role أو status من الـ API
            // 'prohibited' = لو الحقل موجود في الطلب، يرجع 422
            'role' => ['prohibited'],
            'status' => ['prohibited'],
        ];
    }

    /**
     * رسائل التحقق بالعربية
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            // name
            'name.required' => 'الاسم مطلوب.',
            'name.string' => 'الاسم يجب أن يكون نصاً.',
            'name.max' => 'الاسم يجب ألا يتجاوز 255 حرفاً.',

            // email
            'email.required' => 'البريد الإلكتروني مطلوب.',
            'email.string' => 'البريد الإلكتروني يجب أن يكون نصاً.',
            'email.email' => 'البريد الإلكتروني غير صالح.',
            'email.max' => 'البريد الإلكتروني يجب ألا يتجاوز 255 حرفاً.',
            'email.unique' => 'البريد الإلكتروني مستخدم بالفعل.',

            // password
            'password.required' => 'كلمة المرور مطلوبة.',
            'password.string' => 'كلمة المرور يجب أن تكون نصاً.',
            'password.min' => 'كلمة المرور يجب أن تكون 8 أحرف على الأقل.',

            // role & status (prohibited)
            'role.prohibited' => 'لا يمكنك تحديد الدور (role) أثناء التسجيل.',
            'status.prohibited' => 'لا يمكنك تحديد الحالة (status) أثناء التسجيل.',
        ];
    }
}
