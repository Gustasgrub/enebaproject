<?php

namespace Tests\Feature;

use Tests\TestCase;

class HealthCheckTest extends TestCase
{
    public function test_health_endpoint_reports_ok_with_database_version(): void
    {
        $response = $this->getJson('/api/health');

        $response->assertOk()
            ->assertJsonPath('status', 'ok')
            ->assertJsonStructure(['status', 'database', 'checked_at']);

        $this->assertStringStartsWith('PostgreSQL ', $response->json('database'));
    }

    public function test_health_endpoint_allows_the_frontend_origin(): void
    {
        $response = $this->getJson('/api/health', ['Origin' => config('cors.allowed_origins')[0]]);

        $response->assertOk()
            ->assertHeader('Access-Control-Allow-Origin', config('cors.allowed_origins')[0]);
    }

    public function test_health_endpoint_does_not_allow_other_origins(): void
    {
        $response = $this->getJson('/api/health', ['Origin' => 'https://evil.example']);

        // With a single allowed origin the header always names that origin; the browser then
        // blocks any page whose origin differs. So the check is that it never echoes the caller.
        $this->assertNotSame('https://evil.example', $response->headers->get('Access-Control-Allow-Origin'));
    }

    public function test_preflight_answers_can_be_cached_by_the_browser(): void
    {
        $response = $this->call('OPTIONS', '/api/health', server: [
            'HTTP_ORIGIN' => config('cors.allowed_origins')[0],
            'HTTP_ACCESS_CONTROL_REQUEST_METHOD' => 'GET',
        ]);

        $response->assertNoContent()
            ->assertHeader('Access-Control-Max-Age', '7200');
    }
}
