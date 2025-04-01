<?php

use Eugenefvdm\Api\Whm;
use Eugenefvdm\Api\Contracts\WhmInterface;
use Illuminate\Support\Facades\Http;

test('bandwidth returns bandwidth information', function () {
    $stub = json_decode(file_get_contents(__DIR__.'/../stubs/whm/bandwidth_success.json'), true);

    Http::fake([
        'test.example.com:2087/json-api/showbw' => Http::response($stub, 200),
    ]);

    $api = new Whm('test_user', 'test_pass', 'https://test.example.com:2087');

    $result = $api->bandwidth();

    expect($result)->toBe($stub);
    expect($result['bandwidth'][0]['acct'])->toHaveCount(2);
    expect($result['bandwidth'][0]['acct'][0]['maindomain'])->toBe('example1.com');
    expect($result['bandwidth'][0]['acct'][0]['totalbytes'])->toBe(7021740625);
    expect($result['bandwidth'][0]['acct'][1]['maindomain'])->toBe('example2.com');
    expect($result['bandwidth'][0]['acct'][1]['totalbytes'])->toBe(8179292839);

    Http::assertSent(function ($request) {
        return $request->url() === 'https://test.example.com:2087/json-api/showbw'
            && $request->method() === 'GET'
            && $request->header('Authorization')[0] === 'WHM test_user:test_pass';
    });
});

test('it can suspend an email account successfully', function () {    
    $whm = mock(WhmInterface::class);
    
    $whm->shouldReceive('suspendEmail')
        ->with('username', 'user@example.com')
        ->andReturn([
            'status' => 'success',
            'code' => 200,
            'output' => []
        ]);

    $result = $whm->suspendEmail('username', 'user@example.com');

    expect($result['status'])->toBe('success');
    expect($result['code'])->toBe(200);
    expect($result['output'])->toBe([]);
});

test('it returns 404 when email account does not exist', function () {    
    $whm = mock(WhmInterface::class);
    
    $whm->shouldReceive('suspendEmail')
        ->with('username', 'user@example.com')
        ->andReturn([
            'status' => 'error',
            'code' => 404,
            'output' => "Email address 'user@example.com' not found"
        ]);

    $result = $whm->suspendEmail('username', 'user@example.com');

    expect($result['status'])->toBe('error');
    expect($result['code'])->toBe(404);
    expect($result['output'])->toBe("Email address 'user@example.com' not found");
});

test('it returns 400 when email is already suspended', function () {
    $whm = mock(WhmInterface::class);
    
    $whm->shouldReceive('suspendEmail')
        ->with('username', 'user@example.com')
        ->andReturn([
            'status' => 'error',
            'code' => 400,
            'output' => 'Logins for "user@example.com" are suspended.'
        ]);

    $result = $whm->suspendEmail('username', 'user@example.com');

    expect($result['status'])->toBe('error');
    expect($result['code'])->toBe(400);
    expect($result['output'])->toBe('Logins for "user@example.com" are suspended.');
});