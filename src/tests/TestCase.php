<?php

namespace Tests;

use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    protected function createVerifiedUser(): User
    {
        /** @var \App\Models\User $user */
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        return $user;
    }

    protected function createVerifiedAdmin(): User
    {
        /** @var \App\Models\User $admin */
        $admin = User::factory()->create([
            'email_verified_at' => now(),
            'is_admin' => true,
        ]);

        return $admin;
    }
}
