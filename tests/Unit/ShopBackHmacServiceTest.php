<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\ShopBackHmacService;
use Carbon\Carbon;

class ShopBackHmacServiceTest extends TestCase
{
    private ShopBackHmacService $service;

    protected function setUp(): void
    {
        parent::setUp();
        
        config([
            'services.shopback.access_key' => 'test-access-key',
            'services.shopback.access_key_secret' => 'test-secret-key'
        ]);
        
        $this->service = new ShopBackHmacService();
    }

    public function test_generates_signature_with_empty_body()
    {
        $date = Carbon::parse('2022-08-15T14:59:47.585Z');
        
        $result = $this->service->generateSignature(
            'GET',
            '/api/test',
            [],
            'application/json',
            $date
        );
        
        $this->assertArrayHasKey('authorization', $result);
        $this->assertArrayHasKey('date', $result);
        $this->assertArrayHasKey('content_digest', $result);
        
        $this->assertStringStartsWith('SB1-HMAC-SHA256 test-access-key:', $result['authorization']);
        $this->assertEquals('2022-08-15T14:59:47.585Z', $result['date']);
        $this->assertEquals('', $result['content_digest']);
    }

    public function test_generates_signature_with_body()
    {
        $date = Carbon::parse('2022-08-15T14:59:47.585Z');
        $body = ['name' => 'John', 'age' => 30];
        
        $result = $this->service->generateSignature(
            'POST',
            '/api/users',
            $body,
            'application/json',
            $date
        );
        
        $this->assertArrayHasKey('authorization', $result);
        $this->assertArrayHasKey('content_digest', $result);
        $this->assertNotEmpty($result['content_digest']);
        
        $this->assertEquals(64, strlen($result['content_digest']));
    }

    public function test_generates_consistent_signature_for_same_input()
    {
        $date = Carbon::parse('2022-08-15T14:59:47.585Z');
        $body = ['name' => 'John', 'age' => 30];
        
        $result1 = $this->service->generateSignature('POST', '/api/users', $body, 'application/json', $date);
        $result2 = $this->service->generateSignature('POST', '/api/users', $body, 'application/json', $date);
        
        $this->assertEquals($result1['authorization'], $result2['authorization']);
        $this->assertEquals($result1['content_digest'], $result2['content_digest']);
    }

    public function test_sorts_body_keys_alphabetically()
    {
        $date = Carbon::parse('2022-08-15T14:59:47.585Z');
        
        $body1 = ['z' => 1, 'a' => 2, 'm' => 3];
        $body2 = ['a' => 2, 'm' => 3, 'z' => 1];
        
        $result1 = $this->service->generateSignature('POST', '/api/test', $body1, 'application/json', $date);
        $result2 = $this->service->generateSignature('POST', '/api/test', $body2, 'application/json', $date);
        
        $this->assertEquals($result1['content_digest'], $result2['content_digest']);
        $this->assertEquals($result1['authorization'], $result2['authorization']);
    }

    public function test_handles_nested_arrays()
    {
        $date = Carbon::parse('2022-08-15T14:59:47.585Z');
        
        $body = [
            'user' => [
                'name' => 'John',
                'details' => [
                    'age' => 30,
                    'city' => 'NYC'
                ]
            ]
        ];
        
        $result = $this->service->generateSignature('POST', '/api/users', $body, 'application/json', $date);
        
        $this->assertNotEmpty($result['content_digest']);
        $this->assertStringStartsWith('SB1-HMAC-SHA256 test-access-key:', $result['authorization']);
    }

    public function test_get_authorization_header_method()
    {
        $header = $this->service->getAuthorizationHeader('GET', '/api/test');
        
        $this->assertStringStartsWith('SB1-HMAC-SHA256 test-access-key:', $header);
    }

    public function test_validate_signature_returns_true_for_valid_signature()
    {
        $date = Carbon::parse('2022-08-15T14:59:47.585Z');
        $body = ['test' => 'data'];
        
        $signature = $this->service->generateSignature('POST', '/api/test', $body, 'application/json', $date);
        
        $isValid = $this->service->validateSignature(
            $signature['authorization'],
            'POST',
            '/api/test',
            $body,
            'application/json',
            $date
        );
        
        $this->assertTrue($isValid);
    }

    public function test_validate_signature_returns_false_for_invalid_signature()
    {
        $date = Carbon::parse('2022-08-15T14:59:47.585Z');
        
        $isValid = $this->service->validateSignature(
            'SB1-HMAC-SHA256 test-access-key:invalid-signature',
            'POST',
            '/api/test',
            ['test' => 'data'],
            'application/json',
            $date
        );
        
        $this->assertFalse($isValid);
    }
}