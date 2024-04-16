<?php

namespace Tests;

use GuzzleHttp\Client;

class LastModifiedTest extends Test
{
    public function testLastModified()
    {
        $client = new Client([
            'handler' => $this->handler,
            'base_uri' => 'http://localhost:8000'
        ]);

        $response = $client->get('last_modified.php');

        $body = $response->getBody()->getContents();

        // Response was cached
        $this->assertTrue($this->storage->hasCachedResponse());

        // It is stale
        $this->assertTrue($this->storage->getCachedResponse()->isStale());

        // Repeat
        $response = $client->get('last_modified.php');

        // Response was reused
        $this->assertArrayHasKey('X-Cache-Controlled', $response->getHeaders());

        $this->assertEquals($body, $response->getBody()->getContents());
    }
}