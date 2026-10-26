<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     */
    public function test_the_application_returns_a_successful_response(): void
    {
        config([
            'paperclip.api_url' => 'https://example.invalid',
            'paperclip.api_key' => 'test-only',
            'paperclip.company_id' => 'test-only',
        ]);
        Http::preventStrayRequests();
        Http::fake(['https://example.invalid/*' => Http::response([], 200)]);

        $response = $this->get('/');

        $response->assertStatus(200);
        Http::assertSentCount(6);
    }
}
