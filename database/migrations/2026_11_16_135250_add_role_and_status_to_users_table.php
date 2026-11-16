<?php

declare(strict_types=1);

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
            $table->string('role', 20)->default('student')->after('password')->index();
            $table->string('status', 20)->default('pending')->after('role')->index();
        });
    }

    /**
     * التراجع: حذف العمودين
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['role', 'status']);
        });
    }
};
