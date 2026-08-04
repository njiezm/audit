<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default('auditor')->after('password');
            $table->string('job_title')->nullable()->after('role');
            $table->string('signature_path')->nullable()->after('job_title');
            $table->boolean('is_active')->default(true)->after('signature_path');
            $table->timestamp('last_login_at')->nullable()->after('is_active');
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropSoftDeletes();
            $table->dropColumn(['role', 'job_title', 'signature_path', 'is_active', 'last_login_at']);
        });
    }
};
