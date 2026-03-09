<?php

use App\Http\Middleware\DemoMode;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

it('allows request when demo mode is enabled', function (): void {
    config()->set('demo.enabled', true);

    $middleware = new DemoMode;
    $response = $middleware->handle(Request::create('/demo/test'), fn (): \Illuminate\Http\Response => new Response('ok'));

    expect($response->getStatusCode())->toBe(200);
});

it('aborts with 403 when demo mode is disabled', function (): void {
    config()->set('demo.enabled', false);

    $middleware = new DemoMode;
    $middleware->handle(Request::create('/demo/test'), fn (): \Illuminate\Http\Response => new Response('ok'));
})->throws(\Symfony\Component\HttpKernel\Exception\HttpException::class);
