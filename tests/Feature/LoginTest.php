<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_response_includes_user_roles(): void
    {
        $adminRole = Role::create([
            'name' => 'Admin',
            'key' => 'admin',
            'status' => 'active',
        ]);

        $user = User::factory()->create([
            'name' => 'Super Admin',
            'email' => 'superadmin@example.com',
            'password' => bcrypt('password'),
            'status' => 'active',
        ]);

        $user->roles()->attach($adminRole->id);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'superadmin@example.com',
            'password' => 'password',
        ]);

        $response->assertOk()
            ->assertJsonPath('message', 'Login successful')
            ->assertJsonStructure([
                'message',
                'token',
                'user' => [
                    'id',
                    'name',
                    'email',
                    'status',
                    'roles' => [
                        ['id', 'name', 'key'],
                    ],
                ],
            ])
            ->assertJsonPath('user.id', $user->id)
            ->assertJsonPath('user.roles.0.key', 'admin')
            ->assertJsonPath('user.roles.0.name', 'Admin');
    }
}
