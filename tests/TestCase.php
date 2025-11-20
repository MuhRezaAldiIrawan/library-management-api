<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase;

    /**
     * Create a user and return auth token
     */
    protected function authenticateUser()
    {
        $user = \App\Models\User::factory()->create();
        $token = auth('api')->login($user);

        return [
            'user' => $user,
            'token' => $token
        ];
    }

    /**
     * Get authorization header with token
     */
    protected function getAuthHeaders($token)
    {
        return [
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json',
        ];
    }
}
