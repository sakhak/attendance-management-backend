<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendance_records', function (Blueprint $table) {
            // Record attendance for an explicit calendar date (not just "created_at").
            $table->date('attendance_date')->nullable()->after('recorded_by')->index();
        });

        // Backfill existing rows so the new unique key can be applied safely.
        DB::table('attendance_records')
            ->whereNull('attendance_date')
            ->update(['attendance_date' => DB::raw('DATE(created_at)')]);

        Schema::table('attendance_records', function (Blueprint $table) {
            // Old design only allowed one record per student per session (no per-date history).
            $table->dropUnique('attendance_records_class_session_id_student_id_unique');
            $table->unique(['class_session_id', 'student_id', 'attendance_date']);
        });
    }

    public function down(): void
    {
        Schema::table('attendance_records', function (Blueprint $table) {
            $table->dropUnique(['class_session_id', 'student_id', 'attendance_date']);
            $table->unique(['class_session_id', 'student_id']);
            $table->dropIndex(['attendance_date']);
            $table->dropColumn('attendance_date');
        });
    }
};

