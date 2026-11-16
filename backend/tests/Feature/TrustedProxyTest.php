<?php

namespace Tests\Feature;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class TrustedProxyTest extends TestCase
{
    public function test_a_request_forwarded_as_https_is_treated_as_secure(): void
    {
        // A route that only exists in this test, to look at the request as the app sees it.
        Route::get('/api/test/request-scheme', fn (Request $request) => ['secure' => $request->secure()]);

        // Render's proxy talks plain HTTP to the container and reports the original scheme here.
        $this->getJson('/api/test/request-scheme', ['X-Forwarded-Proto' => 'https'])
            ->assertJsonPath('secure', true);
    }
}
