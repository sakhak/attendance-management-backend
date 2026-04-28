<?php

namespace Database\Seeders;

use App\Models\AttendanceRuleSetting;
use App\Models\SchoolSetting;
use Illuminate\Database\Seeder;

class SettingsSeeder extends Seeder
{
    public function run(): void
    {
        SchoolSetting::firstOrCreate(['id' => 1], [
            'school_name' => 'SETEC Institute',
            'address' => 'Phnom Penh',
            'city' => 'Phnom Penh',
            'state_province' => 'Phnom Penh',
            'postal_code' => '62704',
            'country' => 'Cambodia',
            'academic_year' => '2025-2026',
            'term_semester' => 'SU10',
        ]);

        AttendanceRuleSetting::firstOrCreate(['id' => 1], [
            'late_threshold_minutes' => 15,
            'absent_threshold_minutes' => 45,
            'exclude_weekends' => true,
            'auto_exclude_public_holidays' => true,
        ]);
    }
}
