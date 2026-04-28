<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

class CleanUserSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Clear existing data related to users
        Schema::disableForeignKeyConstraints();
        
        // Truncate all related tables
        DB::table('attendance_records')->truncate();
        DB::table('enrollments')->truncate();
        DB::table('class_teacher')->truncate();
        DB::table('teachers')->truncate();
        DB::table('students')->truncate();
        DB::table('user_role')->truncate();
        DB::table('user_profiles')->truncate();
        DB::table('personal_access_tokens')->truncate();
        User::truncate();

        Schema::enableForeignKeyConstraints();

        // 2. Ensure Roles exist (run RoleSeeder)
        $this->call(RoleSeeder::class);

        $superAdminRole = Role::where('key', 'super_admin')->first();
        $adminRole = Role::where('key', 'admin')->first();
        $teacherRole = Role::where('key', 'teacher')->first();
        $studentRole = Role::where('key', 'student')->first();

        // 3. Create Users and assign Roles

        // SUPER ADMIN
        $superAdmin = User::create([
            'name' => 'Super Admin',
            'email' => 'superadmin@example.com',
            'password' => Hash::make('password'),
            'status' => 'active',
        ]);
        $superAdmin->roles()->attach($superAdminRole->id);

        // ADMIN
        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
            'status' => 'active',
        ]);
        $admin->roles()->attach($adminRole->id);

        // TEACHER
        $teacherUser = User::create([
            'name' => 'John Teacher',
            'email' => 'teacher@example.com',
            'password' => Hash::make('password'),
            'status' => 'active',
        ]);
        $teacherUser->roles()->attach($teacherRole->id);
        
        // Create Teacher Profile
        Teacher::create([
            'user_id' => $teacherUser->id,
            'teacher_code' => 'TCH-001',
            'status' => 'active',
        ]);

        // STUDENT
        $studentUser = User::create([
            'name' => 'Alice Student',
            'email' => 'student@example.com',
            'password' => Hash::make('password'),
            'status' => 'active',
        ]);
        $studentUser->roles()->attach($studentRole->id);

        // Create Student Profile
        Student::create([
            'user_id' => $studentUser->id,
            'student_code' => 'STU-001',
            'status' => 'active',
        ]);

        $this->command->info('Database users cleared and recreated successfully!');
    }
}
