<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * تشغيل الترحيل: إضافة عمودي role و status لجدول users
     *
     * role   : admin أو student (القيمة الافتراضية student)
     * status : pending أو approved (القيمة الافتراضية pending)
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // role: نص صغير (20 حرف) — القيمة الافتراضية 'student'
            // نضع index لتسريع الفلترة بـ role (مثلاً جلب كل الطلاب)
            $table->string('role', 20)->default('student')->after('password')->index();

            // status: نص صغير (20 حرف) — القيمة الافتراضية 'pending'
            // نضع index لتسريع الفلترة بـ status (مثلاً جلب كل الـ pending)
            $table->string('status', 20)->default('pending')->after('role')->index();
        });
    }

    /**
     * التراجع: حذف العمودين
     *
     * نحذف الـ index أولاً (Laravel يتعامل معه تلقائياً عند dropColumn في معظم الحالات،
     * لكن نكون صريحين لضمان التوافق)
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['role', 'status']);
        });
    }
};
