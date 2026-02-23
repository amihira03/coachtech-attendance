<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminLoginTest extends TestCase
{
    use RefreshDatabase;

    public function testAdminEmailRequired()
    {
        User::factory()->create([
            'email' => 'admin@example.com',
            'is_admin' => true,
        ]);

        $response = $this->from('/admin/login')->post('/login', [
            'email' => '',
            'password' => 'password',
        ]);

        $response->assertSessionHasErrors([
            'email' => 'メールアドレスを入力してください'
        ]);
    }

    public function testAdminPasswordRequired()
    {
        User::factory()->create([
            'email' => 'admin@example.com',
            'is_admin' => true,
        ]);

        $response = $this->from('/admin/login')->post('/login', [
            'email' => 'admin@example.com',
            'password' => '',
        ]);

        $response->assertSessionHasErrors([
            'password' => 'パスワードを入力してください'
        ]);
    }

    public function testAdminLoginFailsWithInvalidCredentials()
    {
        User::factory()->create([
            'email' => 'admin@example.com',
            'is_admin' => true,
        ]);

        $response = $this->from('/admin/login')->post('/login', [
            'email' => 'wrong@example.com',
            'password' => 'password',
        ]);

        $response->assertSessionHasErrors([
            'login' => 'ログイン情報が登録されていません'
        ]);
    }
}
