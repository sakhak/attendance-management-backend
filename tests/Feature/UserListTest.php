<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class UserListTest extends TestCase
{
    use RefreshDatabase;

    public function test_users_can_be_filtered_to_student_role_only(): void
    {
        $adminRole = Role::create([
            'name' => 'Admin',
            'key' => 'admin',
            'status' => 'active',
        ]);

        $teacherRole = Role::create([
            'name' => 'Teacher',
            'key' => 'teacher',
            'status' => 'active',
        ]);

        $studentRole = Role::create([
            'name' => 'Student',
            'key' => 'student',
            'status' => 'active',
        ]);

        $adminUser = User::factory()->create(['name' => 'Admin User']);
        $adminUser->roles()->attach($adminRole->id);

        $teacherUser = User::factory()->create(['name' => 'Teacher User']);
        $teacherUser->roles()->attach($teacherRole->id);

        $studentUser = User::factory()->create(['name' => 'Student User']);
        $studentUser->roles()->attach($studentRole->id);

        Sanctum::actingAs($adminUser);

        $res = $this->getJson('/api/users?role=student');

        $res->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $studentUser->id)
            ->assertJsonPath('data.0.name', 'Student User')
            ->assertJsonPath('data.0.roles.0.key', 'student');

        $ids = collect($res->json('data'))->pluck('id');

        $this->assertFalse($ids->contains($adminUser->id));
        $this->assertFalse($ids->contains($teacherUser->id));
    }

    public function test_users_without_role_filter_keep_existing_list_behavior(): void
    {
        $adminRole = Role::create([
            'name' => 'Admin',
            'key' => 'admin',
            'status' => 'active',
        ]);

        $studentRole = Role::create([
            'name' => 'Student',
            'key' => 'student',
            'status' => 'active',
        ]);

        $adminUser = User::factory()->create(['name' => 'Admin User']);
        $adminUser->roles()->attach($adminRole->id);

        $studentUser = User::factory()->create(['name' => 'Student User']);
        $studentUser->roles()->attach($studentRole->id);

        Sanctum::actingAs($adminUser);

        $res = $this->getJson('/api/users');

        $res->assertOk()
            ->assertJsonPath('success', true);

        $ids = collect($res->json('data'))->pluck('id');

        $this->assertTrue($ids->contains($adminUser->id));
        $this->assertTrue($ids->contains($studentUser->id));
        $this->assertIsArray($res->json('data.0.roles'));
    }
}
