<?php

namespace Tests;

use GuzzleHttp\Client;

class NoStoreTest extends Test
{
    public function testNoStore()
    {
        $client = new Client([
            'handler' => $this->handler,
            'base_uri' => 'http://localhost:8000'
        ]);

        $client->get('no_store.php');

        // As response has no-store directive
        $this->assertFalse($this->storage->hasCachedResponse());
    }

    public function testPrivate()
    {
        $client = new Client([
            'handler' => $this->handler,
            'base_uri' => 'http://localhost:8000'
        ]);

        $client->get('private.php');

        // As response has private directive
        $this->assertFalse($this->storage->hasCachedResponse());
    }

    public function testAuthorized()
    {
        $client = new Client([
            'handler' => $this->handler,
            'base_uri' => 'http://localhost:8000'
        ]);

        $client->get('index.php', [
            'headers' => [
                'Authorization' => 'Bearer 1234567890'
            ]
        ]);

        // As request has Authorization and doesn't have public directive
        $this->assertFalse($this->storage->hasCachedResponse());
    }
}