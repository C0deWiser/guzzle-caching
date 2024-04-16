<?php

namespace Tests;

use GuzzleHttp\Client;

class NoCacheControlTest extends Test
{
    public function testIndex()
    {
        $client = new Client([
            'handler'  => $this->handler,
            'base_uri' => 'http://localhost:8000'
        ]);

        $client->get('index.php');

        // Response was cached
        $this->assertTrue($this->storage->hasCachedResponse());

        // As response has no expires and has no max-age — is as stale
        $this->assertTrue($this->storage->getCachedResponse()->isStale());

        // Make next request
        $response = $client->get('index.php');

        // Response was not reused
        $this->assertArrayNotHasKey('X-Cache-Controlled', $response->getHeaders());
    }

    public function testPublic()
    {
        $client = new Client([
            'handler'  => $this->handler,
            'base_uri' => 'http://localhost:8000'
        ]);

        $headers = ['headers' => ['Authorization' => 'Bearer 1234567890']];
        $client->get('public.php', $headers);

        // Response was cached as it has public directive
        $this->assertTrue($this->storage->hasCachedResponse());

        // As response has no expires and has no max-age — is as stale
        $this->assertTrue($this->storage->getCachedResponse()->isStale());

        // Make next request
        $response = $client->get('index.php', $headers);

        // Response was not reused
        $this->assertArrayNotHasKey('X-Cache-Controlled', $response->getHeaders());
    }
}