<?php

namespace Tests;

use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Pm\GuzzleCaching\CacheControlStorage;

class VaryTest extends TestCase
{
    public function test()
    {
        $cache = new ArrayCache(86000);

        $headers = [
            'Content-Type' => ['text/html'],
            'Vary'         => ['Accept-Language'],
        ];

        $request = new Request('GET', 'http://127.0.0.1/');
        $requestWithLang = new Request('GET', 'http://127.0.0.1/', [
            'Accept-Language' => ['en-US'],
        ]);
        $response = new Response(200, $headers, 'test');

        // Make request and store response
        $storage = CacheControlStorage::make($cache)
            ->withRequest($request)
            ->withResponse($response);

        $storage->storeResponse();

        // This request has cached response
        $this->assertTrue($storage->hasCachedResponse());

        $storage = CacheControlStorage::make($cache)
            ->withRequest($requestWithLang);

        // This request doesn't have cached response
        $this->assertFalse($storage->hasCachedResponse());

        // Make request w/lang and store response
        CacheControlStorage::make($cache)
            ->withRequest($requestWithLang)
            ->withResponse($response)
            ->storeResponse();

        // This request has cached response
        $this->assertTrue($storage->hasCachedResponse());

        // First request has cached response too
        $storage = CacheControlStorage::make($cache)
            ->withRequest($request);
        $this->assertTrue($storage->hasCachedResponse());
    }
}